<?php

namespace App\Http\Controllers\Backend\AI;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\ExpectedState;
use App\Models\SearchUserChat;
use App\Models\StrategyResource;
use App\Models\StrategyResourceChange;
use App\Models\User;
use App\Services\AI\AiProviderService;
use App\Services\AI\DocumentContextService;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Publish gate (Features spec, phase 1): a strategy stays Draft until a leader
 * commits per-department resources and publishes it to their organization.
 * Kept out of AiChatController, which is already past 2,000 lines.
 */
class StrategyPublishController extends Controller
{
    private const WHOLE_ORG = 'Whole organization';

    public function __construct(
        protected AiProviderService $ai,
        protected DocumentContextService $docs,
        protected OrganizationService $orgs,
    ) {}

    public function show(Request $request, $chat): JsonResponse
    {
        $record = $this->ownChat($request, $chat);
        if (! $record) {
            return $this->denied();
        }

        return response()->json($this->payload($record, $request->user()));
    }

    public function save(Request $request): JsonResponse
    {
        $data = $request->validate([
            'chat_id' => 'required|integer',
            'rows' => 'present|array',
            'rows.*.id' => 'nullable|integer',
            'rows.*.department_id' => 'nullable|integer',
            'rows.*.department_name' => 'required|string|max:255',
            'rows.*.budget' => 'nullable|numeric|min:0|max:9999999999',
            'rows.*.fte' => 'nullable|numeric|min:0|max:9999',
            'rows.*.tools' => 'nullable|string|max:5000',
            'rows.*.notes' => 'nullable|string|max:5000',
        ]);

        $user = $request->user();
        $chat = $this->ownChat($request, $data['chat_id']);
        if (! $chat) {
            return $this->denied();
        }

        $departments = $this->departmentsFor($user)->keyBy('id');
        $rows = [];
        foreach ($data['rows'] as $row) {
            $deptId = $row['department_id'] ?? null;
            if ($deptId !== null && ! $departments->has($deptId)) {
                return response()->json(['error' => 'That department is not in your organization.'], 422);
            }
            $rows[] = [
                'id' => $row['id'] ?? null,
                'department_id' => $deptId,
                'department_name' => $deptId !== null ? $departments[$deptId]->name : self::WHOLE_ORG,
                'budget' => $row['budget'] ?? null,
                'fte' => $row['fte'] ?? null,
                'tools' => $this->text($row['tools'] ?? null),
                'notes' => $this->text($row['notes'] ?? null),
            ];
        }

        DB::transaction(function () use ($chat, $rows) {
            $keep = [];
            foreach ($rows as $row) {
                $attrs = collect($row)->except('id')->all();
                $existing = $row['id'] ? $chat->resources()->whereKey($row['id'])->first() : null;
                if ($existing) {
                    $existing->update($attrs);
                    $keep[] = $existing->id;
                } else {
                    $keep[] = $chat->resources()->create($attrs)->id;
                }
            }
            $chat->resources()->whereNotIn('id', $keep)->delete();
        });

        return response()->json($this->payload($chat->fresh(), $user));
    }

    // -------------------------------------------------------------------------

    /** The chat, only if the signed-in user wrote it. */
    private function ownChat(Request $request, $chatId): ?SearchUserChat
    {
        if (! $chatId || ! SearchUserChat::where('id', $chatId)->where('user_id', $request->user()->id)->exists()) {
            return null;
        }

        return SearchUserChat::find($chatId);
    }

    private function denied(): JsonResponse
    {
        return response()->json(['error' => 'Strategy not found or access denied.'], 403);
    }

    /** @return Collection<int, Department> */
    private function departmentsFor(User $user): Collection
    {
        if (! $user->organization_id) {
            return collect();
        }

        return Department::where('organization_id', $user->organization_id)->orderBy('name')->get(['id', 'name']);
    }

    private function text($value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' ? null : $value;
    }

    /** @return array<string, mixed> */
    private function payload(SearchUserChat $chat, User $user): array
    {
        $rows = $chat->resources()->orderBy('id')->get();
        $changes = StrategyResourceChange::whereIn('strategy_resource_id', $rows->pluck('id'))
            ->with('user:id,name')->orderByDesc('id')->get();
        $names = $rows->pluck('department_name', 'id');

        return [
            'status' => $chat->status ?? 'draft',
            'is_publisher' => $chat->isPublished() && (int) $chat->published_by === (int) $user->id,
            'can_publish' => $this->orgs->canPublish($user),
            'ready' => ! empty($chat->leadership_brief)
                || ExpectedState::where('search_user_chat_id', $chat->id)->exists(),
            'published_by' => $chat->publisher?->name,
            'published_at' => $chat->published_at?->toIso8601String(),
            'currency' => config('custom.default_currency_symbol') ?: '$',
            'departments' => $this->departmentsFor($user)->map(fn ($d) => ['id' => $d->id, 'name' => $d->name])->values(),
            'rows' => $rows->map(fn (StrategyResource $r) => [
                'id' => $r->id,
                'department_id' => $r->department_id,
                'department_name' => $r->department_name,
                'budget' => $r->budget !== null ? (float) $r->budget : null,
                'fte' => $r->fte !== null ? (float) $r->fte : null,
                'tools' => $r->tools,
                'notes' => $r->notes,
                'ai_suggestion' => $r->ai_suggestion,
            ])->values(),
            'changes' => $changes->map(fn (StrategyResourceChange $c) => [
                'department_name' => $names[$c->strategy_resource_id] ?? '',
                'field' => $c->field,
                'old_value' => $c->old_value,
                'new_value' => $c->new_value,
                'user' => $c->user?->name,
                'at' => $c->created_at?->toIso8601String(),
            ])->values(),
        ];
    }
}

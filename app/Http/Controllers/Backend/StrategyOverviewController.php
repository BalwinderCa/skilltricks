<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\SearchUserChat;
use App\Models\User;
use App\Services\DriftIndex;
use App\Services\MyGoals;
use App\Services\OrganizationService;
use App\Services\Recourse;
use App\Services\StrategyAlerts;
use App\Services\StrategyOverview;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Executive view (Features spec, phase 5), for leaders only. */
class StrategyOverviewController extends Controller
{
    public function __construct(protected StrategyOverview $overview, protected OrganizationService $orgs) {}

    public function index(Request $request): View
    {
        abort_unless($this->orgs->isLeader($request->user()), 403);

        return view('backend.pages.strategies.index', [
            'strategies' => $this->overview->list($request->user()),
            'settings' => StrategyOverview::settingsFor($request->user()->organization),
            'isOwner' => (int) optional($request->user()->organization)->owner_user_id === (int) $request->user()->id,
        ]);
    }

    public function show(Request $request, $chat): View
    {
        abort_unless($this->orgs->isLeader($request->user()), 403);
        $detail = $this->overview->detail($request->user(), (int) $chat);
        abort_unless($detail, 404);

        return view('backend.pages.strategies.show', $detail);
    }

    public function recourse(Request $request, $chat, Recourse $recourse, DriftIndex $drift): RedirectResponse
    {
        abort_unless($this->orgs->isLeader($request->user()), 403);
        $record = SearchUserChat::whereKey((int) $chat)->where('status', 'published')
            ->where('organization_id', (int) $request->user()->organization_id)->first();
        abort_unless($record, 404);

        // Each call is a paid AI request: only a strategy in severe drift gets one.
        if ($drift->evaluate($record, alert: false)['level'] !== 'red') {
            flash(localize('Recourse is suggested for strategies in severe drift only.'))->info();

            return back();
        }
        if ($recourse->suggest($record, $request->user())) {
            flash(localize('Recourse options are ready'))->success();
        } else {
            flash(localize('We could not suggest recourse just now, please try again.'))->warning();
        }

        return back();
    }

    public function settings(Request $request): RedirectResponse
    {
        $org = $request->user()->organization;
        abort_unless($org && (int) $org->owner_user_id === (int) $request->user()->id, 403);
        $data = $request->validate([
            'hourly_rate' => 'required|numeric|min:0|max:100000',
            'manual_hours' => 'required|numeric|min:0|max:1000',
            'oi_minutes' => 'required|numeric|min:0|max:10000',
            'token_cost' => 'required|numeric|min:0|max:100',
        ]);
        $org->forceFill(['command_settings' => array_map('floatval', $data)])->save();
        flash(localize('Savings assumptions updated'))->success();

        return back();
    }

    public function approve(Request $request, $chat, StrategyAlerts $alerts, MyGoals $goals): RedirectResponse
    {
        $record = $this->pendingFor($request, (int) $chat);
        // Conditional on still pending: a double click decides once.
        $decided = SearchUserChat::whereKey($record->id)->where('status', 'pending_approval')->update([
            'status' => 'published', 'published_at' => now(), 'approval_decided_by' => $request->user()->id,
            'approval_decided_at' => now(), 'approval_note' => null,
        ]);
        abort_unless($decided, 404);
        $this->tellRequester($record, $alerts, localize('Initiative approved').': '.$goals->companyGoal($record),
            $request->user()->name.' approved your initiative. It is now live for your organization.');
        flash(localize('Initiative approved and published'))->success();

        return back();
    }

    public function reject(Request $request, $chat, StrategyAlerts $alerts, MyGoals $goals): RedirectResponse
    {
        $data = $request->validate(['note' => 'required|string|max:500']);
        $record = $this->pendingFor($request, (int) $chat);
        $decided = SearchUserChat::whereKey($record->id)->where('status', 'pending_approval')->update([
            'status' => 'draft', 'published_by' => null, 'approval_decided_by' => $request->user()->id,
            'approval_decided_at' => now(), 'approval_note' => trim($data['note']),
        ]);
        abort_unless($decided, 404);
        $this->tellRequester($record, $alerts, localize('Initiative sent back').': '.$goals->companyGoal($record),
            $request->user()->name.': "'.trim($data['note']).'"');
        flash(localize('Initiative sent back to its author'))->success();

        return back();
    }

    private function pendingFor(Request $request, int $chatId): SearchUserChat
    {
        $record = SearchUserChat::whereKey($chatId)->where('status', 'pending_approval')
            ->where('organization_id', (int) $request->user()->organization_id)->with('parentChat')->first();
        abort_unless($record, 404);
        abort_unless($this->overview->canApprove($request->user(), $record), 403);

        return $record;
    }

    private function tellRequester(SearchUserChat $record, StrategyAlerts $alerts, string $title, string $body): void
    {
        $requester = User::whereKey($record->published_by ?: $record->user_id)
            ->where('organization_id', $record->organization_id)->first();
        if ($requester) {
            $alerts->send($requester, $title, 'dashboard/users-new-chat/'.$record->id, $body, 'approval_decision');
        }
    }
}

<?php

namespace App\Services;

use App\Models\ExpectedState;
use App\Models\GoalProgressUpdate;
use App\Models\GoalResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Execution telemetry (Features spec, phase 6): what the people holding a goal
 * report about it. Every report is kept; the latest sits on their response.
 */
class ProgressUpdates
{
    public const STATUSES = ['not_started', 'in_progress', 'completed', 'blocked'];

    public function __construct(protected DriftIndex $drift) {}

    public function record(ExpectedState $goal, User $member, string $status, int $pct, ?string $note): void
    {
        $pct = $status === 'completed' ? 100 : max(0, min(100, $pct));
        $note = $note !== null && trim($note) !== '' ? trim($note) : null;

        DB::transaction(function () use ($goal, $member, $status, $pct, $note) {
            GoalProgressUpdate::create(['expected_state_id' => $goal->id, 'user_id' => $member->id, 'status' => $status, 'pct' => $pct, 'note' => $note]);
            $response = GoalResponse::firstOrNew(['expected_state_id' => $goal->id, 'user_id' => $member->id]);
            $response->fill(['progress_status' => $status, 'progress_pct' => $pct, 'progress_note' => $note, 'progress_at' => now()]);
            if ($response->decision === null) {
                $response->decision = 'act_on_it';
                $response->decided_at = now();
            }
            $response->save();
        });

        $this->drift->evaluate($goal->searchUserChat);
    }
}

<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\GoalObstacle;
use App\Models\GoalResponse;
use App\Services\GoalRevisions;
use App\Services\MyGoals;
use App\Services\OrganizationService;
use App\Services\ProgressUpdates;
use App\Services\StartingPoints;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The "My goal" card's two actions: respond to a published goal, and report
 * what is in its way. Visibility is MyGoals' rule; anything else is a 404.
 */
class MyGoalController extends Controller
{
    public function __construct(
        protected MyGoals $goals,
        protected StartingPoints $starts,
        protected GoalRevisions $revisions,
        protected OrganizationService $orgs,
        protected ProgressUpdates $progressUpdates,
    ) {}

    public function decide(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'goal_id' => 'required|integer',
            'decision' => ['required', Rule::in(MyGoals::DECISIONS)],
        ]);
        $goal = $this->goals->visibleGoal($request->user(), (int) $data['goal_id']);
        abort_unless($goal, 404);

        GoalResponse::updateOrCreate(
            ['expected_state_id' => $goal->id, 'user_id' => $request->user()->id],
            ['decision' => $data['decision'], 'decided_at' => now()],
        );
        if ($data['decision'] === 'act_on_it' && ! $this->starts->ensureOptions($goal, $request->user())) {
            flash(localize('Saved. We could not suggest starting points just now, please try again.'))->warning();

            return back();
        }
        flash(localize('Your response has been saved'))->success();

        return back();
    }

    public function reportObstacle(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'goal_id' => 'required|integer',
            'body' => 'required|string|max:2000',
        ]);
        $goal = $this->goals->visibleGoal($request->user(), (int) $data['goal_id']);
        abort_unless($goal, 404);

        GoalObstacle::create(['expected_state_id' => $goal->id, 'user_id' => $request->user()->id, 'body' => trim($data['body'])]);
        flash(localize('Thanks, your obstacle has been recorded'))->success();

        return back();
    }

    public function suggestStart(Request $request): RedirectResponse
    {
        $data = $request->validate(['goal_id' => 'required|integer']);
        $goal = $this->goals->visibleGoal($request->user(), (int) $data['goal_id']);
        abort_unless($goal, 404);

        if ($this->starts->ensureOptions($goal, $request->user())) {
            flash(localize('Here are some places to begin'))->success();
        } else {
            flash(localize('We could not suggest starting points just now, please try again.'))->warning();
        }

        return back();
    }

    public function commit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'goal_id' => 'required|integer',
            'option' => 'required|integer|min:0',
        ]);
        $goal = $this->goals->visibleGoal($request->user(), (int) $data['goal_id']);
        abort_unless($goal, 404);

        if (! $this->starts->commit($goal, $request->user(), (int) $data['option'])) {
            return back()->withErrors(['option' => localize('Pick one of the suggested starting points.')]);
        }
        flash(localize('Your starting point is committed'))->success();

        return back();
    }

    public function revise(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'goal_id' => 'required|integer',
            'text' => 'required|string|max:500',
            'reason' => 'nullable|string|max:500',
        ]);
        $goal = $this->goals->visibleGoal($request->user(), (int) $data['goal_id']);
        abort_unless($goal, 404);
        abort_unless($this->orgs->isLeader($request->user()), 403);

        if ($this->revisions->revise($goal, $request->user(), $data['text'], $data['reason'] ?? null)) {
            flash(localize('The goal has been updated'))->success();
        }

        return back();
    }

    public function progress(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'goal_id' => 'required|integer',
            'status' => ['required', Rule::in(ProgressUpdates::STATUSES)],
            'pct' => 'required|integer|min:0|max:100',
            'note' => 'nullable|string|max:500',
        ]);
        $goal = $this->goals->visibleGoal($request->user(), (int) $data['goal_id']);
        abort_unless($goal, 404);

        $this->progressUpdates->record($goal, $request->user(), $data['status'], (int) $data['pct'], $data['note'] ?? null);
        flash(localize('Your update has been posted'))->success();

        return back();
    }
}

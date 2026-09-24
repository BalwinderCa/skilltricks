<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\GoalObstacle;
use App\Models\GoalResponse;
use App\Services\MyGoals;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The "My goal" card's two actions: respond to a published goal, and report
 * what is in its way. Visibility is MyGoals' rule; anything else is a 404.
 */
class MyGoalController extends Controller
{
    public function __construct(protected MyGoals $goals) {}

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
}

<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ExpectedState;
use App\Models\GoalCascade;
use App\Services\Cascades;
use App\Services\ProgressUpdates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Notion Epic 3: cascade a goal to direct reports, and report on a cascaded sub-goal. */
class CascadeController extends Controller
{
    public function __construct(protected Cascades $cascades) {}

    public function suggest(Request $request): RedirectResponse
    {
        [$root, $parent] = $this->source($request);
        $count = $this->cascades->suggest($request->user(), $root, $parent);
        $count > 0
            ? flash(localize('Draft sub-goals are ready to review'))->success()
            : flash(localize('We could not suggest sub-goals just now, please try again.'))->warning();

        return back();
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate(['assignee_id' => 'required|integer', 'text' => 'required|string|max:300']);
        [$root, $parent] = $this->source($request);
        if (! $this->cascades->add($request->user(), $root, $parent, (int) $data['assignee_id'], $data['text'])) {
            return back()->withErrors(['assignee_id' => localize('Pick one of your direct reports.')]);
        }
        flash(localize('Draft added'))->success();

        return back();
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate(['texts' => 'nullable|array', 'texts.*' => 'nullable|string|max:300', 'remove' => 'nullable|array', 'remove.*' => 'integer']);
        [$root, $parent] = $this->source($request);
        $count = $this->cascades->send($request->user(), $root, $parent, $data['texts'] ?? [], $data['remove'] ?? []);
        flash($count > 0 ? localize('Sent to your team') : localize('Nothing was sent'))->success();

        return back();
    }

    public function progress(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cascade_id' => 'required|integer',
            'status' => ['required', Rule::in(ProgressUpdates::STATUSES)],
            'pct' => 'required|integer|min:0|max:100',
            'note' => 'nullable|string|max:500',
        ]);
        $source = $this->cascades->source($request->user(), null, (int) $data['cascade_id']);
        abort_unless($source && $source[1], 404);

        $this->cascades->progress($source[1], $data['status'], (int) $data['pct'], $data['note'] ?? null);
        flash(localize('Your update has been posted'))->success();

        return back();
    }

    /** @return array{0: ExpectedState, 1: GoalCascade|null} */
    private function source(Request $request): array
    {
        $request->validate(['goal_id' => 'nullable|integer|required_without:cascade_id', 'cascade_id' => 'nullable|integer']);
        $source = $this->cascades->source($request->user(), $request->integer('goal_id') ?: null, $request->integer('cascade_id') ?: null);
        abort_unless($source, 404);
        abort_if($this->cascades->reportsOf($request->user())->isEmpty(), 403);

        return $source;
    }
}

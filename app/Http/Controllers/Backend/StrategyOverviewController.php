<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\SearchUserChat;
use App\Services\OrganizationService;
use App\Services\Recourse;
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

    public function recourse(Request $request, $chat, Recourse $recourse): RedirectResponse
    {
        abort_unless($this->orgs->isLeader($request->user()), 403);
        $record = SearchUserChat::whereKey((int) $chat)->where('status', 'published')
            ->where('organization_id', (int) $request->user()->organization_id)->first();
        abort_unless($record, 404);

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
}

<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\OrganizationService;
use App\Services\StrategyOverview;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Executive view (Features spec, phase 5), for leaders only. */
class StrategyOverviewController extends Controller
{
    public function __construct(protected StrategyOverview $overview, protected OrganizationService $orgs) {}

    public function index(Request $request): View
    {
        abort_unless($this->orgs->isLeader($request->user()), 403);

        return view('backend.pages.strategies.index', ['strategies' => $this->overview->list($request->user())]);
    }

    public function show(Request $request, $chat): View
    {
        abort_unless($this->orgs->isLeader($request->user()), 403);
        $detail = $this->overview->detail($request->user(), (int) $chat);
        abort_unless($detail, 404);

        return view('backend.pages.strategies.show', $detail);
    }
}

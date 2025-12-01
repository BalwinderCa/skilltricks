<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class RoleGoalsExport implements FromView, ShouldAutoSize
{
    protected $roleGoals;
    protected $goal;
    protected $scenario;
    protected $strategy;

    public function __construct($roleGoals, $goal = '', $scenario = '', $strategy = '')
    {
        $this->roleGoals = $roleGoals;
        $this->goal = $goal;
        $this->scenario = $scenario;
        $this->strategy = $strategy;
    }

    public function view(): View
    {
        return view('exports.role_goals_export', [
            'roleGoals' => $this->roleGoals,
            'goal' => $this->goal,
            'scenario' => $this->scenario,
            'strategy' => $this->strategy,
        ]);
    }
}


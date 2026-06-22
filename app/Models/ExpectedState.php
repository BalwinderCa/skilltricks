<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpectedState extends Model
{
    protected $table = 'expected_states';

    protected $fillable = [
        'search_user_chat_id',
        'role',
        'recommended_action',
        'decision',
        'success_metric',
        'target_value',
        'target_date',
        'resources_committed',
        'depends_on_id',
    ];

    protected $casts = [
        'resources_committed' => 'boolean',
        'target_date' => 'date',
    ];

    public function searchUserChat()
    {
        return $this->belongsTo(SearchUserChat::class, 'search_user_chat_id');
    }

    public function observedStates()
    {
        return $this->hasMany(ObservedState::class, 'expected_state_id');
    }

    public function latestObservation()
    {
        return $this->hasOne(ObservedState::class, 'expected_state_id')->latestOfMany();
    }

    public function dependsOn()
    {
        return $this->belongsTo(ExpectedState::class, 'depends_on_id');
    }

    public function dependents()
    {
        return $this->hasMany(ExpectedState::class, 'depends_on_id');
    }

    public function interventions()
    {
        return $this->hasMany(Intervention::class, 'expected_state_id');
    }

    public function latestIntervention()
    {
        return $this->hasOne(Intervention::class, 'expected_state_id')->latestOfMany();
    }
}

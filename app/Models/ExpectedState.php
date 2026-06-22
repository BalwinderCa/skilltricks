<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $search_user_chat_id
 * @property string $role
 * @property string $recommended_action
 * @property string|null $decision
 * @property string|null $success_metric
 * @property string|null $target_value
 * @property string|null $target_date
 * @property bool $resources_committed
 * @property int|null $depends_on_id
 * @property string|null $drift_status
 * @property-read SearchUserChat $searchUserChat
 * @property-read ExpectedState|null $dependsOn
 * @property-read ObservedState|null $latestObservation
 * @property-read Intervention|null $latestIntervention
 */
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

    public function searchUserChat(): BelongsTo
    {
        return $this->belongsTo(SearchUserChat::class, 'search_user_chat_id');
    }

    public function observedStates(): HasMany
    {
        return $this->hasMany(ObservedState::class, 'expected_state_id');
    }

    public function latestObservation(): HasOne
    {
        return $this->hasOne(ObservedState::class, 'expected_state_id')->latestOfMany();
    }

    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(ExpectedState::class, 'depends_on_id');
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(ExpectedState::class, 'depends_on_id');
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class, 'expected_state_id');
    }

    public function latestIntervention(): HasOne
    {
        return $this->hasOne(Intervention::class, 'expected_state_id')->latestOfMany();
    }
}

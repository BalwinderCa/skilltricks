<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SearchUserChat extends Model
{
    protected $table = 'search_user_chat';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'total_tokens',
        'answers',
        'chat_role_categories',
        'categories',
        'subcategories',
        'questionmenuid',
        'search',
        'response',
        'status1',
        'status2',
        'selected_strategy',
        'selected_scenario',
        'additional_context',
        'leadership_brief',
        'organization_id',
        'status',
        'published_by',
        'published_at',
        'drift_index',
        'drift_level',
        'drift_alerted_level',
        'drift_checked_at',
        'recourse',
        'parent_chat_id',
        'correlation_score',
        'correlation_reason',
        'approval_requested_at',
        'approval_decided_by',
        'approval_decided_at',
        'approval_note',
    ];

    protected $casts = [
        'additional_context' => 'array',
        'total_tokens' => 'integer',
        'status1' => 'integer',
        'status2' => 'integer',
        'published_at' => 'datetime',
        'drift_checked_at' => 'datetime',
        'recourse' => 'array',
        'approval_requested_at' => 'datetime',
        'approval_decided_at' => 'datetime',
        'correlation_score' => 'integer',
    ];

    /** Matches the column default, so a freshly created model reads 'draft' too. */
    protected $attributes = [
        'status' => 'draft',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(SearchUserChatData::class, 'search_user_chat_id');
    }

    /** @return HasMany<StrategyResource, $this> */
    public function resources()
    {
        return $this->hasMany(StrategyResource::class, 'search_user_chat_id');
    }

    /** @return BelongsTo<User, $this> */
    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /** Published, or waiting for upstream approval: either way the author can no longer edit it. */
    public function isLocked(): bool
    {
        return in_array($this->status, ['published', 'pending_approval'], true);
    }

    /** @return BelongsTo<SearchUserChat, $this> */
    public function parentChat()
    {
        return $this->belongsTo(SearchUserChat::class, 'parent_chat_id');
    }

    /** @return HasMany<SearchUserChat, $this> */
    public function childChats()
    {
        return $this->hasMany(SearchUserChat::class, 'parent_chat_id');
    }

    public function isFirstMessage(): bool
    {
        return $this->status1 == 0;
    }

    public function incrementTokens(int $tokens): void
    {
        if ($tokens > 0) {
            $this->increment('total_tokens', $tokens);
        }
    }

    public function appendAdditionalContext(string $details): void
    {
        $existing = $this->additional_context ?? [];
        $existing[] = ['additional_details' => $details, 'created_at' => now()->toDateTimeString()];

        $this->update(['additional_context' => $existing]);
    }

    /**
     * Build the "ADDITIONAL USER CONTEXT" block to inject into a system message.
     * Returns an empty string if no context has been stored.
     */
    public function additionalContextBlock(): string
    {
        $contextList = $this->additional_context;

        if (empty($contextList) || ! is_array($contextList)) {
            return '';
        }

        $block = "\n\n--- ADDITIONAL USER CONTEXT (PREVIOUSLY PROVIDED) ---\n";

        foreach ($contextList as $ctx) {
            if (! empty($ctx['additional_details'])) {
                $block .= 'Additional Details: '.$ctx['additional_details']."\n";
            }
        }

        return $block."--- END ADDITIONAL USER CONTEXT ---\n";
    }

    /**
     * The strategy and scenario this chat is working in.
     *
     * These were stored when the user picked them and then read back nowhere, so
     * every follow-up message was generated as if no choice had been made and the
     * model drifted back to the best case it first produced. This block is what
     * carries the choice forward.
     *
     * Read from the row rather than the request: the choice has to survive a page
     * reload, and only some of the front end's calls remember to send it.
     */
    public function selectionBlock(): string
    {
        $strategy = trim((string) $this->selected_strategy);
        $scenario = trim((string) $this->selected_scenario);

        if ($strategy === '' && $scenario === '') {
            return '';
        }

        $block = "\n\n--- ACTIVE SELECTION ---\n";

        if ($strategy !== '') {
            $block .= 'Strategy: '.$this->sanitiseForPrompt($strategy)."\n";
        }

        if ($scenario !== '') {
            $block .= 'Scenario: '.$this->sanitiseForPrompt($scenario)."\n"
                ."Answer every part of this conversation in terms of that scenario. Do not\n"
                ."revert to the best case, and do not re-describe the other scenarios unless\n"
                ."the user asks for them.\n";
        }

        return $block."--- END ACTIVE SELECTION ---\n";
    }

    /**
     * Flatten a stored value before it is rendered into a fenced prompt block.
     *
     * A scenario label carrying a newline and its own "---" fence would otherwise
     * close the block and continue as instructions.
     */
    private function sanitiseForPrompt(string $value): string
    {
        return trim(preg_replace('/\s*-{3,}\s*/', ' ', preg_replace('/\s+/', ' ', $value)));
    }
}

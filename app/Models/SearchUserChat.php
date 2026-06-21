<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchUserChat extends Model
{
    protected $table = 'search_user_chat';

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
    ];

    protected $casts = [
        'additional_context' => 'array',
        'total_tokens'       => 'integer',
        'status1'            => 'integer',
        'status2'            => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(SearchUserChatData::class, 'search_user_chat_id');
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
        $existing   = $this->additional_context ?? [];
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

        if (empty($contextList) || !is_array($contextList)) {
            return '';
        }

        $block = "\n\n--- ADDITIONAL USER CONTEXT (PREVIOUSLY PROVIDED) ---\n";

        foreach ($contextList as $ctx) {
            if (!empty($ctx['additional_details'])) {
                $block .= "Additional Details: " . $ctx['additional_details'] . "\n";
            }
        }

        return $block . "--- END ADDITIONAL USER CONTEXT ---\n";
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $search_user_chat_id
 * @property int|null $user_id
 * @property string|null $search
 * @property string|null $response
 * @property Carbon|null $created_at
 */
class SearchUserChatData extends Model
{
    protected $table = 'search_user_chat_data';

    // Table only has created_at, no updated_at.
    const UPDATED_AT = null;

    protected $fillable = [
        'search_user_chat_id',
        'user_id',
        'answers',
        'chat_role_categories',
        'categories',
        'subcategories',
        'questionmenuid',
        'search',
        'response',
        'created_at',
    ];

    public function chat()
    {
        return $this->belongsTo(SearchUserChat::class, 'search_user_chat_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

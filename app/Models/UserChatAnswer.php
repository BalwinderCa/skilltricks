<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserChatAnswer extends Model
{
    protected $table = 'user_chat_answers';

    protected $fillable = [
        'user_id',
        'answers',
        'chat_role_categories',
        'categories',
        'subcategories',
        'questionmenuid',
        'status',
        'status1',
        'status2',
    ];

    protected $casts = [
        'status'  => 'integer',
        'status1' => 'integer',
        'status2' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

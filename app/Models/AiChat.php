<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiChat extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function category()
    {
        return $this->belongsTo(AiChatCategory::class, 'ai_chat_category_id', 'id');
    }


    public function messages()
    {
        return $this->hasMany(AiChatMessage::class);
    }
}

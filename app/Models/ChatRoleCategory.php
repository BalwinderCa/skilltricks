<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatRoleCategory extends Model
{
    protected $table = 'chat_role_categories';

    // DB has a typo: 'update_at' instead of 'updated_at'
    const UPDATED_AT = 'update_at';

    protected $fillable = ['name', 'status'];

    protected $casts = ['status' => 'integer'];

    public function chatCategories()
    {
        return $this->hasMany(ChatCategory::class, 'role_name', 'name');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatCategory extends Model
{
    protected $table = 'chat_categories';

    // DB has a typo: 'update_at' instead of 'updated_at'
    const UPDATED_AT = 'update_at';

    protected $fillable = ['name', 'role_name', 'status'];

    protected $casts = ['status' => 'integer'];

    public function roleCategory()
    {
        return $this->belongsTo(ChatRoleCategory::class, 'role_name', 'name');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeForRole($query, string $roleName)
    {
        return $query->where('role_name', $roleName);
    }
}

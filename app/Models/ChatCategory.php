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

    /**
     * Nullable on purpose: a user with no role resolves to null, and the honest
     * answer for "categories for no role" is none. where('role_name', null)
     * would compile to "role_name is null" and hand back the rows that happen to
     * carry no role, which is a different question.
     */
    public function scopeForRole($query, ?string $roleName)
    {
        return $roleName === null
            ? $query->whereRaw('1 = 0')
            : $query->where('role_name', $roleName);
    }
}

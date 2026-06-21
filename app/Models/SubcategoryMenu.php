<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubcategoryMenu extends Model
{
    protected $table = 'subcategory_menu';

    protected $fillable = ['role', 'categories', 'subcategories'];

    public function questions()
    {
        return $this->hasMany(SubcategoryMenuQuestion::class, 'subcategorymenu_id');
    }

    public function activeQuestions()
    {
        return $this->hasMany(SubcategoryMenuQuestion::class, 'subcategorymenu_id')->where('status', 1);
    }

    public function scopeForRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('categories', $category);
    }

    public function scopeForSubcategory($query, ?string $subcategory)
    {
        return $subcategory
            ? $query->where('subcategories', $subcategory)
            : $query->whereNull('subcategories')->orWhere('subcategories', '');
    }
}

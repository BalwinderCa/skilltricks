<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubcategoryMenuQuestion extends Model
{
    protected $table = 'subcategory_menu_question';

    protected $fillable = ['subcategorymenu_id', 'question', 'status'];

    protected $casts = ['status' => 'integer'];

    public function menu()
    {
        return $this->belongsTo(SubcategoryMenu::class, 'subcategorymenu_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}

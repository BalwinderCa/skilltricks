<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use HasFactory;
    
    public function scopeIsActive($query)
    {
        return $query->where('is_active', 1);
    } 
    
    public function scopeIsActiveForTemplate($query)
    {
        return $query->where('is_active_for_templates', 1);
    } 
}

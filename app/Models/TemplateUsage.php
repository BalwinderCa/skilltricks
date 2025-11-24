<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateUsage extends Model
{
    use HasFactory;

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

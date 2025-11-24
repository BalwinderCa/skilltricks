<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateLocalization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'template_id',
        'lang_key',
        'description',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaqLocalization extends Model
{
    use HasFactory;
    protected $fillable = ['faq_id', 'question', 'answer', 'lang_key'];
}

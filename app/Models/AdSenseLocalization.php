<?php

namespace App\Models;

use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdSenseLocalization extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'ad_sense_id',
        'lang_key',
    ];

}

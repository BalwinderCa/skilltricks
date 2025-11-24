<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElevenLabsModel extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = ['response'=>'object'];
}

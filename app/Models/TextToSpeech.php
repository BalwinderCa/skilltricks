<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TextToSpeech extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    public function elevenLabVoice()
    {
        return $this->hasOne(ElevenLabsModelVoice::class, 'voice_id', 'voice');
    }
}

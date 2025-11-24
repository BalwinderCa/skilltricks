<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiBlogWizard extends Model
{
    use HasFactory;

    protected $table = "ai_blog_wizards";
    protected $fillable = [
        "is_blog_published",
        "user_id",
        "uuid",
        "total_words",
        "completed_step",
        "subscription_history_id",
        "created_by",
        "updated_by",
        "deleted_at",
        "prompt_tokens",
        "completion_tokens",
        "input_prompt"
    ];

    public function aiBlogWizardKeyword(){
        return $this->hasOne(AiBlogWizardKeyWord::class);
    }

    public function aiBlogWizardTitle(){
        return $this->hasOne(AiBlogWizardTitle::class);
    }

    public function aiBlogWizardImages(){
        return $this->hasMany(AiBlogWizardImage::class);
    }

    public function aiBlogWizardOutlines(){
        return $this->hasMany(AiBlogWizardOutline::class);
    }

    public function aiBlogWizardArticle(){
        return $this->hasOne(AiBlogWizardArticle::class);
    }
}

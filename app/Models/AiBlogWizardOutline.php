<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiBlogWizardOutline extends Model
{
    use HasFactory;  
    
    public function aiBlogWizard(){
        return $this->belongsTo(AiBlogWizard::class);
    }
}

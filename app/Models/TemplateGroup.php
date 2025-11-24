<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateGroup extends Model
{
    use HasFactory;

    # guarded
    protected $guarded = [
        ''
    ];
    public function templates()
    {
        return $this->hasMany(Template::class, 'template_group_id', 'id');
    }
}

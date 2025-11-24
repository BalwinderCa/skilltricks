<?php

namespace Modules\Support\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    
    protected static function newFactory()
    {
        return \Modules\Support\Database\factories\CategoryFactory::new();
    }
    public function staff()
    {
        return $this->belongsTo(User::class, 'assign_staff', 'id')->withDefault([
            'name'=>'n/a'
        ]);
    }
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'category_id', 'id');
    }
}

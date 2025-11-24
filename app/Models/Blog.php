<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        "title",
        "slug",
        "is_wizard_blog",
        "blog_category_id",
        "short_description",
        "description",
        "thumbnail_image",
        "banner",
        "video_provider",
        "video_link",
        "is_active",
        "is_popular",
        "meta_title",
        "meta_img",
        "meta_description",
        "deleted_at"
    ];

    protected $with = ['blog_localizations'];

    public function scopeIsActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function collectLocalization($entity = '', $lang_key = '')
    {
        $lang_key = $lang_key ==  '' ? App::getLocale() : $lang_key;
        $blog_localizations = $this->blog_localizations->where('lang_key', $lang_key)->first();
        return $blog_localizations != null && $blog_localizations->$entity ? $blog_localizations->$entity : $this->$entity;
    }

    public function blog_localizations()
    {
        return $this->hasMany(BlogLocalization::class);
    }

    public function blog_category()
    {
        return $this->hasOne(BlogCategory::class,'id','blog_category_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'blog_tags', 'blog_id', 'tag_id');
    }


    public function scopeFilters($query)
    {
        $request = request();

        if ($request->has("search")) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->has("category_id")) {
            $query->where('blog_category_id', $request->category_id);
        }

        if ( $request->has("is_published")) {
            $query->where('is_active', $request->is_published);
        }

        if($request->has("is_wizard_blog")){

            if(!empty($request->is_wizard_blog)){
                $isWizardBlog = $request->is_wizard_blog == 1 ? 1 : 0;

                $query->where('is_wizard_blog', $isWizardBlog);
            }
        }
    }

}

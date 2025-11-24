<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FineTuneHistory extends Model
{
    use HasFactory;
    protected $table = "fine_tune_histories";

    protected $fillable = [
        "fine_tune_id",
        "ft_model_id",
        "file_model_id",
        "title",
        "description",
        "trained_file_path",
        "training_data",
        "status",
        "status_details",
        "file_upload_response",
        "fine_tune_job_response"
    ];

    protected $casts =[
        "training_data" => "array"
    ];

    public function fineTune() : BelongsTo
    {
        return $this->belongsTo(FineTune::class, "fine_tune_id");
    }

    public function scopeFineTuneId($query, $fine_tune_id)
    {
        $query->where("fine_tune_id", $fine_tune_id);
    }
}

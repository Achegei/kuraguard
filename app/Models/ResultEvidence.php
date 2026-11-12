<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResultEvidence extends Model
{
    protected $fillable = [
        'result_submission_id',
        'file_path',
        'file_hash',
        'form_type',
        'uploaded_by'
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(ResultSubmission::class, 'result_submission_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

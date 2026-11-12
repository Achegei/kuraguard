<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidateResult extends Model
{
    protected $fillable = [
        'result_submission_id',
        'candidate_id',
        'votes'
    ];

    protected function casts(): array
    {
        return [
            'votes' => 'integer',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(ResultSubmission::class, 'result_submission_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}

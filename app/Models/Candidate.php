<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    protected $fillable = [
        'election_id',
        'name',
        'party',
        'is_client'
    ];

    protected function casts(): array
    {
        return [
            'is_client' => 'boolean',
        ];
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(CandidateResult::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResultSubmission extends Model
{
    protected $fillable = [
        'polling_station_id',
        'agent_id',
        'valid_votes',
        'rejected_votes',
        'status',
        'notes',
        'submitted_at',
        'verified_at',
        'verified_by'
    ];

    protected function casts(): array
    {
        return [
            'valid_votes' => 'integer',
            'rejected_votes' => 'integer',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function pollingStation(): BelongsTo
    {
        return $this->belongsTo(PollingStation::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function candidateResults(): HasMany
    {
        return $this->hasMany(CandidateResult::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(ResultEvidence::class);
    }
}

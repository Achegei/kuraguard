<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PollingStation extends Model
{
    protected $fillable = [
        'election_id',
        'county',
        'constituency',
        'ward',
        'name',
        'station_code',
        'registered_voters',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'registered_voters' => 'integer',
        ];
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AgentAssignment::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ResultSubmission::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Election extends Model
{
    protected $fillable = [
        'name',
        'office',
        'election_date',
        'status',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'election_date' => 'date',
            'is_demo' => 'boolean',
        ];
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function pollingStations(): HasMany
    {
        return $this->hasMany(PollingStation::class);
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }
}

<?php

namespace App\Enums;

enum ElectoralOffice: string
{
    case PRESIDENT = 'president';
    case GOVERNOR = 'governor';
    case SENATOR = 'senator';
    case WOMAN_REPRESENTATIVE = 'woman_representative';
    case MP = 'mp';
    case MCA = 'mca';

    public function label(): string
    {
        return match ($this) {
            self::PRESIDENT => 'President',
            self::GOVERNOR => 'Governor',
            self::SENATOR => 'Senator',
            self::WOMAN_REPRESENTATIVE => 'Woman Representative',
            self::MP => 'Member of Parliament',
            self::MCA => 'Member of County Assembly',
        };
    }

    public function scopeLevel(): string
    {
        return match ($this) {
            self::PRESIDENT => 'national',

            self::GOVERNOR,
            self::SENATOR,
            self::WOMAN_REPRESENTATIVE => 'county',

            self::MP => 'constituency',

            self::MCA => 'ward',
        };
    }

    public function navigationLevels(): array
    {
        return match ($this->scopeLevel()) {
            'national' => [
                'national',
                'county',
                'constituency',
                'ward',
                'polling_centre',
                'polling_station',
            ],

            'county' => [
                'county',
                'constituency',
                'ward',
                'polling_centre',
                'polling_station',
            ],

            'constituency' => [
                'constituency',
                'ward',
                'polling_centre',
                'polling_station',
            ],

            'ward' => [
                'ward',
                'polling_centre',
                'polling_station',
            ],
        };
    }

    public function requiresGeographicSelection(): bool
    {
        return $this !== self::PRESIDENT;
    }
}

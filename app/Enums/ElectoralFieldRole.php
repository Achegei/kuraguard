<?php

namespace App\Enums;

enum ElectoralFieldRole: string
{
    case NATIONAL_COORDINATOR = 'national_coordinator';
    case COUNTY_COORDINATOR = 'county_coordinator';
    case CONSTITUENCY_COORDINATOR = 'constituency_coordinator';
    case WARD_COORDINATOR = 'ward_coordinator';
    case POLLING_CENTRE_COORDINATOR = 'polling_centre_coordinator';
    case POLLING_STATION_AGENT = 'polling_station_agent';
    case MOBILIZER = 'mobilizer';

    public function geographicLevel(): string
    {
        return match ($this) {
            self::NATIONAL_COORDINATOR => 'national',
            self::COUNTY_COORDINATOR => 'county',
            self::CONSTITUENCY_COORDINATOR => 'constituency',
            self::WARD_COORDINATOR => 'ward',
            self::POLLING_CENTRE_COORDINATOR => 'polling_centre',
            self::POLLING_STATION_AGENT => 'polling_station',
            self::MOBILIZER => 'variable',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::NATIONAL_COORDINATOR => 'National Coordinator',
            self::COUNTY_COORDINATOR => 'County Coordinator',
            self::CONSTITUENCY_COORDINATOR => 'Constituency Coordinator',
            self::WARD_COORDINATOR => 'Ward Coordinator',
            self::POLLING_CENTRE_COORDINATOR => 'Polling Centre Coordinator',
            self::POLLING_STATION_AGENT => 'Polling Station Agent',
            self::MOBILIZER => 'Campaign Mobilizer',
        };
    }
}

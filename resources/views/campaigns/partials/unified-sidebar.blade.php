<aside class="kg-unified-sidebar">
    <div class="brand">Kura<span>Guard</span></div>
    <div class="caption">ELECTION INTELLIGENCE</div>

    <div class="nav-label">CAMPAIGN WORKSPACE</div>

    <a class="kg-nav-link {{ request()->routeIs('campaign.dashboard') ? 'active' : '' }}"
       href="{{ route('campaign.dashboard', ['contestId' => $contest->id]) }}">
        Campaign Overview
    </a>

    <a class="kg-nav-link {{ request()->routeIs('campaign.operations.monitor') ? 'active' : '' }}"
       href="{{ route('campaign.operations.monitor', ['contestId' => $contest->id]) }}">
        Election Operations
    </a>

    <a class="kg-nav-link {{ request()->routeIs('campaign.reports.tally') ? 'active' : '' }}"
       href="{{ route('campaign.reports.tally', ['contestId' => $contest->id]) }}">
        Result Verification
    </a>

    <a class="kg-nav-link {{ request()->routeIs('campaign.reports.audit') ? 'active' : '' }}"
       href="{{ route('campaign.reports.audit', ['contestId' => $contest->id]) }}">
        Application Audit History
    </a>


    @can('campaign.assign-agents', $contest->id)
        <a class="kg-nav-link {{ request()->routeIs('campaign.recruitment.*') ? 'active' : '' }}"
           href="{{ route('campaign.recruitment.index', ['contestId' => $contest->id]) }}">
            Recruitment Management
        </a>
    @endcan

    <a class="kg-nav-link {{ request()->routeIs('campaign.scenarios.*') ? 'active' : '' }}"
   href="{{ route('campaign.scenarios.index', ['contestId' => $contest->id]) }}">
    Scenario Builder
</a>

<div class="nav-label">ELECTION NETWORK</div>

    <a class="kg-nav-link {{ request()->routeIs('campaign.network.stations') ? 'active' : '' }}"
       href="{{ route('campaign.network.stations', ['contestId' => $contest->id]) }}">
        Polling Stations
    </a>

    @can('campaign.assign-agents', $contest->id)
        <a class="kg-nav-link {{ request()->routeIs('campaign.network.agents') ? 'active' : '' }}"
           href="{{ route('campaign.network.agents', ['contestId' => $contest->id]) }}">
            Agent Network
        </a>
    @endcan

    <div class="nav-label">LEGACY DEMONSTRATION</div>

    <a class="kg-nav-link" href="{{ route('command-centre.index') }}">
        Simulated Election Overview
    </a>

    <div class="nav-label">ACCOUNT</div>

    {{-- Consent settings are restricted to the owner of this specific contest.
         Campaign membership alone does not authorize publication consent. --}}
    @if (auth()->check() && \Illuminate\Support\Facades\DB::table('aspirant_contests as discovery_contests')
        ->join('aspirants as discovery_aspirants', 'discovery_aspirants.id', '=', 'discovery_contests.aspirant_id')
        ->where('discovery_contests.id', $contest->id)
        ->where('discovery_aspirants.user_id', auth()->id())
        ->exists())
        <a class="kg-nav-link {{ request()->routeIs('campaign.discovery-settings.*') ? 'active' : '' }}"
           href="{{ route('campaign.discovery-settings.show', ['contestId' => $contest->id]) }}">
            Aspirant Discovery &amp; Privacy
        </a>
    @endif

    <a class="kg-nav-link" href="{{ route('campaign.landing') }}">
        Switch Campaign
    </a>

    <a class="kg-nav-link" href="{{ url('/') }}">
        Public Website
    </a>

    <form method="POST" action="{{ route('logout') }}" class="kg-logout-form">
        @csrf
        <button type="submit" class="kg-nav-link kg-logout-button">
            Sign Out
        </button>
    </form>

    <div class="kg-sidebar-footer">
        <span>ACTIVE CAMPAIGN</span>
        <strong>{{ $contest->display_name }}</strong>
        <small>{{ $contest->election_year }} · {{ str_replace('_', ' ', $contest->office) }}</small>
    </div>
</aside>

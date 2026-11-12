@extends('layouts.campaign')

@section('title', 'Aspirant Discovery Settings | KuraGuard')
@section('breadcrumb', 'Aspirant Discovery Settings')

@section('content')
<div style="max-width: 760px; margin: 0 auto;">
    <h1>Aspirant discovery &amp; privacy</h1>
    <p>Choose whether other KuraGuard campaigns contesting the same electoral seat can find your aspirant name in their hypothetical Scenario Builder.</p>
    <div style="background: #fff; border: 1px solid #dce5e8; border-radius: 12px; padding: 24px; margin: 22px 0;">
        <h2 style="margin-top: 0;">{{ $contest->display_name }}</h2>
        <p>{{ str_replace('_', ' ', $contest->office) }} &middot; {{ $contest->election_year }}</p>
        <p><strong>Current status:</strong> {{ $contest->scenario_discovery_consent ? 'Discoverable' : 'Private' }}</p>
        <p>Opting in publishes only your aspirant display name and declared electoral contest to other authenticated same-seat campaign users. It does not publish your email, phone number, campaign team, agents, or internal records. KuraGuard registration does not mean official IEBC clearance.</p>
        <p>Consent is optional and defaults to private. You may withdraw it at any time. Withdrawal prevents inclusion in future scenario page loads, but cannot erase information already viewed or copied by others.</p>
        @if(session('status'))
            <p role="status" style="color: #087b54; font-weight: 700;">{{ session('status') }}</p>
        @endif
        <form method="POST" action="{{ route('campaign.discovery-settings.update', ['contestId' => $contest->id]) }}">
            @csrf
            <input type="hidden" name="scenario_discovery_consent" value="{{ $contest->scenario_discovery_consent ? '0' : '1' }}">
            <button type="submit" style="padding: 12px 18px; border: 0; border-radius: 8px; color: #fff; background: {{ $contest->scenario_discovery_consent ? '#8b2535' : '#087b54' }}; cursor: pointer; font-weight: 700;">
                {{ $contest->scenario_discovery_consent ? 'Withdraw publication consent' : 'I agree — make my aspirant identity discoverable' }}
            </button>
        </form>
    </div>
    <a href="{{ route('campaign.dashboard', ['contestId' => $contest->id]) }}">Back to campaign dashboard</a>
</div>
@endsection

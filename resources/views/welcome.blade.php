@extends('layouts.public')

@section('title', 'KuraGuard | Election Intelligence & Command Platform')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/kuraguard-home.css') }}">
@endpush

@section('content')
<div class="kg-home">

    <section class="kg-home-hero">
        <div class="kg-home-container kg-hero-grid">

            <div class="kg-hero-copy">
                <div class="kg-home-eyebrow">
                    <span class="kg-status-dot"></span>
                    ELECTION INTELLIGENCE & COMMAND PLATFORM
                </div>

                <h1>
                    Every agent.<br>
                    Every station.<br>
                    <span>Every vote.</span><br>
                    Under your watch.
                </h1>

                <p>
                    KuraGuard brings campaign teams, polling-station
                    operations, incident monitoring and result
                    verification into one coordinated command centre.
                </p>

                <div class="kg-home-actions">
                    @auth
                        <a class="kg-home-btn kg-home-btn-primary"
                           href="{{ route('campaign.landing') }}">
                            Open My Workspace
                            <span aria-hidden="true">→</span>
                        </a>
                    @else
                        <a class="kg-home-btn kg-home-btn-primary"
                           href="{{ route('login') }}">
                            Access KuraGuard
                            <span aria-hidden="true">→</span>
                        </a>
                    @endauth

                    <a class="kg-home-btn kg-home-btn-outline"
                       href="#how-it-works">
                        See How It Works
                    </a>
                </div>

                <div class="kg-hero-trust">
                    <span>Built for campaign operations</span>
                    <span>Role-based access</span>
                    <span>Traceable reporting</span>
                </div>
            </div>

            <div class="kg-preview-frame">
                <div class="kg-preview-topbar">
                    <div class="kg-preview-brand">
                        <span class="kg-preview-icon">K</span>
                        <div>
                            <strong>KuraGuard</strong>
                            <small>Election Command Centre</small>
                        </div>
                    </div>
                    <span class="kg-preview-live">
                        <span></span> DEMO PREVIEW
                    </span>
                </div>

                <div class="kg-preview-body">
                    <div class="kg-preview-heading">
                        <div>
                            <small>CAMPAIGN INTELLIGENCE</small>
                            <h3>Election Overview</h3>
                        </div>
                        <span>2027 Simulation</span>
                    </div>

                    <div class="kg-preview-stats">
                        <div>
                            <small>Polling Stations</small>
                            <strong>1,248</strong>
                            <span>Illustrative coverage</span>
                        </div>
                        <div>
                            <small>Agents Deployed</small>
                            <strong>1,186</strong>
                            <span>Sample deployment</span>
                        </div>
                        <div>
                            <small>Reporting Rate</small>
                            <strong>95.0%</strong>
                            <span>Example metric</span>
                        </div>
                    </div>

                    <div class="kg-preview-chart">
                        <div class="kg-preview-chart-header">
                            <strong>Station Reporting Activity</strong>
                            <span>Illustrative trend</span>
                        </div>

                        <svg viewBox="0 0 500 160"
                             preserveAspectRatio="none"
                             role="img"
                             aria-label="Illustrative upward reporting trend">
                            <defs>
                                <linearGradient id="kgChartFill"
                                                x1="0" y1="0"
                                                x2="0" y2="1">
                                    <stop offset="0%"
                                          stop-color="#39d6ad"
                                          stop-opacity=".26"/>
                                    <stop offset="100%"
                                          stop-color="#39d6ad"
                                          stop-opacity="0"/>
                                </linearGradient>
                            </defs>
                            <path d="M0 145 L60 130 L120 138 L180 95
                                     L240 108 L300 66 L360 80
                                     L420 36 L500 16 L500 160
                                     L0 160 Z"
                                  fill="url(#kgChartFill)"/>
                            <path d="M0 145 L60 130 L120 138 L180 95
                                     L240 108 L300 66 L360 80
                                     L420 36 L500 16"
                                  fill="none"
                                  stroke="#39d6ad"
                                  stroke-width="4"
                                  stroke-linecap="round"
                                  stroke-linejoin="round"/>
                        </svg>
                    </div>

                    <div class="kg-preview-events">
                        <div>
                            <span class="kg-event-dot green"></span>
                            Station check-in received
                            <small>Sample</small>
                        </div>
                        <div>
                            <span class="kg-event-dot amber"></span>
                            Result awaiting verification
                            <small>Sample</small>
                        </div>
                        <div>
                            <span class="kg-event-dot blue"></span>
                            Coordinator update recorded
                            <small>Sample</small>
                        </div>
                    </div>
                </div>

                <div class="kg-preview-disclaimer">
                    Product illustration only. All displayed figures
                    are fictional demonstration values.
                </div>
            </div>
        </div>
    </section>

    <section class="kg-home-strip">
        <div class="kg-home-container kg-strip-grid">
            <span>ONE CAMPAIGN WORKSPACE</span>
            <span>FIELD AGENT COORDINATION</span>
            <span>STATION-LEVEL VISIBILITY</span>
            <span>RESULT INTEGRITY</span>
        </div>
    </section>

    <section id="platform" class="kg-home-section">
        <div class="kg-home-container">
            <div class="kg-section-intro">
                <span class="kg-section-label">THE PLATFORM</span>
                <h2>
                    Election operations deserve
                    <span>complete visibility.</span>
                </h2>
                <p>
                    Replace disconnected calls, spreadsheets and
                    scattered updates with a coordinated operational
                    workflow for your campaign team.
                </p>
            </div>

            <div class="kg-feature-grid">
                <article class="kg-feature-card">
                    <span class="kg-feature-number">01 / NETWORK</span>
                    <div class="kg-feature-symbol">◎</div>
                    <h3>Polling Station Coverage</h3>
                    <p>
                        Organize your campaign's station network and
                        track where field reports have been received.
                    </p>
                </article>

                <article class="kg-feature-card">
                    <span class="kg-feature-number">02 / PEOPLE</span>
                    <div class="kg-feature-symbol">♧</div>
                    <h3>Agent Recruitment & Deployment</h3>
                    <p>
                        Coordinate applications, authorized assignments
                        and field-agent participation.
                    </p>
                </article>

                <article class="kg-feature-card">
                    <span class="kg-feature-number">03 / OPERATIONS</span>
                    <div class="kg-feature-symbol">◈</div>
                    <h3>Election-Day Monitoring</h3>
                    <p>
                        Receive station reports, monitor attendance
                        and track operational incidents.
                    </p>
                </article>

                <article class="kg-feature-card">
                    <span class="kg-feature-number">04 / INTEGRITY</span>
                    <div class="kg-feature-symbol">◇</div>
                    <h3>Results & Verification</h3>
                    <p>
                        Review submitted figures and maintain
                        traceable verification and audit records.
                    </p>
                </article>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="kg-workflow-section">
        <div class="kg-home-container">
            <div class="kg-section-intro">
                <span class="kg-section-label">HOW IT WORKS</span>
                <h2>
                    From recruitment to results.
                    <span>One connected journey.</span>
                </h2>
            </div>

            <div class="kg-workflow-grid">
                <div class="kg-workflow-step">
                    <span>01</span>
                    <h3>Build your network</h3>
                    <p>Recruit and organize authorized field personnel.</p>
                </div>

                <div class="kg-workflow-step">
                    <span>02</span>
                    <h3>Deploy your agents</h3>
                    <p>Coordinate assignments across campaign stations.</p>
                </div>

                <div class="kg-workflow-step">
                    <span>03</span>
                    <h3>Monitor election day</h3>
                    <p>Track attendance, reports and field incidents.</p>
                </div>

                <div class="kg-workflow-step">
                    <span>04</span>
                    <h3>Verify the outcome</h3>
                    <p>Review submissions and preserve audit history.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="kg-audience-section">
        <div class="kg-home-container kg-audience-grid">
            <div>
                <span class="kg-section-label">BUILT FOR CAMPAIGNS</span>
                <h2>
                    Your election network.
                    <span>Your command centre.</span>
                </h2>
            </div>

            <div class="kg-audience-copy">
                <p>
                    Whether you are coordinating a parliamentary,
                    county or ward-level campaign, KuraGuard is designed
                    to give authorized teams structured visibility
                    into field operations and reporting.
                </p>

                <div class="kg-audience-tags">
                    <span>Governor</span>
                    <span>Senator</span>
                    <span>Member of Parliament</span>
                    <span>Member of County Assembly</span>
                </div>
            </div>
        </div>
    </section>

    <section class="kg-home-cta">
        <div class="kg-home-container kg-cta-inner">
            <div>
                <span class="kg-section-label">TAKE COMMAND</span>
                <h2>Make every polling station count.</h2>
                <p>
                    Bring your campaign's field operations into
                    one coordinated election command centre.
                </p>
            </div>

            @auth
                <a href="{{ route('campaign.landing') }}"
                   class="kg-home-btn kg-home-btn-primary">
                    Enter Workspace →
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="kg-home-btn kg-home-btn-primary">
                    Access Platform →
                </a>
            @endauth
        </div>
    </section>

</div>
@endsection

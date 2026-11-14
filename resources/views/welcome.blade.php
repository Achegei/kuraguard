@extends('layouts.public')
@section('title', 'KuraGuard | Every Polling Station. Every Vote. Accounted For.')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/kuraguard-home.css') }}?v=20261113h">
@endpush
@section('content')
<div class="kg-home-v2">
  <section class="kg-photo-hero" aria-label="KuraGuard election operations">
    <div class="kg-hero-slides" data-kg-slideshow>
      <div class="kg-hero-slide is-active" data-kg-slide style="--hero-photo:url('{{ asset('images/home/voters.jpg') }}')" role="img" aria-label="Voters queue outside a polling station"></div>
      <div class="kg-hero-slide" data-kg-slide style="--hero-photo:url('{{ asset('images/home/aspirant.jpg') }}')" role="img" aria-label="Aspirant speaking from a podium"></div>
      <div class="kg-hero-slide" data-kg-slide style="--hero-photo:url('{{ asset('images/home/agents.jpg') }}')" role="img" aria-label="Election agents coordinating field operations"></div>
    </div>
    <div class="kg-hero-shade"></div>
    <div class="kg-home-wrap kg-hero-content">
      <div class="kg-hero-eyebrow"><span class="kg-live-dot"></span> BUILT FOR KENYAN CAMPAIGNS</div>
      <h1>Every Polling Station.<br><em>Every Vote.</em><br>Accounted For.</h1>
      <p>One powerful workspace to coordinate your people, protect your reporting process and see what is happening across your campaign.</p>
      <div class="kg-hero-buttons">
        @auth
          <a class="kg-btn kg-btn-primary" href="{{ route('campaign.landing') }}">Open My Workspace <span aria-hidden="true">↗</span></a>
        @else
          <a class="kg-btn kg-btn-primary" href="{{ route('register') }}">Register as an Aspirant <span aria-hidden="true">↗</span></a>
        @endauth
        <a class="kg-btn kg-btn-glass" href="#discover">Explore the Platform <span aria-hidden="true">↓</span></a>
      </div>
      <div class="kg-hero-bottom"><span>CAMPAIGN INTELLIGENCE · FIELD OPERATIONS · RESULT VERIFICATION</span>
        <div class="kg-slide-controls" aria-label="Hero slideshow controls">
          <button type="button" data-kg-prev aria-label="Previous slide">←</button>
          <span data-kg-counter>01 / 03</span>
          <button type="button" data-kg-next aria-label="Next slide">→</button>
          <button type="button" data-kg-pause aria-label="Pause slideshow">Ⅱ</button>
        </div>
      </div>
    </div>
  </section>

  <section class="kg-editorial" id="discover">
    <div class="kg-home-wrap">
      <div class="kg-section-head"><div><span class="kg-kicker">A NEW STANDARD FOR CAMPAIGN OPERATIONS</span><h2>Confidence begins<br>with <em>visibility.</em></h2></div><p>From the first field assignment to the final station report, bring your entire election operation together in one clear view.</p></div>
      <div class="kg-editorial-grid">
        <article class="kg-editorial-card kg-editorial-large"><div class="kg-photo" style="--card-photo:url('{{ asset('images/home/voters.jpg') }}')"></div><div class="kg-card-shade"></div><div class="kg-editorial-copy"><span>01 / THE PEOPLE</span><h3>Every community.<br>Every polling station.</h3><p>Plan and organize your campaign network with station-level clarity.</p></div></article>
        <article class="kg-editorial-card"><div class="kg-photo" style="--card-photo:url('{{ asset('images/home/aspirant.jpg') }}')"></div><div class="kg-card-shade"></div><div class="kg-editorial-copy"><span>02 / THE CAMPAIGN</span><h3>Lead from the front.</h3><p>Give your team a coordinated command centre.</p></div></article>
        <article class="kg-editorial-card"><div class="kg-photo" style="--card-photo:url('{{ asset('images/home/agents.jpg') }}')"></div><div class="kg-card-shade"></div><div class="kg-editorial-copy"><span>03 / THE FIELD</span><h3>Stay connected on the ground.</h3><p>Follow assignments, reports and incidents as they develop.</p></div></article>
      </div>
    </div>
  </section>


  <section class="kg-seat-section" id="races">
    <div class="kg-home-wrap">
      <div class="kg-seat-top">
        <div>
          <span class="kg-kicker">ONE PLATFORM. EVERY LEVEL OF LEADERSHIP.</span>
          <h2>Built around <em>your race.</em></h2>
          <p>From a single ward to a nationwide campaign, organize your election operations around the seat you're contesting.</p>
        </div>
        <a class="kg-seat-cta" href="{{ route('register') }}">
          Start your campaign <span aria-hidden="true">↗</span>
        </a>
      </div>

      <div class="kg-seat-cards">
        <a class="kg-seat-card" href="{{ route('register') }}">
          <span class="kg-seat-symbol">01</span>
          <strong>President</strong>
          <small>National campaign</small>
          <span class="kg-seat-arrow" aria-hidden="true">↗</span>
        </a>
        <a class="kg-seat-card" href="{{ route('register') }}">
          <span class="kg-seat-symbol">02</span>
          <strong>Governor</strong>
          <small>Countywide operations</small>
          <span class="kg-seat-arrow" aria-hidden="true">↗</span>
        </a>
        <a class="kg-seat-card" href="{{ route('register') }}">
          <span class="kg-seat-symbol">03</span>
          <strong>Senator</strong>
          <small>County representation</small>
          <span class="kg-seat-arrow" aria-hidden="true">↗</span>
        </a>
        <a class="kg-seat-card" href="{{ route('register') }}">
          <span class="kg-seat-symbol">04</span>
          <strong>Woman Representative</strong>
          <small>County representation</small>
          <span class="kg-seat-arrow" aria-hidden="true">↗</span>
        </a>
        <a class="kg-seat-card" href="{{ route('register') }}">
          <span class="kg-seat-symbol">05</span>
          <strong>Member of Parliament</strong>
          <small>Constituency campaign</small>
          <span class="kg-seat-arrow" aria-hidden="true">↗</span>
        </a>
        <a class="kg-seat-card" href="{{ route('register') }}">
          <span class="kg-seat-symbol">06</span>
          <strong>Member of County Assembly</strong>
          <small>Ward-level campaign</small>
          <span class="kg-seat-arrow" aria-hidden="true">↗</span>
        </a>
      </div>
    </div>
  </section>

  <section class="kg-capabilities" id="platform">
    <div class="kg-home-wrap">
      <div class="kg-command-intro">
        <div>
          <span class="kg-kicker">THE KURAGUARD PLATFORM</span>
          <h2>One command centre.<br><em>Complete oversight.</em></h2>
        </div>
        <p>Replace disconnected spreadsheets and fragmented updates with one structured workspace for your authorized campaign team.</p>
      </div>

      <div class="kg-command-grid">
        <article class="kg-command-card">
          <div class="kg-command-card-top">
            <span class="kg-command-number">01 / COVERAGE</span>
            <span class="kg-command-icon" aria-hidden="true">◎</span>
          </div>
          <div>
            <h3>Polling Station Network</h3>
            <p>Organize geographic coverage and identify where station-level reporting needs attention.</p>
          </div>
        </article>

        <article class="kg-command-card">
          <div class="kg-command-card-top">
            <span class="kg-command-number">02 / PEOPLE</span>
            <span class="kg-command-icon" aria-hidden="true">♧</span>
          </div>
          <div>
            <h3>Agent Coordination</h3>
            <p>Manage recruitment, approvals and assignments across your campaign network.</p>
          </div>
        </article>

        <article class="kg-command-card">
          <div class="kg-command-card-top">
            <span class="kg-command-number">03 / OPERATIONS</span>
            <span class="kg-command-icon" aria-hidden="true">◈</span>
          </div>
          <div>
            <h3>Election-Day Monitoring</h3>
            <p>Follow check-ins, field reports and operational incidents in one place.</p>
          </div>
        </article>

        <article class="kg-command-card">
          <div class="kg-command-card-top">
            <span class="kg-command-number">04 / INTEGRITY</span>
            <span class="kg-command-icon" aria-hidden="true">◇</span>
          </div>
          <div>
            <h3>Results &amp; Verification</h3>
            <p>Review submissions and maintain traceable verification and audit records.</p>
          </div>
        </article>
      </div>
    </div>
  </section>


<section class="kg-journey" id="how-it-works">
  <div class="kg-home-wrap">
    <div class="kg-journey-intro">
      <div>
        <span class="kg-kicker">HOW IT WORKS</span>
        <h2>From setup to<br><em>election day.</em></h2>
      </div>
      <p>Four clear steps to organize your campaign, coordinate field operations and review station reports.</p>
    </div>

    <div class="kg-journey-steps">
      <article class="kg-journey-step">
        <div class="kg-step-top"><span class="kg-step-icon">01</span><span class="kg-step-line"></span></div>
        <h3>Set up your campaign</h3>
        <p>Register and establish your authorized campaign workspace.</p>
      </article>

      <article class="kg-journey-step">
        <div class="kg-step-top"><span class="kg-step-icon">02</span><span class="kg-step-line"></span></div>
        <h3>Build your network</h3>
        <p>Recruit and assign agents across polling stations.</p>
      </article>

      <article class="kg-journey-step">
        <div class="kg-step-top"><span class="kg-step-icon">03</span><span class="kg-step-line"></span></div>
        <h3>Coordinate election day</h3>
        <p>Monitor field reports and operational incidents.</p>
      </article>

      <article class="kg-journey-step">
        <div class="kg-step-top"><span class="kg-step-icon">04</span><span class="kg-step-line"></span></div>
        <h3>Review every report</h3>
        <p>Verify submissions and preserve an audit trail.</p>
      </article>
    </div>
  </div>
</section>

<section class="kg-final-cta">
  <div class="kg-home-wrap">
    <div class="kg-final-panel">
      <div class="kg-final-message">
        <span class="kg-kicker">YOUR CAMPAIGN. YOUR COMMAND.</span>
        <h2>Every polling station<br><em>matters.</em></h2>
        <p>Give your campaign team the tools to organize, monitor and report with confidence.</p>
      </div>

      <div class="kg-final-actions">
        @auth
          <a class="kg-btn kg-btn-primary" href="{{ route('campaign.landing') }}">Enter Your Workspace ↗</a>
        @else
          <a class="kg-btn kg-btn-primary" href="{{ route('register') }}">Register as an Aspirant ↗</a>
          <a class="kg-btn kg-btn-outline" href="{{ route('login') }}">Sign In →</a>
        @endauth
        <small>Built for independent campaign operations.</small>
      </div>
    </div>
  </div>
</section>

</div>
@push('scripts')<script src="{{ asset('js/kuraguard-home.js') }}?v=20261113b" defer></script>@endpush
@endsection

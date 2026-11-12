<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// KuraGuard local demonstration dashboard.
// Do not expose publicly without authentication.
\Route::get('/command-centre', [
    \App\Http\Controllers\CommandCentreController::class,
    'index'
])->middleware('auth')->name('command-centre.index');


// KuraGuard authentication routes.
\Route::middleware('guest')->group(function () {
    \Route::get('/login', [
        \App\Http\Controllers\AuthController::class,
        'showLogin'
    ])->name('login');

    \Route::post('/login', [
        \App\Http\Controllers\AuthController::class,
        'login'
    ])->name('login.submit');
});

\Route::post('/logout', [
    \App\Http\Controllers\AuthController::class,
    'logout'
])->middleware('auth')->name('logout');


// Authenticated review of simulated election submissions.
\Route::get('/verification/{submission}', [
    \App\Http\Controllers\ResultVerificationController::class,
    'show'
])->middleware('auth')->name('verification.show');


// Result verification decisions (authenticated; reviewer authorization in controller).
\Route::post('/verification/{submission}/approve', [
    \App\Http\Controllers\ResultVerificationController::class,
    'approve'
])->middleware('auth')->name('verification.approve');

\Route::post('/verification/{submission}/reject', [
    \App\Http\Controllers\ResultVerificationController::class,
    'reject'
])->middleware('auth')->name('verification.reject');


// Upload simulated polling-station result evidence.
\Route::post('/verification/{submission}/evidence', [
    \App\Http\Controllers\ResultEvidenceController::class,
    'store'
])->middleware('auth')->name('verification.evidence.store');


// Authenticated Agent Network directory.
\Route::get('/agents', [
    \App\Http\Controllers\AgentNetworkController::class,
    'index'
])->middleware('auth')->name('agents.index');


// Demo administrator: register an election agent.
\Route::post('/agents', [
    \App\Http\Controllers\AgentNetworkController::class,
    'store'
])->middleware('auth')->name('agents.store');


// Demo administrator: assign an existing agent to a polling station.
\Route::post('/agents/assign', [
    \App\Http\Controllers\AgentNetworkController::class,
    'assign'
])->middleware('auth')->name('agents.assign');


// Demo administrator: register a simulated polling station.
\Route::post('/polling-stations', [
    \App\Http\Controllers\PollingStationController::class,
    'store'
])->middleware('auth')->name('polling-stations.store');


// Authenticated polling station monitoring dashboard.
\Route::get('/polling-stations', [
    \App\Http\Controllers\PollingStationNetworkController::class,
    'index'
])->middleware('auth')->name('polling-stations.index');

// AI-assisted extraction of simulated polling-station results.
// Restricted further by ResultOcrController to the demo administrator.
\Route::post('/verification/{submission}/ocr', [
    \App\Http\Controllers\ResultOcrController::class,
    'extract'
])->middleware(['auth', 'throttle:5,1'])->name('verification.ocr.extract');


// Public Electoral Explorer interface.
// Historical IEBC 2022 reference data.
\Route::get('/explorer', function () {
    return view('explorer.index');
})->name('explorer.index');


// Public IEBC historical Electoral Explorer.
// Read-only endpoints; no authentication required.
\Route::middleware('throttle:60,1')
    ->prefix('explorer/api')
    ->name('explorer.api.')
    ->group(function () {
        \Route::get('/offices', [
            \App\Http\Controllers\ElectoralExplorerApiController::class,
            'offices',
        ])->name('offices');

        \Route::get('/counties', [
            \App\Http\Controllers\ElectoralExplorerApiController::class,
            'counties',
        ])->name('counties');

        \Route::get('/constituencies', [
            \App\Http\Controllers\ElectoralExplorerApiController::class,
            'constituencies',
        ])->name('constituencies');

        \Route::get('/wards', [
            \App\Http\Controllers\ElectoralExplorerApiController::class,
            'wards',
        ])->name('wards');

        \Route::get('/summary', [
            \App\Http\Controllers\ElectoralScopeSummaryController::class,
            'show',
        ])->name('summary');

        \Route::get('/polling-centres', [
            \App\Http\Controllers\ElectoralDrilldownController::class,
            'pollingCentres',
        ])->name('polling-centres');

        \Route::get('/polling-stations', [
            \App\Http\Controllers\ElectoralDrilldownController::class,
            'pollingStations',
        ])->name('polling-stations');
    });


// Aspirant campaign dashboard.
// Authentication and campaign-specific viewing authorization required.
\Route::get('/campaigns/{contestId}/dashboard', [
    \App\Http\Controllers\CampaignDashboardController::class,
    'index',
])
    ->middleware([
        'auth',
        'can:campaign.view,contestId',
    ])
    ->whereNumber('contestId')
    ->name('campaign.dashboard');


// Aspirant campaign field assignment.
// Assignment requires campaign-specific management authorization.
\Route::middleware([
    'auth',
    'can:campaign.assign-agents,contestId',
])
    ->prefix('campaigns/{contestId}/applications')
    ->whereNumber('contestId')
    ->name('campaign.applications.')
    ->group(function () {
        \Route::post('/{applicationId}/assign', [
            \App\Http\Controllers\FieldAssignmentController::class,
            'store',
        ])
            ->whereNumber('applicationId')
            ->name('assign');
    });


// Aspirant campaign application review.
// Authentication and campaign-specific authorization are required.
\Route::middleware([
    'auth',
    'can:campaign.review-applications,contestId',
])
    ->prefix('campaigns/{contestId}/applications')
    ->whereNumber('contestId')
    ->name('campaign.applications.')
    ->group(function () {
        // Campaign-scoped application review audit history.
        \Route::get('/audit-history', [
            \App\Http\Controllers\RoleApplicationAuditController::class,
            'index',
        ])->name('audit-history');

        \Route::post('/{applicationId}/approve', [
            \App\Http\Controllers\RoleApplicationReviewController::class,
            'approve',
        ])
            ->whereNumber('applicationId')
            ->name('approve');

        \Route::post('/{applicationId}/reject', [
            \App\Http\Controllers\RoleApplicationReviewController::class,
            'reject',
        ])
            ->whereNumber('applicationId')
            ->name('reject');
    });


// Aspirant campaign field assignment termination.
// Authentication and campaign-specific management authorization required.
\Route::middleware([
    'auth',
    'can:campaign.assign-agents,contestId',
])
    ->prefix('campaigns/{contestId}/assignments')
    ->whereNumber('contestId')
    ->name('campaign.assignments.')
    ->group(function () {
        \Route::post('/{assignmentId}/end', [
            \App\Http\Controllers\FieldAssignmentController::class,
            'end',
        ])
            ->whereNumber('assignmentId')
            ->name('end');
    });

// Authenticated mobilizer workspace.
// Authorization is enforced by MobilizerWorkspaceController.
Route::get(
    '/mobilizer/workspace',
    [\App\Http\Controllers\MobilizerWorkspaceController::class, 'index']
)
    ->middleware('auth')
    ->name('mobilizer.workspace');

// Authorized campaign landing and selection.
Route::get(
    '/campaigns',
    [\App\Http\Controllers\CampaignLandingController::class, 'index']
)
    ->middleware('auth')
    ->name('campaign.landing');


// Aspirant campaign onboarding.
// Authentication required for both routes.
// The POST endpoint is rate-limited and uses Laravel CSRF protection.
\Route::middleware('auth')
    ->prefix('aspirants')
    ->name('aspirants.')
    ->group(function () {
        \Route::get('/register', [
            \App\Http\Controllers\AspirantOnboardingController::class,
            'create',
        ])->name('register');

        \Route::post('/register', [
            \App\Http\Controllers\AspirantOnboardingController::class,
            'store',
        ])
            ->middleware('throttle:5,1')
            ->name('store');
    });

// KuraGuard campaign-scoped field operations.


// KuraGuard campaign-scoped field operations.
// All routes require authentication and use campaign-specific authorization.
Route::middleware('auth')
    ->prefix('campaigns/{contestId}/operations')
    ->whereNumber('contestId')
    ->name('campaign.operations.')
    ->group(function () {
        Route::get('/', [
            \App\Http\Controllers\CampaignOperationsController::class,
            'overview',
        ])->name('overview');

        Route::post('/station-reports', [
            \App\Http\Controllers\CampaignOperationsController::class,
            'stationReport',
        ])->middleware('throttle:30,1')->name('station-reports.store');

        Route::post('/incidents', [
            \App\Http\Controllers\CampaignOperationsController::class,
            'incident',
        ])->middleware('throttle:20,1')->name('incidents.store');

        Route::post('/incidents/{incidentId}/transition', [
            \App\Http\Controllers\CampaignIncidentTransitionController::class,
            '__invoke',
        ])
            ->whereNumber('incidentId')
            ->middleware([
                'can:campaign.manage-incidents,contestId',
                'throttle:20,1',
            ])
            ->name('incidents.transition');
    });


// Campaign-specific election-day monitoring interface.
// Existing operations JSON endpoints remain unchanged.
Route::get('/campaigns/{contestId}/operations/monitor', [
    \App\Http\Controllers\CampaignOperationsMonitorController::class,
    'index',
])
    ->middleware([
        'auth',
        'can:campaign.view,contestId',
    ])
    ->whereNumber('contestId')
    ->name('campaign.operations.monitor');

// Browser-based campaign result review.
// Existing JSON review endpoint remains unchanged.
Route::post('/campaigns/{contestId}/results/{submissionId}/web-review', [
    \App\Http\Controllers\CampaignResultWebReviewController::class,
    '__invoke',
])
    ->middleware([
        'auth',
        'can:campaign.review-applications,contestId',
        'throttle:20,1',
    ])
    ->whereNumber('contestId')
    ->whereNumber('submissionId')
    ->name('campaign.results.web-review');

// KuraGuard campaign result workflow.
Route::middleware('auth')
    ->prefix('campaigns/{contestId}/results')
    ->whereNumber('contestId')
    ->name('campaign.results.')
    ->group(function () {
        Route::post('/', [
            \App\Http\Controllers\CampaignResultController::class,
            'submit',
        ])->middleware('throttle:20,1')->name('submit');

        Route::post('/{submissionId}/evidence', [
            \App\Http\Controllers\CampaignResultController::class,
            'evidence',
        ])->whereNumber('submissionId')
          ->middleware('throttle:20,1')->name('evidence');


        Route::get('/{submissionId}/evidence/{evidenceId}/download', [
            \App\Http\Controllers\CampaignResultController::class,
            'downloadEvidence',
        ])
            ->whereNumber('submissionId')
            ->whereNumber('evidenceId')
            ->middleware('throttle:30,1')
            ->name('evidence.download');

        Route::post('/{submissionId}/review', [
            \App\Http\Controllers\CampaignResultController::class,
            'review',
        ])->whereNumber('submissionId')
          ->middleware('throttle:20,1')->name('review');

        Route::get('/tally', [
            \App\Http\Controllers\CampaignResultController::class,
            'tally',
        ])->name('tally');
    });


// Public KuraGuard participation application routes.
// No authentication; submission is CSRF-protected and rate-limited.
Route::middleware('throttle:30,1')
    ->prefix('join/api')
    ->name('join.api.')
    ->group(function () {
        Route::get('/campaigns', [
            \App\Http\Controllers\PublicRoleApplicationController::class,
            'campaigns',
        ])->name('campaigns');

        Route::get('/geography', [
            \App\Http\Controllers\PublicRoleApplicationController::class,
            'geography',
        ])->name('geography');

        Route::post('/applications', [
            \App\Http\Controllers\PublicRoleApplicationController::class,
            'store',
        ])
            ->middleware('throttle:5,1')
            ->name('applications.store');
    });


// Public KuraGuard participation pages.
Route::view('/join', 'public.join')->name('join.index');
Route::view('/join/agent', 'public.join')->name('join.agent');
Route::view('/join/mobilizer', 'public.join')->name('join.mobilizer');


// Campaign recruitment management.
// Only campaign owners and campaign administrators may modify settings.
Route::middleware([
    'auth',
    'can:campaign.assign-agents,contestId',
])
    ->prefix('campaigns/{contestId}/recruitment')
    ->whereNumber('contestId')
    ->name('campaign.recruitment.')
    ->group(function () {
        Route::get('/', [
            \App\Http\Controllers\CampaignRecruitmentController::class,
            'index',
        ])->name('index');

        Route::patch('/', [
            \App\Http\Controllers\CampaignRecruitmentController::class,
            'update',
        ])->name('update');
    });


// Authenticated campaign reporting pages.
// Existing tally and audit JSON endpoints remain unchanged.
\Route::middleware(['auth', 'can:campaign.view,contestId'])
    ->get(
        '/campaigns/{contestId}/reports/tally',
        [\App\Http\Controllers\CampaignReportPageController::class, 'tally']
    )
    ->whereNumber('contestId')
    ->name('campaign.reports.tally');

\Route::middleware(['auth', 'can:campaign.review-applications,contestId'])
    ->get(
        '/campaigns/{contestId}/reports/audit-history',
        [\App\Http\Controllers\CampaignReportPageController::class, 'audit']
    )
    ->whereNumber('contestId')
    ->name('campaign.reports.audit');


// Unified campaign-specific polling station and agent pages.
// The legacy demonstration routes remain unchanged.
\Route::middleware(['auth', 'can:campaign.view,contestId'])
    ->get(
        '/campaigns/{contestId}/polling-stations',
        [\App\Http\Controllers\CampaignNetworkPageController::class, 'stations']
    )
    ->whereNumber('contestId')
    ->name('campaign.network.stations');

\Route::middleware(['auth', 'can:campaign.assign-agents,contestId'])
    ->get(
        '/campaigns/{contestId}/agents',
        [\App\Http\Controllers\CampaignNetworkPageController::class, 'agents']
    )
    ->whereNumber('contestId')
    ->name('campaign.network.agents');


// Campaign-scoped public recruitment review.
\Route::middleware([
    'auth',
    'can:campaign.review-applications,contestId',
])
    ->post(
        '/campaigns/{contestId}/public-applications/{applicationId}/review',
        [\App\Http\Controllers\PublicApplicationReviewController::class, 'review']
    )
    ->whereNumber('contestId')
    ->whereNumber('applicationId')
    ->name('campaign.public-applications.review');


// Public aspirant account registration.
// Existing /aspirants/register campaign onboarding remains unchanged.
\Route::middleware(['guest', 'throttle:10,1'])
    ->get('/register', [
        \App\Http\Controllers\AspirantAccountRegistrationController::class,
        'create',
    ])
    ->name('register');

\Route::middleware(['guest', 'throttle:5,1'])
    ->post('/register', [
        \App\Http\Controllers\AspirantAccountRegistrationController::class,
        'store',
    ])
    ->name('register.store');


// Approved applicant activation invitations.
// Only authorized campaign reviewers may issue invitations.
\Route::post(
    '/campaigns/{contestId}/public-applications/{applicationId}/invite',
    [
        \App\Http\Controllers\AgentInvitationController::class,
        'send',
    ]
)
    ->middleware(['auth', 'throttle:5,1'])
    ->name('campaign.agent-invitations.send');


// Single-use agent activation links.
\Route::get('/agent/activate/{token}', [
    \App\Http\Controllers\AgentActivationController::class,
    'create',
])
    ->middleware('throttle:10,1')
    ->name('agent.activation.create');

\Route::post('/agent/activate/{token}', [
    \App\Http\Controllers\AgentActivationController::class,
    'store',
])
    ->middleware('throttle:5,1')
    ->name('agent.activation.store');


// Owner-only, contest-specific aspirant discovery publication settings.
\Route::middleware(['auth', 'throttle:30,1'])
    ->prefix('campaigns/{contestId}/discovery-settings')
    ->whereNumber('contestId')
    ->group(function () {
        \Route::get('/', [\App\Http\Controllers\CampaignDiscoveryConsentController::class, 'show'])
            ->name('campaign.discovery-settings.show');
        \Route::post('/', [\App\Http\Controllers\CampaignDiscoveryConsentController::class, 'update'])
            ->name('campaign.discovery-settings.update');
    });

// Authenticated, read-only campaign scenario calculator.
\Route::get(
    '/campaigns/{contestId}/scenarios',
    [\App\Http\Controllers\CampaignScenarioController::class, 'index']
)
    ->middleware([
        'auth',
        'can:campaign.view,contestId',
    ])
    ->whereNumber('contestId')
    ->name('campaign.scenarios.index');

// Authenticated, read-only geography for the campaign Scenario Builder.
\Route::get(
    '/campaigns/{contestId}/scenarios/geography',
    [\App\Http\Controllers\CampaignScenarioGeographyController::class, 'index']
)
    ->middleware(['auth', 'can:campaign.view,contestId', 'throttle:60,1'])
    ->whereNumber('contestId')
    ->name('campaign.scenarios.geography');

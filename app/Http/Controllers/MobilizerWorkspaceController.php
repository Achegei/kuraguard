<?php

namespace App\Http\Controllers;

use App\Services\MobilizerAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MobilizerWorkspaceController extends Controller
{
    public function index(
        Request $request,
        MobilizerAccessService $access
    ): View {
        $userId = (int) $request->user()->id;

        $assignments = $access->activeAssignments($userId);

        $approvedApplications = DB::table('role_applications')
            ->where('applicant_user_id', $userId)
            ->where('requested_role', 'mobilizer')
            ->where('status', 'approved')
            ->count();

        abort_if(
            $assignments->isEmpty() && $approvedApplications === 0,
            403,
            'You do not have an authorized mobilizer account.'
        );

        return view('mobilizer.workspace', [
            'assignments' => $assignments,
            'awaitingDeployment' => $assignments->isEmpty(),
        ]);
    }
}

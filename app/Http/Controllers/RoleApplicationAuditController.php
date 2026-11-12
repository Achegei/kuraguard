<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RoleApplicationAuditController extends Controller
{
    public function index(
        Request $request,
        int $contestId
    ): JsonResponse {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        abort_unless(
            Gate::forUser($user)->allows(
                'campaign.review-applications',
                $contestId
            ),
            403
        );

        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 25);

        $records = DB::table('audit_logs as audit')
            ->join(
                'role_applications as applications',
                function ($join) use ($contestId) {
                    $join->on(
                        'applications.id',
                        '=',
                        'audit.entity_id'
                    )->where(
                        'applications.aspirant_contest_id',
                        '=',
                        $contestId
                    );
                }
            )
            ->where('audit.entity_type', 'role_application')
            ->whereIn('audit.action', [
                'role_application.approved',
                'role_application.rejected',
            ])
            ->select([
                'audit.id',
                'audit.user_id',
                'audit.action',
                'audit.entity_id',
                'audit.metadata',
                'audit.created_at',
            ])
            ->orderByDesc('audit.id')
            ->paginate($perPage);

        $records->getCollection()->transform(
            function ($record) {
                $record->metadata = json_decode(
                    $record->metadata ?? '{}',
                    true
                );

                return $record;
            }
        );

        return response()->json($records);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FieldAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FieldAssignmentController extends Controller
{
    public function __construct(
        private readonly FieldAssignmentService $assignments
    ) {
    }


    public function end(
        Request $request,
        int $contestId,
        int $assignmentId
    ): JsonResponse {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->assignments->endAssignment(
            $user,
            $contestId,
            $assignmentId,
            $validated['reason'] ?? null
        );

        return response()->json([
            'message' => 'Field assignment ended successfully.',
            'assignment_id' => $assignmentId,
            'status' => 'ended',
        ], 200);
    }

    public function store(
        Request $request,
        int $contestId,
        int $applicationId
    ): JsonResponse {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $assignmentId = $this->assignments->assign(
            $user,
            $contestId,
            $applicationId
        );

        return response()->json([
            'message' => 'Field assignment created successfully.',
            'assignment_id' => $assignmentId,
            'application_id' => $applicationId,
            'status' => 'assigned',
        ], 201);
    }
}

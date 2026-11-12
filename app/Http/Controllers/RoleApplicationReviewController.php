<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\RoleApplicationReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleApplicationReviewController extends Controller
{
    public function __construct(
        private readonly RoleApplicationReviewService $reviews
    ) {
    }

    public function approve(
        Request $request,
        int $contestId,
        int $applicationId
    ): JsonResponse {
        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $this->reviews->approve(
            $user,
            $contestId,
            $applicationId,
            $validated['review_notes'] ?? null
        );

        return response()->json([
            'message' => 'Application approved successfully.',
            'application_id' => $applicationId,
            'status' => 'approved',
        ]);
    }

    public function reject(
        Request $request,
        int $contestId,
        int $applicationId
    ): JsonResponse {
        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $this->reviews->reject(
            $user,
            $contestId,
            $applicationId,
            $validated['review_notes'] ?? null
        );

        return response()->json([
            'message' => 'Application rejected successfully.',
            'application_id' => $applicationId,
            'status' => 'rejected',
        ]);
    }
}

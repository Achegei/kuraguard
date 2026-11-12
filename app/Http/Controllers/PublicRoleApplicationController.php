<?php

namespace App\Http\Controllers;

use App\Enums\ElectoralFieldRole;
use App\Services\RoleApplicationValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicRoleApplicationController extends Controller
{
    public function campaigns(): JsonResponse
    {
        $campaigns = DB::table('aspirant_contests as contests')
            ->where('contests.public_recruitment_enabled', true)
            ->where('contests.public_recruitment_open', true)
            ->join('aspirants as aspirants', 'aspirants.id', '=', 'contests.aspirant_id')
            ->select(
                'contests.id',
                'contests.office',
                'contests.election_year',
                'aspirants.display_name'
            )
            ->orderBy('aspirants.display_name')
            ->limit(100)
            ->get();

        return response()->json(['data' => $campaigns]);
    }

    public function geography(Request $request): JsonResponse
    {
        $data = $request->validate([
            'level' => [
                'required',
                Rule::in([
                    'county',
                    'constituency',
                    'ward',
                    'polling_centre',
                    'polling_station',
                ]),
            ],
            'parent_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $level = $data['level'];
        $parentId = $data['parent_id'] ?? null;

        if ($level !== 'county' && $parentId === null) {
            throw ValidationException::withMessages([
                'parent_id' => 'A parent electoral area is required.',
            ]);
        }

        $query = match ($level) {
            'county' => DB::table('electoral_counties'),
            'constituency' => DB::table('electoral_constituencies')
                ->where('county_id', $parentId)
                ->whereNull('special_category_id'),
            'ward' => DB::table('electoral_wards')
                ->where('constituency_id', $parentId),
            'polling_centre' => DB::table('electoral_polling_centres')
                ->where('ward_id', $parentId),
            'polling_station' => DB::table('electoral_polling_stations')
                ->where('polling_centre_id', $parentId)
                ->where('is_active', true),
        };

        return response()->json([
            'data' => $query
                ->select('id', 'name', 'code')
                ->orderBy('name')
                ->limit(500)
                ->get(),
        ]);
    }

    public function store(
        Request $request,
        RoleApplicationValidator $validator
    ): JsonResponse {
        $data = $request->validate([
            'aspirant_contest_id' => [
                'required', 'integer',
                'exists:aspirant_contests,id',
            ],
            'requested_role' => [
                'required',
                Rule::in(['polling_station_agent', 'mobilizer']),
            ],
            'full_name' => [
                'required', 'string', 'min:3', 'max:150',
            ],
            'email' => [
                'required', 'email', 'max:190',
            ],
            'phone' => [
                'required', 'string', 'min:9', 'max:30',
            ],
            'geographic_level' => [
                'required',
                Rule::in([
                    'county',
                    'constituency',
                    'ward',
                    'polling_centre',
                    'polling_station',
                ]),
            ],
            'geographic_id' => [
                'required', 'integer', 'min:1',
            ],
            'experience' => [
                'nullable', 'string', 'max:3000',
            ],
        ]);

        $contest = DB::table('aspirant_contests')
            ->where('id', $data['aspirant_contest_id'])
            ->first();

        if (
            !$contest ||
            !(bool) $contest->public_recruitment_enabled ||
            !(bool) $contest->public_recruitment_open
        ) {
            abort(404, 'Campaign recruitment is unavailable.');
        }


        $role = $data['requested_role'];
        $level = $data['geographic_level'];

        if ($role === 'polling_station_agent') {
            $validatedGeography = $validator->validate(
                (int) $contest->id,
                ElectoralFieldRole::POLLING_STATION_AGENT,
                $level,
                (int) $data['geographic_id'],
                (int) $contest->electoral_dataset_id
            );
        } else {
            $office = \App\Enums\ElectoralOffice::tryFrom(
                $contest->office
            );

            if (! $office) {
                abort(422, 'Unsupported electoral office.');
            }

            $jurisdiction = app(
                \App\Services\ElectoralJurisdictionValidator::class
            );

            $contestScope = $jurisdiction->validateContest(
                $office,
                $contest->county_id,
                $contest->constituency_id,
                $contest->ward_id
            );

            $location = $jurisdiction->validateApplication(
                $office,
                $contestScope,
                $level,
                (int) $data['geographic_id']
            );

            $validatedGeography = [
                'electoral_dataset_id' => $contest->electoral_dataset_id,
                'geographic_level' => $level,
                'county_id' => $location['county_id'],
                'constituency_id' => $location['constituency_id'],
                'ward_id' => $location['ward_id'],
                'polling_centre_id' => $location['polling_centre_id'],
                'polling_station_id' => $location['polling_station_id'],
            ];
        }

        $email = mb_strtolower(trim($data['email']));

        $duplicate = DB::table('public_role_applications')
            ->where('aspirant_contest_id', $contest->id)
            ->where('requested_role', $role)
            ->where('email', $email)
            ->where('status', 'pending')
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'email' => 'An application is already pending for this campaign and role.',
            ]);
        }

        DB::table('public_role_applications')->insert([
            'aspirant_contest_id' => $contest->id,
            'requested_role' => $role,
            'full_name' => trim($data['full_name']),
            'email' => $email,
            'phone' => trim($data['phone']),
            'experience' => $data['experience'] ?? null,
            'status' => 'pending',
            'electoral_dataset_id' => $contest->electoral_dataset_id,
            'geographic_level' => $level,
            'county_id' => $validatedGeography['county_id'],
            'constituency_id' => $validatedGeography['constituency_id'],
            'ward_id' => $validatedGeography['ward_id'],
            'polling_centre_id' => $validatedGeography['polling_centre_id'],
            'polling_station_id' => $validatedGeography['polling_station_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Application received successfully.',
            'status' => 'pending',
        ], 201);
    }
}

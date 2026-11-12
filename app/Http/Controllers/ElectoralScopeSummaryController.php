<?php

namespace App\Http\Controllers;

use App\Enums\ElectoralOffice;
use App\Services\ElectoralScopeResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ElectoralScopeSummaryController extends Controller
{
    public function show(
        Request $request,
        ElectoralScopeResolver $resolver
    ): JsonResponse {
        $validated = $request->validate([
            'office' => [
                'required',
                Rule::enum(ElectoralOffice::class),
            ],
            'geographic_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ]);

        $office = ElectoralOffice::from($validated['office']);

        $geographicId = isset($validated['geographic_id'])
            ? (int) $validated['geographic_id']
            : null;

        $summary = $resolver->resolve(
            $office,
            $geographicId
        );

        $summary['statistics_scope'] =
            'ordinary_county_linked_geography';

        $summary['reference_notice'] =
            'Historical IEBC 2022 registered-voter reference. '
            . 'Not a certified 2027 voter register or election forecast.';

        if ($office === ElectoralOffice::PRESIDENT) {
            $summary['national_total_notice'] =
                'The ordinary geographic summary excludes '
                . 'Diaspora and Prisons special categories.';
        }

        return response()->json([
            'data' => $summary,
        ]);
    }
}

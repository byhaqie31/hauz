<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyCoOwner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyCoOwnerController extends Controller
{
    public function index(Request $request, Property $property): JsonResponse
    {
        abort_if($property->owner_id !== $request->user()->id, 403);

        return response()->json($property->coOwners);
    }

    public function store(Request $request, Property $property): JsonResponse
    {
        abort_if($property->owner_id !== $request->user()->id, 403);

        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'share_pct'  => 'required|numeric|min:0.01|max:100',
            'is_primary' => 'boolean',
            'user_id'    => 'nullable|uuid|exists:users,id',
        ]);

        $coOwner = $property->coOwners()->create($data);

        return response()->json($coOwner, 201);
    }

    /** Replace all co-owners in one call — validates sum=100 and one primary. */
    public function sync(Request $request, Property $property): JsonResponse
    {
        abort_if($property->owner_id !== $request->user()->id, 403);

        $data = $request->validate([
            'co_owners'               => 'required|array|min:1',
            'co_owners.*.name'        => 'required|string|max:255',
            'co_owners.*.share_pct'   => 'required|numeric|min:0.01|max:100',
            'co_owners.*.is_primary'  => 'required|boolean',
            'co_owners.*.user_id'     => 'nullable|uuid|exists:users,id',
        ]);

        $coOwners = $data['co_owners'];

        // Invariant: shares must sum to 100
        $total = array_sum(array_column($coOwners, 'share_pct'));
        if (abs($total - 100) > 0.01) {
            return response()->json([
                'message' => 'Co-owner shares must sum to 100%.',
                'total'   => $total,
            ], 422);
        }

        // Invariant: exactly one primary
        $primaryCount = count(array_filter($coOwners, fn ($c) => $c['is_primary']));
        if ($primaryCount !== 1) {
            return response()->json([
                'message' => 'Exactly one co-owner must be marked as primary.',
            ], 422);
        }

        $property->coOwners()->delete();

        foreach ($coOwners as $row) {
            $property->coOwners()->create($row);
        }

        return response()->json($property->coOwners()->get());
    }

    public function destroy(Request $request, Property $property, PropertyCoOwner $coOwner): JsonResponse
    {
        abort_if($property->owner_id !== $request->user()->id, 403);
        abort_if($coOwner->is_primary, 422, 'Cannot remove the primary owner. Assign a new primary first.');

        $coOwner->delete();

        return response()->json(null, 204);
    }
}

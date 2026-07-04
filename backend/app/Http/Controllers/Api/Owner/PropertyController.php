<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyCoOwner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $properties = Property::with(['coOwners', 'units'])
            ->where('owner_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json($properties);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'type'     => 'required|in:condo,landed,shoplot,room',
            'address'  => 'required|string|max:500',
            'city'     => 'required|string|max:100',
            'state'    => 'required|string|max:50',
            'postcode' => 'required|digits:5',
        ]);

        $property = Property::create(array_merge($data, [
            'owner_id' => $request->user()->id,
        ]));

        // Seed the primary co-owner entry for this owner
        PropertyCoOwner::create([
            'property_id' => $property->id,
            'user_id'     => $request->user()->id,
            'name'        => $request->user()->name,
            'share_pct'   => 100.00,
            'is_primary'  => true,
        ]);

        return response()->json($property->load('coOwners'), 201);
    }

    public function show(Request $request, Property $property): JsonResponse
    {
        abort_if($property->owner_id !== $request->user()->id, 403);

        return response()->json($property->load(['coOwners', 'units']));
    }

    public function update(Request $request, Property $property): JsonResponse
    {
        abort_if($property->owner_id !== $request->user()->id, 403);

        $data = $request->validate([
            'name'              => 'sometimes|string|max:255',
            'internal_label'    => 'nullable|string|max:255',
            'type'              => 'sometimes|in:condo,landed,shoplot,room',
            'address'           => 'sometimes|string|max:500',
            'city'              => 'sometimes|string|max:100',
            'state'             => 'sometimes|string|max:50',
            'postcode'          => 'sometimes|digits:5',
            'notes'             => 'nullable|string',
            'year_built'        => 'nullable|integer|min:1900|max:2100',
            'built_up_sqft'     => 'nullable|integer|min:1',
            'land_sqft'         => 'nullable|integer|min:1',
            'bedrooms'          => 'nullable|integer|min:0|max:20',
            'bathrooms'         => 'nullable|integer|min:0|max:20',
            'parking_lots'      => 'nullable|integer|min:0',
            'furnishing'        => 'nullable|in:unfurnished,partial,fully',
            'ownership'         => 'nullable|array',
            'utilities'         => 'nullable|array',
        ]);

        $property->update($data);

        return response()->json($property->load(['coOwners', 'units']));
    }

    public function destroy(Request $request, Property $property): JsonResponse
    {
        abort_if($property->owner_id !== $request->user()->id, 403);

        $property->delete();

        return response()->json(null, 204);
    }
}

<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Request $request, Property $property): JsonResponse
    {
        abort_if($property->owner_id !== $request->user()->id, 403);

        return response()->json($property->units);
    }

    public function store(Request $request, Property $property): JsonResponse
    {
        abort_if($property->owner_id !== $request->user()->id, 403);

        $data = $request->validate([
            'label'     => 'required|string|max:255',
            'bedrooms'  => 'nullable|integer|min:0|max:20',
            'bathrooms' => 'nullable|integer|min:0|max:20',
            'sqft'      => 'nullable|integer|min:1',
            'status'    => 'nullable|in:vacant,occupied,maintenance',
        ]);

        $unit = $property->units()->create($data);

        return response()->json($unit, 201);
    }

    public function show(Request $request, Property $property, Unit $unit): JsonResponse
    {
        abort_if($property->owner_id !== $request->user()->id, 403);
        abort_if($unit->property_id !== $property->id, 404);

        return response()->json($unit);
    }

    public function update(Request $request, Property $property, Unit $unit): JsonResponse
    {
        abort_if($property->owner_id !== $request->user()->id, 403);
        abort_if($unit->property_id !== $property->id, 404);

        $data = $request->validate([
            'label'     => 'sometimes|string|max:255',
            'bedrooms'  => 'nullable|integer|min:0|max:20',
            'bathrooms' => 'nullable|integer|min:0|max:20',
            'sqft'      => 'nullable|integer|min:1',
            'status'    => 'sometimes|in:vacant,occupied,maintenance',
        ]);

        $unit->update($data);

        return response()->json($unit);
    }

    public function destroy(Request $request, Property $property, Unit $unit): JsonResponse
    {
        abort_if($property->owner_id !== $request->user()->id, 403);
        abort_if($unit->property_id !== $property->id, 404);

        $unit->delete();

        return response()->json(null, 204);
    }
}

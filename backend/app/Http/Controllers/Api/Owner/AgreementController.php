<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgreementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $agreements = Agreement::with(['unit.property', 'tenant'])
            ->whereHas('unit.property', fn ($q) =>
                $q->where('owner_id', $request->user()->id)
            )
            ->latest()
            ->get();

        return response()->json($agreements);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'unit_id'               => 'required|uuid|exists:units,id',
            'tenant_id'             => 'required|uuid|exists:users,id',
            'start_date'            => 'required|date',
            'end_date'              => 'required|date|after:start_date',
            'rent_amount_cents'     => 'required|integer|min:1',
            'deposit_amount_cents'  => 'required|integer|min:0',
            'late_fee_cents'        => 'nullable|integer|min:0',
            'rent_due_day'          => 'required|integer|min:1|max:28',
            'status'                => 'nullable|in:draft,active,expired,terminated',
        ]);

        // Verify the unit belongs to this owner
        $unit = Unit::findOrFail($data['unit_id']);
        abort_if($unit->property->owner_id !== $request->user()->id, 403);

        $agreement = Agreement::create($data);

        return response()->json($agreement->load(['unit.property', 'tenant']), 201);
    }

    public function show(Request $request, Agreement $agreement): JsonResponse
    {
        $this->authorizeOwner($request, $agreement);

        return response()->json($agreement->load(['unit.property', 'tenant', 'tenancy', 'invoices']));
    }

    public function update(Request $request, Agreement $agreement): JsonResponse
    {
        $this->authorizeOwner($request, $agreement);

        $data = $request->validate([
            'start_date'           => 'sometimes|date',
            'end_date'             => 'sometimes|date',
            'rent_amount_cents'    => 'sometimes|integer|min:1',
            'deposit_amount_cents' => 'sometimes|integer|min:0',
            'late_fee_cents'       => 'nullable|integer|min:0',
            'rent_due_day'         => 'sometimes|integer|min:1|max:28',
            'status'               => 'sometimes|in:draft,active,expired,terminated',
        ]);

        $agreement->update($data);

        return response()->json($agreement->load(['unit.property', 'tenant']));
    }

    public function destroy(Request $request, Agreement $agreement): JsonResponse
    {
        $this->authorizeOwner($request, $agreement);

        $agreement->delete();

        return response()->json(null, 204);
    }

    private function authorizeOwner(Request $request, Agreement $agreement): void
    {
        abort_if(
            $agreement->unit->property->owner_id !== $request->user()->id,
            403
        );
    }
}

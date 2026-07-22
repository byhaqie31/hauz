<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgreementResource;
use App\Http\Resources\AgreementWithRefsResource;
use App\Models\Agreement;
use Illuminate\Http\Request;

class TenantAgreementController extends Controller
{
    public function show(Request $request)
    {
        $base = Agreement::where('tenant_id', $request->user()->id);

        $agreement = (clone $base)->where('status', 'active')->latest()->first()
            ?? (clone $base)->where('status', '!=', 'draft')->orderByDesc('start_date')->first();

        if (! $agreement) {
            // response()->json(null) would encode {} here (Symfony's JsonResponse constructor
            // coalesces a null $data to an empty ArrayObject). setData(null) bypasses that and
            // encodes the literal JSON null the frontend/tests expect.
            return response()->json()->setData(null);
        }

        if ($request->filled('expand')) {
            $agreement->load(['unit.property.coOwners', 'tenant']);

            return new AgreementWithRefsResource($agreement);
        }

        return new AgreementResource($agreement);
    }
}

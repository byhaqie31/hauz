<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantAgreementController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $agreement = Agreement::with(['unit.property', 'invoices'])
            ->where('tenant_id', $request->user()->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        abort_if(! $agreement, 404, 'No active agreement found.');

        return response()->json($agreement);
    }
}

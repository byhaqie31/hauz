<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'phone'             => $user->phone,
            'personal'          => $user->personal_info,
            'emergency_contact' => $user->emergency_contact,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                      => 'sometimes|string|max:255',
            'phone'                     => 'sometimes|string|max:30',
            'personal'                  => 'nullable|array',
            'personal.ic_number'        => 'nullable|string|max:20',
            'personal.date_of_birth'    => 'nullable|date',
            'personal.occupation'       => 'nullable|string|max:255',
            'personal.employer'         => 'nullable|string|max:255',
            'personal.monthly_income_cents' => 'nullable|integer|min:0',
            'personal.nationality'      => 'nullable|string|max:100',
            'emergency_contact'         => 'nullable|array',
            'emergency_contact.name'    => 'nullable|string|max:255',
            'emergency_contact.phone'   => 'nullable|string|max:30',
            'emergency_contact.relationship' => 'nullable|string|max:100',
        ]);

        $update = array_filter([
            'name'              => $data['name'] ?? null,
            'phone'             => $data['phone'] ?? null,
            'personal_info'     => $data['personal'] ?? null,
            'emergency_contact' => $data['emergency_contact'] ?? null,
        ], fn ($v) => $v !== null);

        $request->user()->update($update);

        return $this->show($request);
    }
}

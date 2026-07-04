<?php

namespace App\Http\Controllers\Api\Owner;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /**
     * List all tenants who have (or had) an agreement on a unit owned by this owner.
     * Until Global Scopes land, we join through agreements → units → properties.
     */
    public function index(Request $request): JsonResponse
    {
        $tenants = User::where('role', UserRole::TENANT)
            ->whereHas('agreements.unit.property', fn ($q) =>
                $q->where('owner_id', $request->user()->id)
            )
            ->latest()
            ->get();

        return response()->json($tenants);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required|string|max:30',
        ]);

        $tenant = User::create(array_merge($data, [
            'role'       => UserRole::TENANT,
            'invited_at' => now(),
        ]));

        return response()->json($tenant, 201);
    }

    public function show(Request $request, User $tenant): JsonResponse
    {
        abort_if($tenant->role !== UserRole::TENANT, 404);
        $this->authorizeTenantAccess($request, $tenant);

        return response()->json($tenant);
    }

    public function update(Request $request, User $tenant): JsonResponse
    {
        abort_if($tenant->role !== UserRole::TENANT, 404);
        $this->authorizeTenantAccess($request, $tenant);

        $data = $request->validate([
            'name'              => 'sometimes|string|max:255',
            'phone'             => 'sometimes|string|max:30',
            'status'            => 'sometimes|in:invited,active,notice_given,moved_out',
            'personal'          => 'nullable|array',
            'emergency_contact' => 'nullable|array',
        ]);

        // Map frontend field names to DB column names
        if (isset($data['personal'])) {
            $data['personal_info'] = $data['personal'];
            unset($data['personal']);
        }

        $tenant->update($data);

        return response()->json($tenant);
    }

    public function destroy(Request $request, User $tenant): JsonResponse
    {
        abort_if($tenant->role !== UserRole::TENANT, 404);
        $this->authorizeTenantAccess($request, $tenant);

        $tenant->delete();

        return response()->json(null, 204);
    }

    public function invite(Request $request, User $tenant): JsonResponse
    {
        // TODO Phase 2: dispatch TenantInviteNotification (magic link)
        return response()->json(['message' => 'Invite feature coming in Phase 2.'], 501);
    }

    private function authorizeTenantAccess(Request $request, User $tenant): void
    {
        $isOwner = $tenant->agreements()
            ->whereHas('unit.property', fn ($q) =>
                $q->where('owner_id', $request->user()->id)
            )
            ->exists();

        abort_unless($isOwner, 403);
    }
}

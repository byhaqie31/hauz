<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAccountNotificationsRequest;
use App\Http\Requests\UpdateAccountPreferencesRequest;
use App\Http\Requests\UpdateAccountProfileRequest;
use App\Http\Resources\OwnerAccountResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function show(Request $request): OwnerAccountResource
    {
        return new OwnerAccountResource($request->user()->fresh());
    }

    public function updateProfile(UpdateAccountProfileRequest $request): OwnerAccountResource
    {
        $request->user()->update($request->toModelAttributes());

        return new OwnerAccountResource($request->user()->fresh());
    }

    public function updatePreferences(UpdateAccountPreferencesRequest $request): OwnerAccountResource
    {
        $current = $request->user()->owner_preferences ?? OwnerAccountResource::defaultPreferences();
        $request->user()->update(['owner_preferences' => array_merge($current, $request->validated())]);

        return new OwnerAccountResource($request->user()->fresh());
    }

    public function updateNotifications(UpdateAccountNotificationsRequest $request): OwnerAccountResource
    {
        $current = $request->user()->notification_preferences ?? OwnerAccountResource::defaultNotifications();
        $request->user()->update(['notification_preferences' => array_merge($current, $request->validated())]);

        return new OwnerAccountResource($request->user()->fresh());
    }

    public function plans(): JsonResponse
    {
        return response()->json([
            ['tier' => 'free',     'priceRm' => 0,   'unitsCap' => 2,           'description' => 'owner.settings.plan.tiers.free'],
            ['tier' => 'starter',  'priceRm' => 29,  'unitsCap' => 5,           'description' => 'owner.settings.plan.tiers.starter'],
            ['tier' => 'pro',      'priceRm' => 79,  'unitsCap' => 25,          'description' => 'owner.settings.plan.tiers.pro'],
            ['tier' => 'business', 'priceRm' => 199, 'unitsCap' => 'unlimited', 'description' => 'owner.settings.plan.tiers.business'],
        ]);
    }
}

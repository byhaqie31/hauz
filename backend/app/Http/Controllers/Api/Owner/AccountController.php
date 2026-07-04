<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'profile' => [
                'id'                 => $user->id,
                'name'               => $user->name,
                'email'              => $user->email,
                'phone'              => $user->phone,
                'business_name'      => $user->business_name,
                'bank_account_last4' => $user->bank_account_last4,
                'photo_url'          => null, // Phase 4
            ],
            'preferences'   => $user->owner_preferences ?? ['locale' => 'en', 'theme' => 'system', 'money_locale' => 'en-MY'],
            'notifications' => $user->notification_preferences ?? ['events' => [], 'channels' => []],
            'plan_tier'     => $user->plan_tier,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => 'sometimes|string|max:255',
            'phone'         => 'sometimes|string|max:30',
            'business_name' => 'nullable|string|max:255',
        ]);

        $request->user()->update($data);

        return response()->json($request->user()->fresh());
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'locale'       => 'sometimes|in:en,ms',
            'theme'        => 'sometimes|in:light,dark,system',
            'money_locale' => 'sometimes|in:en-MY',
        ]);

        $current = $request->user()->owner_preferences ?? [];
        $request->user()->update(['owner_preferences' => array_merge($current, $data)]);

        return response()->json($request->user()->fresh()->owner_preferences);
    }

    public function updateNotifications(Request $request): JsonResponse
    {
        $data = $request->validate([
            'events'   => 'sometimes|array',
            'channels' => 'sometimes|array',
        ]);

        $current = $request->user()->notification_preferences ?? [];
        $request->user()->update(['notification_preferences' => array_merge($current, $data)]);

        return response()->json($request->user()->fresh()->notification_preferences);
    }

    public function plans(): JsonResponse
    {
        return response()->json([
            ['tier' => 'free',     'price_rm' => 0,   'units_cap' => 2,           'description' => 'owner.settings.plan.tiers.free'],
            ['tier' => 'starter',  'price_rm' => 29,  'units_cap' => 5,           'description' => 'owner.settings.plan.tiers.starter'],
            ['tier' => 'pro',      'price_rm' => 79,  'units_cap' => 25,          'description' => 'owner.settings.plan.tiers.pro'],
            ['tier' => 'business', 'price_rm' => 199, 'units_cap' => 'unlimited', 'description' => 'owner.settings.plan.tiers.business'],
        ]);
    }
}

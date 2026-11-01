<?php

namespace App\Services\Api;

use App\Models\Center;
use App\Models\User;
use App\Services\CenterHierarchy;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;

class AuthService
{
    public function __construct(
        private readonly CenterHierarchy $centerHierarchy
    ) {}

    /**
     * التحقق من بيانات المستخدم وإصدار Token.
     */
    public function login(
        array $credentials,
        string $deviceName = 'flutter-app'
    ): ?array {
        $user = User::query()
            ->where('email', $credentials['email'])
            ->first();

        if (
            ! $user ||
            ! Hash::check($credentials['password'], $user->password)
        ) {
            return null;
        }

        $token = $user->createToken(
            $deviceName,
            ['*']
        );

        return [
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $this->userData($user),
        ];
    }

    /**
     * تجهيز بيانات المستخدم والمراكز والأدوار والصلاحيات.
     */
    public function userData(User $user): array
    {
        $accessibleIds = $this->centerHierarchy
            ->accessibleIdsForUser($user);

        $centers = Center::query()
            ->with('region')
            ->whereIn('id', $accessibleIds)
            ->orderBy('name')
            ->get()
            ->map(fn (Center $center) => [
                'id' => $center->id,
                'name' => $center->name,
                'code' => $center->code,
                'region_id' => $center->region_id,

                'region' => $center->region ? [
                    'id' => $center->region->id,
                    'name' => $center->region->name,
                ] : null,

                'phone' => $center->phone,
                'email' => $center->email,
                'address' => $center->address,
                'is_active' => $center->is_active,
            ])
            ->values()
            ->all();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,

            'center_id' => $user->center_id,
            'region_id' => $user->region_id,

            'centers' => $centers,

            'roles' => $user->getRoleNames()
                ->values()
                ->all(),

            'permissions' => $user->getAllPermissions()
                ->pluck('name')
                ->unique()
                ->values()
                ->all(),
        ];
    }
}

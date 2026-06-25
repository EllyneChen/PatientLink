<?php

return [

    // JWT secret — generate with: php artisan jwt:secret
    'secret' => env('JWT_SECRET'),

    // Token time-to-live in minutes.
    // NFR-01: "Tokens shall ... expire after a configurable duration."
    'ttl' => env('JWT_TTL', 60),

    // Refresh window in minutes (how long a token can still be refreshed
    // after it expires).
    'refresh_ttl' => env('JWT_REFRESH_TTL', 20160),

    'algo' => env('JWT_ALGO', 'HS256'),

    'required_claims' => [
        'iss',
        'iat',
        'exp',
        'nbf',
        'sub',
        'jti',
    ],

    'persistent_claims' => [
        // 'role' is set via getJWTCustomClaims() in User model and
        // persists across token refreshes so RoleMiddleware keeps working.
        'role',
    ],

    'lock_subject' => true,

    'leeway' => env('JWT_LEEWAY', 0),

    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),

    'blacklist_grace_period' => env('JWT_BLACKLIST_GRACE_PERIOD', 0),

    'show_black_list_exception' => false,

    'decrypt_cookies' => false,

    'providers' => [
        'jwt' => Tymon\JWTAuth\Providers\JWT\Lcobucci::class,
        'auth' => Tymon\JWTAuth\Providers\Auth\Illuminate::class,
        'storage' => Tymon\JWTAuth\Providers\Storage\Illuminate::class,
    ],

];

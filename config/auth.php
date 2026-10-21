<?php

return [
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => 'cuentas',
    ],
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'cuentas',
        ],
    ],
    'providers' => [
        'cuentas' => [
            'driver' => 'eloquent',
            'model' => App\Models\CuentaAcceso::class,
        ],
    ],
    'passwords' => [
        'cuentas' => [
            'provider' => 'cuentas',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],
    'password_timeout' => 10800,
];

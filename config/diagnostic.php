<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Diagnostic Dashboard PIN
    |--------------------------------------------------------------------------
    |
    | Keep this value in the environment on production hosting. When it is not
    | configured, the diagnostic dashboard remains unavailable outside local.
    |
    */
    'pin' => env('DIAGNOSTIC_PIN'),

    /*
    |--------------------------------------------------------------------------
    | Emergency PIN Access
    |--------------------------------------------------------------------------
    |
    | The diagnostic dashboard can run sensitive maintenance actions. Keep PIN
    | access disabled in production unless you explicitly need a short emergency
    | window. Super admins can still access it through the normal staff session.
    |
    */
    'allow_pin_access' => env('DIAGNOSTIC_ALLOW_PIN_ACCESS', env('APP_ENV') === 'local'),
];

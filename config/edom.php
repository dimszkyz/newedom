<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SIAKAD Handoff Secret
    |--------------------------------------------------------------------------
    |
    | Must match siakad's edom_hmac_secret. It is used to verify the signed
    | /enter token before the EDOM app trusts student and period identity.
    |
    */
    'hmac_siakad_secret' => env('HMAC_SIAKAD_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Return URL
    |--------------------------------------------------------------------------
    |
    | Used only when the handoff token does not contain a return_url.
    |
    */
    'siakad_fallback_url' => env('EDOM_SIAKAD_FALLBACK_URL', env('APP_URL')),
];

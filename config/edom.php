<?php

return [
    'hmac_siakad_secret' => env('HMAC_SIAKAD_SECRET', 'temporary-random-edom-hmac-7f4d0c9b1a8e6f203b55c1149dd83a2c'),

    'siakad_fallback_url' => env('EDOM_SIAKAD_FALLBACK_URL', env('APP_URL')),
];

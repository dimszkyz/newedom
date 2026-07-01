<?php

return [
    'hmac_siakad_secret' => env('HMAC_SIAKAD_SECRET'),
    'siakad_fallback_url' => env('EDOM_SIAKAD_FALLBACK_URL', env('APP_URL')),
];

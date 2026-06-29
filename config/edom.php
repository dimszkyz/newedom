<?php

return [
    'hmac_siakad_secret' => env('HMAC_SIAKAD_SECRET', 'temporary-random-edom-hmac-7f4d0c9b1a8e6f203b55c1149dd83a2c'),
    'siakad_fallback_url' => env('EDOM_SIAKAD_FALLBACK_URL', env('APP_URL')),

    'fake_siakad' => [
    'enabled' => filter_var(
        env('EDOM_FAKE_SIAKAD', false),
        FILTER_VALIDATE_BOOLEAN
    ),

    'krs' => [
        [
            'idtawarmatakuliahdetail' => 4567,
            'idmatakuliah' => 123,
            'kode' => 'TIF101',
            'nama' => 'Algoritma',
            'dosen' => [
                'nidn' => '0612345678',
                'nama' => 'Dosen Testing',
            ],
            'dosen_team' => [],
            'id_unw_program_studi' => 14,
        ],
    ],
],
];


<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeEdomToken extends Command
{
    // protected $signature = 'edom:make-token {idmahasiswa=testing18273} {idtahunajaran=2026} {idsemester=2} {--ttl=3600} {--return-url=}';
    protected $signature = 'edom:make-token {idmahasiswa=18273} {idtahunajaran=2026} {idsemester=2} {--ttl=3600} {--return-url=}';

    protected $description = 'Mint a siakad-style EDOM handoff token for testing /enter';

    public function handle(): int
    {
        $secret = (string) config('edom.hmac_siakad_secret');

        if ($secret === '') {
            $this->error('HMAC_SIAKAD_SECRET masih kosong. Isi dulu di file .env.');

            return self::FAILURE;
        }

        $returnUrl = $this->option('return-url')
            ?: rtrim((string) config('edom.siakad_fallback_url', config('app.url')), '/').'/edom';

        $payload = [
            'siakad_idmahasiswa' => (string) $this->argument('idmahasiswa'),
            'siakad_idtahunajaran' => (int) $this->argument('idtahunajaran'),
            'siakad_idsemester' => (int) $this->argument('idsemester'),
            'return_url' => $returnUrl,
            'exp' => time() + (int) $this->option('ttl'),
        ];

        $b64 = rtrim(strtr(base64_encode((string) json_encode($payload)), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $b64, $secret);
        $token = $b64.'.'.$signature;

        $this->line('token: '.$token);
        $this->line('url:   '.rtrim((string) config('app.url'), '/').'/enter?token='.$token);

        return self::SUCCESS;
    }
}

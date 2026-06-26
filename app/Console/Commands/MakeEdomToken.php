<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeEdomToken extends Command
{
    protected $signature = 'edom:make-token {idmahasiswa} {idtahunajaran} {idsemester} {--ttl=3600} {--return-url=}';

    protected $description = 'Mint a siakad-style EDOM handoff token for testing /enter';

    public function handle(): int
    {
        $secret = (string) config('edom.hmac_siakad_secret');

        if ($secret === '') {
            $this->error('HMAC_SIAKAD_SECRET is empty. Fill it in .env before generating a token.');

            return self::FAILURE;
        }

        $returnUrl = $this->option('return-url')
            ?: rtrim((string) config('edom.siakad_fallback_url', config('app.url')), '/').'/edom';

        $payload = [
            'siakad_idmahasiswa' => (int) $this->argument('idmahasiswa'),
            'siakad_idtahunajaran' => (int) $this->argument('idtahunajaran'),
            'siakad_idsemester' => (int) $this->argument('idsemester'),
            'return_url' => $returnUrl,
            'exp' => time() + (int) $this->option('ttl'),
        ];

        $b64 = rtrim(strtr(base64_encode((string) json_encode($payload)), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $b64, $secret);
        $token = $b64.'.'.$signature;
        $url = rtrim((string) config('app.url'), '/').'/enter?token='.$token;

        $this->line('token: '.$token);
        $this->line('url:   '.$url);

        return self::SUCCESS;
    }
}

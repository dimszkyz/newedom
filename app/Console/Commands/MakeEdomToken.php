<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeEdomToken extends Command
{
    protected $signature = 'edom:make-token {first} {second?} {third?} {--idmahasiswa=} {--ttl=3600} {--return-url=}';

    protected $description = 'Mint a siakad-style EDOM handoff token for testing /enter';

    public function handle(): int
    {
        $secret = (string) config('edom.hmac_siakad_secret');

        if ($secret === '') {
            $this->error('HMAC_SIAKAD_SECRET is empty. Fill it in .env before generating a token.');

            return self::FAILURE;
        }

        [$siakadIdMahasiswa, $siakadIdTahunAjaran, $siakadIdSemester] = $this->resolveArguments();

        if ($siakadIdTahunAjaran <= 0 || $siakadIdSemester <= 0 || $siakadIdMahasiswa <= 0) {
            $this->error('Invalid arguments. Use: php artisan edom:make-token {idtahunajaran} {idsemester} or php artisan edom:make-token {idmahasiswa} {idtahunajaran} {idsemester}.');

            return self::FAILURE;
        }

        $returnUrl = $this->option('return-url')
            ?: rtrim((string) config('edom.siakad_fallback_url', config('app.url')), '/').'/edom';

        $payload = [
            'siakad_idmahasiswa' => $siakadIdMahasiswa,
            'siakad_idtahunajaran' => $siakadIdTahunAjaran,
            'siakad_idsemester' => $siakadIdSemester,
            'return_url' => $returnUrl,
            'exp' => time() + (int) $this->option('ttl'),
        ];

        $b64 = rtrim(strtr(base64_encode((string) json_encode($payload)), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $b64, $secret);
        $token = $b64.'.'.$signature;
        $url = rtrim((string) config('app.url'), '/').'/enter?token='.$token;

        $this->line('siakad_idmahasiswa: '.$siakadIdMahasiswa);
        $this->line('siakad_idtahunajaran: '.$siakadIdTahunAjaran);
        $this->line('siakad_idsemester: '.$siakadIdSemester);
        $this->line('token: '.$token);
        $this->line('url:   '.$url);

        return self::SUCCESS;
    }

    /**
     * Supports both forms:
     * - php artisan edom:make-token 2025 2
     * - php artisan edom:make-token 18273 2025 2
     */
    private function resolveArguments(): array
    {
        $first = $this->argument('first');
        $second = $this->argument('second');
        $third = $this->argument('third');

        if ($third !== null) {
            return [(int) $first, (int) $second, (int) $third];
        }

        $defaultMahasiswa = $this->option('idmahasiswa')
            ?: config('edom.test_siakad_idmahasiswa', 18273);

        return [(int) $defaultMahasiswa, (int) $first, (int) $second];
    }
}

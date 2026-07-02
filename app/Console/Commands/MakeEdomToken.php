<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeEdomToken extends Command
{
    protected $signature = 'edom:make-token
        {idmahasiswa : ID mahasiswa dari inv_mahasiswa.id}
        {idtahunajaran : Tahun ajaran yang dikirim ke /edom/krs, misalnya 2025}
        {idsemester : ID semester yang dikirim ke /edom/krs, misalnya 1}
        {--ttl=3600 : Masa berlaku token dalam detik}
        {--return-url= : URL tujuan setelah seluruh EDOM selesai}';

    protected $description = 'Membuat token handoff EDOM untuk mahasiswa dan periode SIAKAD tertentu';

    public function handle(): int
    {
        $secret = (string) config('edom.hmac_siakad_secret');

        if ($secret === '') {
            $this->error('HMAC_SIAKAD_SECRET masih kosong. Isi dulu di file .env.');

            return self::FAILURE;
        }

        $studentId = $this->positiveInteger($this->argument('idmahasiswa'));
        $academicYear = $this->positiveInteger($this->argument('idtahunajaran'));
        $semesterId = $this->positiveInteger($this->argument('idsemester'));
        $ttl = $this->positiveInteger($this->option('ttl'));

        if ($studentId === null || $academicYear === null || $semesterId === null || $ttl === null) {
            $this->error('ID mahasiswa, tahun ajaran, semester, dan TTL harus berupa bilangan bulat positif.');

            return self::FAILURE;
        }

        $returnUrl = $this->option('return-url')
            ?: rtrim((string) config('edom.siakad_fallback_url', config('app.url')), '/').'/edom';

        $payload = [
            'siakad_idmahasiswa' => (string) $studentId,
            'siakad_idtahunajaran' => $academicYear,
            'siakad_idsemester' => $semesterId,
            'return_url' => $returnUrl,
            'exp' => time() + $ttl,
        ];

        $b64 = rtrim(strtr(base64_encode((string) json_encode($payload, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $b64, $secret);
        $token = $b64.'.'.$signature;

        $this->info("parameter: mahasiswa {$studentId}, tahun ajaran {$academicYear}, semester {$semesterId}");
        $this->line('token: '.$token);
        $this->line('url:   '.rtrim((string) config('app.url'), '/').'/enter?token='.$token);

        return self::SUCCESS;
    }

    private function positiveInteger(mixed $value): ?int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT);

        return $integer !== false && $integer > 0 ? $integer : null;
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MakeEdomTokenCommandTest extends TestCase
{
    public function test_it_generates_a_handoff_token_for_the_explicit_student_period(): void
    {
        config()->set('app.url', 'http://127.0.0.1:8000');
        config()->set('edom.hmac_siakad_secret', 'test-handoff-secret');
        config()->set('edom.siakad_fallback_url', 'https://siakad.test');

        $exitCode = Artisan::call('edom:make-token', [
            'idmahasiswa' => '18273',
            'idtahunajaran' => '2025',
            'idsemester' => '1',
            '--ttl' => '3600',
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString(
            'parameter: mahasiswa 18273, tahun ajaran 2025, semester 1',
            $output
        );
        $this->assertMatchesRegularExpression('/^token:[^\S\r\n]*(\S+)\r?$/m', $output);
        $this->assertStringContainsString('http://127.0.0.1:8000/enter?token=', $output);

        preg_match('/^token:[^\S\r\n]*(\S+)\r?$/m', $output, $matches);
        [$encodedPayload, $signature] = explode('.', $matches[1], 2);
        $payload = json_decode($this->base64UrlDecode($encodedPayload), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('18273', $payload['siakad_idmahasiswa']);
        $this->assertSame(2025, $payload['siakad_idtahunajaran']);
        $this->assertSame(1, $payload['siakad_idsemester']);
        $this->assertSame('https://siakad.test/edom', $payload['return_url']);
        $this->assertSame(
            hash_hmac('sha256', $encodedPayload, 'test-handoff-secret'),
            $signature
        );
    }

    public function test_it_rejects_an_invalid_period(): void
    {
        config()->set('edom.hmac_siakad_secret', 'test-handoff-secret');

        $exitCode = Artisan::call('edom:make-token', [
            'idmahasiswa' => '18273',
            'idtahunajaran' => '2025',
            'idsemester' => '0',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString(
            'ID mahasiswa, tahun ajaran, semester, dan TTL harus berupa bilangan bulat positif.',
            Artisan::output()
        );
    }

    private function base64UrlDecode(string $value): string
    {
        $base64 = strtr($value, '-_', '+/');
        $base64 .= str_repeat('=', (4 - strlen($base64) % 4) % 4);

        return (string) base64_decode($base64, true);
    }
}

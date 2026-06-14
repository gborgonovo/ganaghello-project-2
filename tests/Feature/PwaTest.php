<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Blocco 9: la PWA è installabile. Smoke test su manifest, service worker e icone.
 */
class PwaTest extends TestCase
{
    public function test_manifest_valido(): void
    {
        $path = public_path('manifest.webmanifest');
        $this->assertFileExists($path);

        $m = json_decode(file_get_contents($path), true);
        $this->assertIsArray($m, 'Il manifest deve essere JSON valido');
        $this->assertSame('BiG-Log', $m['name']);
        $this->assertSame('standalone', $m['display']);
        $this->assertSame('/cruscotto', $m['start_url']);
        $this->assertNotEmpty($m['icons']);

        // C'è almeno un'icona 512 e una maskable.
        $sizes = array_column($m['icons'], 'sizes');
        $this->assertContains('512x512', $sizes);
        $purposes = array_column($m['icons'], 'purpose');
        $this->assertContains('maskable', $purposes);
    }

    public function test_service_worker_e_icone_presenti(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('icons/gp-logo_192.png'));
        $this->assertFileExists(public_path('icons/gp-logo_512.png'));
    }
}

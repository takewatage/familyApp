<?php

namespace Tests\Feature\Pwa;

use Tests\TestCase;

class ServiceWorkerTest extends TestCase
{
    private string $tmpPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpPath = sys_get_temp_dir().'/sw-test-'.uniqid().'.js';
        config(['pwa.service_worker_path' => $this->tmpPath]);
    }

    protected function tearDown(): void
    {
        if (is_file($this->tmpPath)) {
            unlink($this->tmpPath);
        }

        parent::tearDown();
    }

    public function test_returns_built_service_worker_as_javascript(): void
    {
        file_put_contents($this->tmpPath, 'self.skipWaiting();');

        $response = $this->get('/sw.js');

        $response->assertOk();
        $this->assertStringStartsWith('application/javascript', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
        $this->assertSame('self.skipWaiting();', $response->getFile()->getContent());
    }

    public function test_does_not_start_session(): void
    {
        file_put_contents($this->tmpPath, 'self.skipWaiting();');

        $response = $this->get('/sw.js');

        $response->assertOk();
        $response->assertCookieMissing(config('session.cookie'));
        $response->assertCookieMissing('XSRF-TOKEN');
    }

    public function test_returns_404_when_not_built(): void
    {
        $this->get('/sw.js')->assertNotFound();
    }
}

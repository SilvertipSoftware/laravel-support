<?php

use Carbon\Carbon;

require_once __DIR__ . '/ControllerTestCase.php';


class ConditionalGetTest extends ControllerTestCase {

    public function setUp(): void {
        parent::setUp();

        $this->lastModified = Carbon::now()->startOfDay()->toRfc7231String();
    }

    public function testResponseReturnsLastModified() {
        $this->get('/times/fresh')
            ->assertStatus(200)
            ->assertHeader('Last-Modified', $this->lastModified);
    }

    public function testRequestObeysLastModified() {
        $headers = [
            'If-Modified-Since' => $this->lastModified
        ];

        $this->get('/times/fresh', $headers)
            ->assertStatus(304);
    }

    public function testRequestWorksWithHeaderInFarPast() {
        $headers = [
            'If-Modified-Since' => Carbon::now()->subYears(5)->toRfc7231String()
        ];

        $this->get('/times/fresh', $headers)
            ->assertStatus(200)
            ->assertSee('fresh view content');
    }

    public function testRequestNotModified() {
        $headers = [
            'If-Modified-Since' => $this->lastModified
        ];

        $resp = $this->get('/times/stale', $headers)
            ->assertStatus(304);
    }
}

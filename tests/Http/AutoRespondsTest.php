<?php

require_once __DIR__ . '/ControllerTestCase.php';
require_once __DIR__ . '/../controllers/PackagedControllers.php';
require_once __DIR__ . '/../controllers/TestControllers.php';
require_once __DIR__ . '/../controllers/SubNamespaceControllers.php';

class AutoRespondsTest extends ControllerTestCase {

    public function testHtmlResponseUsesCorrectView() {
        $response = $this->get('/eyes')
            ->assertSuccessful();

        $this->assertEquals('eyes.index', $response->getContent());
    }

    public function testHtmlResponseUsesCorrectViewForJavascript() {
        $response = $this->get('/eyes', $this->acceptJavascriptHeaders())
            ->assertSuccessful();

        $this->assertStringContainsString('(function', $response->getContent());
        $this->assertStringContainsString('eyes.js.index', $response->getContent());
    }

    public function testHtmlResponseUsesCorrectViewForStream() {
        $response = $this->get('/eyes', $this->acceptStreamHeaders())
            ->assertSuccessful();

        $this->assertStringContainsString('<turbo-stream', $response->getContent());
        $this->assertStringContainsString('eyes.stream.index', $response->getContent());
    }

    public function testHtmlResponseUsesCorrectViewInSubNamespace() {
        $response = $this->get('/addresses')
            ->assertSuccessful();

        $this->assertEquals('companies.addresses.index', $response->getContent());
    }

    public function testHtmlResponseUsesCorrectViewInPackageNamespace() {
        $response = $this->get('/pirates')
            ->assertSuccessful();

        $this->assertEquals('some_vendor::pirates.index', $response->getContent());
    }

    public function testControllerVariablesAreShared() {
        foreach (['html', 'javascript', 'stream'] as $format) {
            $method = 'accept' . ucfirst($format) . 'Headers';
            $headers = $this->{$method}();

            $response = $this->get('/eyes/SOMEID', $headers)
                ->assertSuccessful()
                ->assertSee('Showing model SOMEID');

            if ($format != 'html') {
                $contentType = $response->headers->get('Content-Type');
                $this->assertStringContainsString($headers['Accept'], $contentType);
            }
        }
    }

    public function testHtmlRedirects() {
        $this->post('/eyes')
            ->assertRedirect('/eyes/NEWID');
    }

    public function testJavscriptRedirects() {
        $this->post('/eyes', [], $this->acceptJavascriptHeaders())
            ->assertSee('(function')
            ->assertSee('/eyes/NEWID');
    }
}

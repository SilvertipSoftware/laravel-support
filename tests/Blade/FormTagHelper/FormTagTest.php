<?php

namespace Tests\Blade;

use Illuminate\Support\Arr;
use Orchestra\Testbench\TestCase;
use SilvertipSoftware\LaravelSupport\Blade\FormTagHelper;
use Tests\TestSupport\HtmlAssertions;

class FormTagTest extends TestCase {
    use HtmlAssertions;
    use FormTagHelper {
        urlFor as originalUrlFor;
    }

    public function setUp(): void {
        static::$protectAgainstForgery = false;
    }

    public function tearDown(): void {
        static::$defaultEnforceUtf8 = true;
        static::$protectAgainstForgery = true;
    }

    public function testFormTag() {
        $actual = static::formTag();
        $expected = $this->wholeForm();
        $this->assertDomEquals($expected, $actual);
    }

    public function testFormTagMultipart() {
        $actual = static::formTag([], ['multipart' => true]);
        $expected = $this->wholeForm(['enctype' => true]);
        $this->assertDomEquals($expected, $actual);
    }

    public function testFormTagWithMethodPatch() {
        $actual = static::formTag([], ['method' => 'patch']);
        $expected = $this->wholeForm(['method' => 'patch']);
        $this->assertDomEquals($expected, $actual);
    }

    public function testFormTagWithMethodPut() {
        $actual = static::formTag([], ['method' => 'put']);
        $expected = $this->wholeForm(['method' => 'put']);
        $this->assertDomEquals($expected, $actual);
    }

    public function testFormTagWithMethodDelete() {
        $actual = static::formTag([], ['method' => 'delete']);
        $expected = $this->wholeForm(['method' => 'delete']);
        $this->assertDomEquals($expected, $actual);
    }

    public function testFormTagWithRemote() {
        $actual = static::formTag([], ['remote' => true]);
        $expected = $this->wholeForm(['remote' => true]);
        $this->assertDomEquals($expected, $actual);
    }

    public function testFormTagWithRemoteFalse() {
        $actual = static::formTag([], ['remote' => false]);
        $expected = $this->wholeForm();
        $this->assertDomEquals($expected, $actual);
    }

    public function testFormTagWithFalseUrlForOptions() {
        $actual = static::formTag(false);
        $expected = $this->wholeForm([], false);
        $this->assertDomEquals($expected, $actual);
    }

    public function testFormTagWithFalseAction() {
        $actual = static::formTag([], ['action' => false]);
        $expected = $this->wholeForm([], false);
        $this->assertDomEquals($expected, $actual);
    }

    public function testFormTagWithEnforceUtf8True() {
        $actual = static::formTag([], ['enforce_utf8' => true]);
        $expected = $this->wholeForm(['enforce_utf8' => true]);
        $this->assertDomEquals($expected, $actual);
    }

    public function testFormTagWithEnforceUtf8False() {
        $actual = static::formTag([], ['enforce_utf8' => false]);
        $expected = $this->wholeForm(['enforce_utf8' => false]);
        $this->assertDomEquals($expected, $actual);
    }

    public function testFormTagWithDefaultEnforceUtf8False() {
        static::$defaultEnforceUtf8 = false;

        $actual = static::formTag([]);
        $expected = $this->wholeForm(['enforce_utf8' => false]);
        $this->assertDomEquals($expected, $actual);
    }

    public function testFormTagWithDefaultEnforceUtf8True() {
        static::$defaultEnforceUtf8 = true;

        $actual = static::formTag([]);
        $expected = $this->wholeForm(['enforce_utf8' => true]);
        $this->assertDomEquals($expected, $actual);
    }

    public static function urlFor($options = null) {
        if (is_array($options)) {
            return 'http://www.example.com';
        }

        return static::originalUrlFor($options);
    }

    private function formText($action = "http://www.example.com", $options = []) {
        foreach (['remote', 'enctype', 'html_class', 'id', 'method'] as $var) {
            extract([
                $var => Arr::get($options, $var)
            ]);
        }

        $method = $method == 'get' ? 'get' : 'post';

        return '<form accept-charset="UTF-8"'
            . ($action ? (' action="' . $action . '"') : '')
            . ($enctype ? ' enctype="multipart/form-data"' : '')
            . ($remote ? ' data-remote="true"' : '')
            . ($html_class ? (' class="' . $html_class . '"') : '')
            . ($id ? (' id="' . $id . '"') : '')
            . ' method="' . $method . '">';
    }

    private function hiddenFields($options = []) {
        $method = Arr::get($options, 'method');
        $enforceUtf8 = Arr::get($options, 'enforce_utf8', true);

        $out = '';

        if ($enforceUtf8) {
            $out .= '<input name="utf8" type="hidden" value="&#x2713;" autocomplete="off" />';
        }

        if ($method && !in_array($method, ['get', 'post'])) {
            $out .= '<input name="_method" type="hidden" value="' . $method . '" autocomplete="off" />';
        }

        return $out;
    }

    private function wholeForm($options = [], $action = null) {
        $out = $this->formText($action !== null ? $action : 'http://www.example.com', $options)
            . $this->hiddenFields($options);

        return $out;
    }
}

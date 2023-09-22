<?php

namespace Tests\Blade\Directives;

use Orchestra\Testbench\TestCase;
use SilvertipSoftware\LaravelSupport\Blade\FormBuilder;
use SilvertipSoftware\LaravelSupport\Blade\ViewSupport;
use Tests\TestSupport\HtmlAssertions;

class FormBuilderTest extends TestCase {
    use DirectiveTestSupport,
        HtmlAssertions;

    public function setUp(): void {
        parent::setUp();
        ViewSupport::$protectAgainstForgery = false;
        ViewSupport::$formWithGeneratesRemoteForms = false;
        ViewSupport::$defaultEnforceUtf8 = false;
        $this->createFixtures();
    }

    public function tearDown(): void {
        parent::tearDown();
        ViewSupport::$protectAgainstForgery = true;
        ViewSupport::$formWithGeneratesRemoteForms = true;
        ViewSupport::$defaultEnforceUtf8 = true;
    }

    public function testBasicBuilderDirectivesExist() {
        foreach (FormBuilder::$fieldHelpers as $helper) {
            $this->assertDirectiveExists($helper);
        }

        $this->assertDirectiveExists('button');
        $this->assertDirectiveExists('select');
        $this->assertDirectiveExists('submit');
    }

    public function testFormId() {
        $this->assertDirectiveExists('id');

        $expected = '<form accept-charset="UTF-8" action="/posts" id="new_post" method="post">'
            . '<span>Hello there</span>'
            . '</form>'
            . '<div form="new_post">form reference</div>';

        $blade = "@formWith(model: \$newPost, options: ['id' => 'new_post'] as \$f)\n"
            . "<span>Hello there</span>\n"
            . "@section('sticky_footer')\n"
            . '<div form="@id($f)">form reference</div>' . "\n"
            . "@endsection\n"
            . "@endBlock\n"
            . "@yield('sticky_footer')";

        $this->assertBlade($expected, $blade);
    }

    public function testFieldId() {
        $this->assertDirectiveExists('fieldId');

        $expected = '<form accept-charset="UTF-8" action="/posts" method="post">'
            . '<div>post_title wuz here</div>'
            . '</form>';

        $blade = "@formWith(model: \$newPost as \$f)\n"
            . "<div>@fieldId(\$f, 'title') wuz here</div>"
            . "@endBlock";

        $this->assertBlade($expected, $blade);
    }

    public function testFieldName() {
        $this->assertDirectiveExists('fieldName');

        $expected = '<form accept-charset="UTF-8" action="/posts" method="post">'
            . '<div>post[title] wuz here</div>'
            . '</form>';

        $blade = "@formWith(model: \$newPost as \$f)\n"
            . "<div>@fieldName(\$f, 'title') wuz here</div>\n"
            . "@endBlock";

        $this->assertBlade($expected, $blade);
    }

    public function testCollectionSelect() {
        $this->assertDirectiveExists('collectionSelect');
    }

    public function testCollectionRadioButtons() {
        $this->assertDirectiveExists('collectionRadioButtons');
    }

    public function testCollectionCheckBoxes() {
        $this->assertDirectiveExists('collectionCheckBoxes');
    }
}

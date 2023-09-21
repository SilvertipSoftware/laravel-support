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
            $this->assertDirectiveExists('bld' . ucfirst($helper));
        }

        $this->assertDirectiveExists('bldButton');
        $this->assertDirectiveExists('bldSelect');
        $this->assertDirectiveExists('bldSubmit');
    }

    public function testFormId() {
        $this->assertDirectiveExists('bldId');
        $expected = '<form accept-charset="UTF-8" action="/posts" id="new_post" method="post">'
            . '<span>Hello there</span>'
            . '</form>'
            . '<div form="new_post">form reference</div>';

        $blade = "@formWith(model: \$newPost, options: ['id' => 'new_post'] as \$f)\n"
            . "<span>Hello there</span>\n"
            . "@section('sticky_footer')\n"
            . '<div form="@bldId($f)">form reference</div>' . "\n"
            . "@endsection\n"
            . "@endFormWith\n"
            . "@yield('sticky_footer')";

        $this->assertBlade($expected, $blade);
    }

    public function testFieldId() {
        $this->assertDirectiveExists('bldFieldId');

        $expected = '<form accept-charset="UTF-8" action="/posts" method="post">'
            . '<div>post_title wuz here</div>'
            . '</form>';

        $blade = "@formWith(model: \$newPost as \$f)\n"
            . "<div>@bldFieldId(\$f, 'title') wuz here</div>"
            . "@endFormWith";

        $this->assertBlade($expected, $blade);
    }

    public function testFieldName() {
        $this->assertDirectiveExists('bldFieldName');

        $expected = '<form accept-charset="UTF-8" action="/posts" method="post">'
            . '<div>post[title] wuz here</div>'
            . '</form>';

        $blade = "@formWith(model: \$newPost as \$f)\n"
            . "<div>@bldFieldName(\$f, 'title') wuz here</div>\n"
            . "@endFormWith";

        $this->assertBlade($expected, $blade);
    }

    public function testCollectionSelect() {
        $this->assertDirectiveExists('bldCollectionSelect');
    }

    public function testCollectionRadioButtons() {
        $this->assertDirectiveExists('bldCollectionRadioButtons');
    }

    public function testCollectionCheckBoxes() {
        $this->assertDirectiveExists('bldCollectionCheckBoxes');
    }
}

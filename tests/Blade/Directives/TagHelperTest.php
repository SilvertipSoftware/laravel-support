<?php

namespace Tests\Blade\Directives;

use Illuminate\View\ViewException;
use Orchestra\Testbench\TestCase;
use Tests\TestSupport\HtmlAssertions;

class TagHelperTest extends TestCase {
    use DirectiveTestSupport,
        HtmlAssertions;

    public function setUp(): void {
        parent::setUp();
        $this->createFixtures();
    }

    public function testTag() {
        $this->assertDirectiveExists('tag');
        $this->assertDirectiveNotExists('endTag');

        $this->assertBlade('<span></span>', "@tag('span')");
    }

    public function testTagWithOptions() {
        $this->assertBlade(
            '<span class="red" title="Hello"></span>',
            "@tag('span', ['class' => 'red', 'title' => 'Hello'])"
        );
    }

    public function testTagOnlyOpener() {
        $this->assertEquals(
            '<span id="start">',
            $this->blade("@tag('span', ['id' => 'start'], true)")
        );
    }

    public function testTagEscaping() {
        $this->assertBlade(
            '<span title="Play&gt;">',
            "@tag('span', ['title' => 'Play>'])"
        );
    }

    public function testTokenList() {
        $this->assertDirectiveExists('tokenList');

        $this->assertEquals('one two', $this->blade("@tokenList('one', 'two')"));
        $this->assertEquals('one two', $this->blade("@tokenList(['one', 'two'])"));
        $this->assertEquals('one two', $this->blade("@tokenList('one', 'two', 'one')"));
        $this->assertEquals('one two thr-ee', $this->blade("@tokenList('one', 'two', 'thr-ee')"));
    }

    public function testClassNames() {
        $this->assertDirectiveExists('classNames');

        $this->assertEquals('one two', $this->blade("@classNames('one', 'two')"));
        $this->assertEquals('one two', $this->blade("@classNames(['one', 'two'])"));
        $this->assertEquals('one two', $this->blade("@classNames('one', 'two', 'one')"));
        $this->assertEquals('one two thr-ee', $this->blade("@classNames('one', 'two', 'thr-ee')"));
    }

    public function testCloseTag() {
        $this->assertDirectiveExists('closeTag');

        $this->assertEquals('</svg>', $this->blade("@closeTag('svg')"));
    }

    public function testContentTag() {
        $this->assertDirectiveExists('contentTag');
        $this->assertDirectiveNotExists('bldContentTag');

        $this->assertBlade(
            '<span>Hello span</span>',
            "@contentTag('span', 'Hello span')"
        );
    }

    public function testContentTagWithOptions() {
        $this->assertBlade(
            '<span class="important">Hello span</span>',
            "@contentTag('span', 'Hello span', ['class' => 'important'])"
        );
    }

    public function testContentTagDoesEscapeWithString() {
        $this->assertBlade(
            '<span>Play &gt;</span>',
            "@contentTag('span', 'Play >')"
        );
    }

    public function testContentTagForceNoEscape() {
        $this->assertBlade(
            '<span><b>Hello bold</b></span>',
            "@contentTag('span', '<b>Hello bold</b>', [], false)"
        );
    }

    public function testContentTagWithBlock() {
        $this->assertBlade(
            '<span>Hello </span>',
            "@contentTag('span' as \$_)Hello @endBlock"
        );
    }

    public function testContentTagWithBlockAndOptions() {
        $this->assertBlade(
            '<span class="important">Hello </span>',
            "@contentTag('span', ['class' => 'important'] as \$_)Hello @endBlock"
        );
    }

    public function testContentTagDoesNotEscapeForBladeWithBlock() {
        $this->assertBlade(
            '<span><b>Hello bold</b></span>',
            "@contentTag('span' as \$_)<b>Hello bold</b>@endBlock"
        );
    }

    public function testContentTagWithBlockNeedsEndDirective() {
        $this->expectException(ViewException::class);

        $this->blade("@contentTag('span' as \$_)No end");
    }

    public function testCdataSection() {
        $this->assertDirectiveExists('cdataSection');
        $this->assertDirectiveNotExists('bldCdataSection');

        $this->assertEquals(
            '<![CDATA[Play >]]>',
            $this->blade("@cdataSection('Play >')")
        );

        $this->assertEquals(
            '<![CDATA[[P]lay]]>',
            $this->blade("@cdataSection('[P]lay')")
        );

        $this->assertEquals(
            '<![CDATA[Play [[p]]]]><![CDATA[>]]>',
            $this->blade("@cdataSection('Play [[p]]>')")
        );
    }
}

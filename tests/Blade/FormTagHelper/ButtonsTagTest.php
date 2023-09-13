<?php

namespace Tests\Blade;

use Orchestra\Testbench\TestCase;
use SilvertipSoftware\LaravelSupport\Blade\FormTagHelper;
use Tests\TestSupport\HtmlAssertions;

class ButtonsTagTest extends TestCase {
    use HtmlAssertions,
        FormTagHelper;

    public function tearDown(): void {
        static::$automaticallyDisableSubmitTag = true;
    }

    public function testSubmitTag() {
        $expected = '<input name="commit" data-disable-with="Saving..." type="submit" value="Save" />';
        $opts = [
            'data' => [
                'disable-with' => 'Saving...'
            ]
        ];

        $this->assertDomEquals($expected, static::submitTag('Save', $opts));
    }

    public function testEmptySubmitTag() {
        $expected = '<input data-disable-with="Save" name="commit" type="submit" value="Save" />';
        $this->assertDomEquals($expected, static::submitTag('Save'));
    }

    public function testEmptySubmitTagWithOptOut() {
        static::$automaticallyDisableSubmitTag = false;

        $expected = '<input data="" name="commit" type="submit" value="Save" />';
        $this->assertDomEquals($expected, static::submitTag('Save'));
    }

    public function testEmptySubmitTagWithOptOutAndExplicitDisabling() {
        static::$automaticallyDisableSubmitTag = false;

        $expected = '<input data="" name="commit" type="submit" value="Save" />';
        $opts = [
            'data' => [
                'disable-with' => false
            ]
        ];

        $this->assertDomEquals($expected, static::submitTag('Save'));
    }

    public function testEmptySubmitTagWithDataDisableString() {
        $expected = '<input data-disable-with="Processing..."'
            . ' data-confirm="Are you sure?"'
            . ' name="commit" type="submit" value="Save" />';
        $opts = [
            'data-disable-with' => 'Processing...',
            'data-confirm' => 'Are you sure?',
        ];

        $this->assertDomEquals($expected, static::submitTag('Save', $opts));
    }

    public function testEmptySubmitTagWithDataDisableBoolean() {
        $expected = '<input data="" data-confirm="Are you sure?"'
            . ' name="commit" type="submit" value="Save" />';
        $opts = [
            'data-disable-with' => false,
            'data-confirm' => 'Are you sure?',
        ];

        $this->assertDomEquals($expected, static::submitTag('Save', $opts));
    }

    public function testEmptySubmitTagWithDataHashDisableBoolean() {
        $expected = '<input data-confirm="Are you sure?"'
            . ' name="commit" type="submit" value="Save" />';
        $opts = [
            'data' => [
                'disable-with' => false,
                'confirm' => 'Are you sure?',
            ]
        ];

        $this->assertDomEquals($expected, static::submitTag('Save', $opts));
    }

    public function testButtonTag() {
        $expected = '<button name="button" type="submit">Button</button>';

        $this->assertDomEquals($expected, static::buttonTag());
    }

    public function testButtonTagWithSubmitType() {
        $expected = '<button name="button" type="submit">Save</button>';

        $this->assertDomEquals($expected, static::buttonTag('Save', ['type' => 'submit']));
    }

    public function testButtonTagWithButtonType() {
        $expected = '<button name="button" type="button">Button</button>';

        $this->assertDomEquals($expected, static::buttonTag('Button', ['type' => 'button']));
    }

    public function testButtonTagWithResetType() {
        $expected = '<button name="button" type="reset">Reset</button>';

        $this->assertDomEquals($expected, static::buttonTag('Reset', ['type' => 'reset']));
    }

    public function testButtonTagWithDisabledOption() {
        $expected = '<button name="button" type="reset" disabled="disabled">Reset</button>';

        $this->assertDomEquals($expected, static::buttonTag('Reset', ['type' => 'reset', 'disabled' => true]));
    }

    public function testButtonTagEscapesContent() {
        $expected = '<button name="button" type="submit">&lt;b&gt;Button&lt;/b&gt;</button>';

        $this->assertDomEquals($expected, static::buttonTag('<b>Button</b>'));
    }

    public function testButtonTagWithBlock() {
        $expected = '<button name="button" type="submit">Content</button>';

        $this->assertDomEquals($expected, static::buttonTag(function () {
            return 'Content';
        }));
    }

    public function testButtonTagWithBlockAndOptions() {
        $expected = '<button name="temptation" type="button"><strong>Do not press!</strong></button>';
        $opts = [
            'name' => 'temptation',
            'type' => 'button'
        ];
        $fn = function () {
            return static::contentTag('strong', 'Do not press!');
        };

        $this->assertDomEquals($expected, static::buttonTag($opts, $fn));
    }

    public function testButtonTagWithConfirm() {
        $expected = '<button name="button" data-confirm="Are you sure?" type="submit">Save</button>';

        $this->assertDomEquals($expected, static::buttonTag('Save', ['data' => ['confirm' => 'Are you sure?']]));
    }

    public function testButtonTagWithDisableWith() {
        $expected = '<button name="button" data-disable-with="Wait..." type="submit">Save</button>';

        $this->assertDomEquals($expected, static::buttonTag('Save', ['data' => ['disable-with' => 'Wait...']]));
    }
}

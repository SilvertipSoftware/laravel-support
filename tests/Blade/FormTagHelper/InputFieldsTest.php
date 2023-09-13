<?php

namespace Tests\Blade;

use Orchestra\Testbench\TestCase;
use SilvertipSoftware\LaravelSupport\Blade\FormTagHelper;
use Tests\TestSupport\HtmlAssertions;

class InputFieldsTest extends TestCase {
    use HtmlAssertions,
        FormTagHelper;

    public function testCheckBoxTag() {
        $actual = static::checkBoxTag('admin');
        $expected = '<input type="checkbox" id="admin" name="admin" value="1" />';
        $this->assertDomEquals($expected, $actual);
        $this->assertValidHtmlId(static::checkBoxTag('item[][title]'));
    }

    public function testCheckBoxTagDisabled() {
        $actual = static::checkBoxTag('admin', "1", false, ['disabled' => true]);
        $expected = '<input type="checkbox" id="admin" name="admin" value="1" disabled="disabled" />';
        $this->assertDomEquals($expected, $actual);
    }

    public function testCheckBoxTagDefaultChecked() {
        $actual = static::checkBoxTag('admin', "1", true);
        $expected = '<input type="checkbox" id="admin" name="admin" value="1" checked="checked" />';
        $this->assertDomEquals($expected, $actual);
    }

    public function testRadioButtonTag() {
        $actual = static::radioButtonTag('num_people', 5);
        $expected = '<input type="radio" id="num_people_5" name="num_people" value="5" />';
        $this->assertDomEquals($expected, $actual);
        $this->assertValidHtmlId(static::radioButtonTag('item[][title]', 'apache2.2'));
    }

    public function testRadioButtonTagDisabled() {
        $actual = static::radioButtonTag('num_people', 5, false, ['disabled' => true]);
        $expected = '<input type="radio" id="num_people_5" name="num_people" value="5" disabled="disabled" />';
        $this->assertDomEquals($expected, $actual);
    }

    public function testRadioButtonTagNestedName() {
        $actual = static::radioButtonTag('computer[status]', 'working');
        $expected = '<input type="radio" id="computer_status_working" name="computer[status]" value="working" />';
        $this->assertDomEquals($expected, $actual);
    }

    public function testTextFieldTag() {
        $actual = static::textFieldTag('title', 'Hello!');
        $expected = '<input id="title" name="title" type="text" value="Hello!" />';
        $this->assertDomEquals($expected, $actual);
    }

    public function testTextFieldTagClassString() {
        $actual = static::textFieldTag('title', 'Hello!', ['class' => 'admin']);
        $expected = '<input class="admin" id="title" name="title" type="text" value="Hello!" />';
        $this->assertDomEquals($expected, $actual);
    }

    public function testTextFieldTagSizeSymbol() {
        $actual = static::textFieldTag('title', 'Hello!', ['size' => 75]);
        $expected = '<input id="title" name="title" size="75" type="text" value="Hello!" />';
        $this->assertDomEquals($expected, $actual);
    }

    public function testTextFieldTagSizeString() {
        $actual = static::textFieldTag('title', 'Hello!', ['size' => '75']);
        $expected = '<input id="title" name="title" size="75" type="text" value="Hello!" />';
        $this->assertDomEquals($expected, $actual);
    }

    public function testTextFieldTagMaxlength() {
        $actual = static::textFieldTag('title', 'Hello!', ['maxlength' => 75]);
        $expected = '<input id="title" name="title" maxlength="75" type="text" value="Hello!" />';
        $this->assertDomEquals($expected, $actual);
    }

    public function testTextFieldTagMaxlengthString() {
        $actual = static::textFieldTag('title', 'Hello!', ['maxlength' => '75']);
        $expected = '<input id="title" name="title" maxlength="75" type="text" value="Hello!" />';
        $this->assertDomEquals($expected, $actual);
    }

    public function testTextFieldTagDisabled() {
        $actual = static::textFieldTag('title', 'Hello!', ['disabled' => true]);
        $expected = '<input id="title" name="title" disabled="disabled" type="text" value="Hello!" />';
        $this->assertDomEquals($expected, $actual);
    }

    public function testTextFieldTagWithPlaceholderOption() {
        $actual = static::textFieldTag('title', 'Hello!', ['placeholder' => 'Enter search term...']);
        $expected = '<input id="title" name="title" placeholder="Enter search term..." type="text" value="Hello!" />';
        $this->assertDomEquals($expected, $actual);
    }

    public function testTextFieldTagWithMultipleOptions() {
        $actual = static::textFieldTag('title', 'Hello!', ['size' => 70, 'maxlength' => 80]);
        $expected = '<input id="title" name="title" size="70" maxlength="80" type="text" value="Hello!" />';
        $this->assertDomEquals($expected, $actual);
    }

    public function testTextFieldTagIdSanitized() {
        $this->assertValidHtmlId(static::textFieldTag('item[][title]'));
    }

    public function testSearchFieldTag() {
        $actual = static::searchFieldTag('query');
        $expected = '<input id="query" name="query" type="search" />';
        $this->assertDomEquals($expected, $actual);
        $this->assertValidHtmlId(static::searchFieldTag('item[][title]'));
    }

    public function testEmailFieldTag() {
        $actual = static::emailFieldTag('address');
        $expected = '<input id="address" name="address" type="email" />';
        $this->assertDomEquals($expected, $actual);
        $this->assertValidHtmlId(static::emailFieldTag('item[][title]'));
    }

    public function testHiddenFieldTag() {
        $actual = static::hiddenFieldTag("id", 3);
        $expected = '<input id="id" name="id" type="hidden" value="3" autocomplete="off" />';
        $this->assertDomEquals($expected, $actual);
        $this->assertValidHtmlId(static::hiddenFieldTag('item[][title]'));
    }

    public function testNumberFieldTag() {
        $actual = static::numberFieldTag('qty', null, ['range' => [1, 9]]);
        $expected = '<input id="qty" name="qty" type="number" min="1" max="9" />';
        $this->assertDomEquals($expected, $actual);
        $this->assertValidHtmlId(static::numberFieldTag('item[][title]'));
    }

    public function testRangeFieldTag() {
        $actual = static::rangeFieldTag('qty', null, ['range' => [1, 9]]);
        $expected = '<input id="qty" name="qty" type="range" min="1" max="9" />';
        $this->assertDomEquals($expected, $actual);
        $this->assertValidHtmlId(static::rangeFieldTag('item[][title]'));
    }

    public function testPasswordFieldTag() {
        $actual = static::passwordFieldTag();
        $expected = '<input id="password" name="password" type="password" />';
        $this->assertDomEquals($expected, $actual);
        $this->assertValidHtmlId(static::passwordFieldTag('item[][title]'));
    }

    public function testWeekFieldTag() {
        $actual = static::weekFieldTag('birthday');
        $expected = '<input id="birthday" name="birthday" type="week" />';
        $this->assertDomEquals($expected, $actual);
        $this->assertValidHtmlId(static::weekFieldTag('item[][title]'));
    }

    public function testMonthFieldTag() {
        $actual = static::monthFieldTag('birthday');
        $expected = '<input id="birthday" name="birthday" type="month" />';
        $this->assertDomEquals($expected, $actual);
        $this->assertValidHtmlId(static::monthFieldTag('item[][title]'));
    }

    public function testDateFieldTag() {
        $actual = static::dateFieldTag('birthday');
        $expected = '<input id="birthday" name="birthday" type="date" />';
        $this->assertDomEquals($expected, $actual);
        $this->assertValidHtmlId(static::dateFieldTag('item[][title]'));
    }

    public function testTimeFieldTag() {
        $actual = static::timeFieldTag('wake');
        $expected = '<input id="wake" name="wake" type="time" />';
        $this->assertDomEquals($expected, $actual);
        $this->assertValidHtmlId(static::timeFieldTag('item[][title]'));
    }

    public function testDatetimeFieldTag() {
        $actual = static::datetimeFieldTag('appt');
        $expected = '<input id="appt" name="appt" type="datetime-local" />';
        $this->assertDomEquals($expected, $actual);
        $this->assertValidHtmlId(static::datetimeFieldTag('item[][title]'));
    }

    public function testColorFieldTag() {
        $actual = static::colorFieldTag('car');
        $expected = '<input id="car" name="car" type="color" />';
        $this->assertDomEquals($expected, $actual);
        $this->assertValidHtmlId(static::colorFieldTag('item[][title]'));
    }

    public function testUrlFieldTag() {
        $actual = static::urlFieldTag('url');
        $expected = '<input id="url" name="url" type="url" />';
        $this->assertDomEquals($expected, $actual);
        $this->assertValidHtmlId(static::urlFieldTag('item[][title]'));
    }

    public function testTextAreaTag() {
        $actual = static::textAreaTag('body', 'hello world');
        $expected = '<textarea id="body" name="body">' . "\n" . 'hello world</textarea>';
        $this->assertDomEquals($expected, $actual);
        $this->assertValidHtmlId(static::textAreaTag('item[][title]'));
    }

    public function testTextAreaTagSize() {
        $actual = static::textAreaTag('body', 'hello world', ['size' => '20x40']);
        $expected = '<textarea cols="20" id="body" name="body" rows="40">' . "\n" . 'hello world</textarea>';
        $this->assertDomEquals($expected, $actual);
    }

    public function testTextAreaTagSizeIgnoredIfInteger() {
        $actual = static::textAreaTag('body', 'hello world', ['size' => 20]);
        $expected = '<textarea id="body" name="body">' . "\n" . 'hello world</textarea>';
        $this->assertDomEquals($expected, $actual);
    }

    public function testTextAreaTagEscapesContent() {
        $actual = static::textAreaTag('body', '<b>hello world</b>');
        $expected = '<textarea id="body" name="body">' . "\n" . '&lt;b&gt;hello world&lt;/b&gt;</textarea>';
        $this->assertDomEquals($expected, $actual);
    }

    public function testTextAreaTagUnescapedContent() {
        $actual = static::textAreaTag('body', '<b>hello world</b>', ['escape' => false]);
        $expected = '<textarea id="body" name="body">' . "\n" . '<b>hello world</b></textarea>';
        $this->assertDomEquals($expected, $actual);
    }

    public function testTextAreaTagUnescapedNullContent() {
        $actual = static::textAreaTag('body', null, ['escape' => false]);
        $expected = '<textarea id="body" name="body">' . "\n" . '</textarea>';
        $this->assertDomEquals($expected, $actual);
    }
}

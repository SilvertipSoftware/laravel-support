<?php

namespace Tests\Blade\FormHelper;

use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Stringable;
use Orchestra\Testbench\TestCase;
use SilvertipSoftware\LaravelSupport\Blade\FormHelper;
use Tests\TestSupport\HtmlAssertions;

class InputFieldsTest extends TestCase {
    use HtmlAssertions,
        FormHelper,
        FormHelperFixtures;

    public function setUp(): void {
        parent::setUp();
        static::$protectAgainstForgery = false;

        $this->createFixtures();
    }

    public function tearDown(): void {
        parent::tearDown();
        static::$multipleFileFieldIncludeHidden = false;
        static::$protectAgainstForgery = true;
    }

    public function testTextFieldPlaceholderWithoutTranslation() {
        Lang::setLocale('placeholder');

        $this->assertDomEquals(
            '<input id="post_body" name="post[body]" placeholder="Body" type="text" value="This is a post" />',
            static::textField('post', 'body', ['placeholder' => true, 'object' => $this->post])
        );
    }

    public function testTextFieldPlaceholderWithTranslation() {
        Lang::setLocale('placeholder');

        $expected = '<input id="post_title" name="post[title]" placeholder="What is this about?" type="text"'
            . ' value="Hello World" />';

        $this->assertDomEquals(
            $expected,
            static::textField('post', 'title', ['placeholder' => true, 'object' => $this->post])
        );
    }

    public function testTextFieldPlaceholderWithTranslationAndToModel() {
        Lang::setLocale('placeholder');

        $expected = '<input id="post_delegator_title" name="post_delegator[title]"'
            . ' placeholder="Delegate model_name title" type="text" value="Hello World" />';

        $this->assertDomEquals(
            $expected,
            static::textField('post_delegator', 'title', ['placeholder' => true, 'object' => $this->postDelegator])
        );
    }

    public function testTextFieldPlaceholderWithHumanAttributeName() {
        Lang::setLocale('placeholder');

        $expected = '<input id="post_cost" name="post[cost]" placeholder="Total cost" type="text"/>';

        $this->assertDomEquals(
            $expected,
            static::textField('post', 'cost', ['placeholder' => true, 'object' => $this->post])
        );
    }

    public function testTextFieldPlaceholderWithHumanAttributeNameAndToModel() {
        $expected = '<input id="post_delegator_title" name="post_delegator[title]"'
            . ' placeholder="Delegate Title" type="text" value="Hello World" />';

        $this->assertDomEquals(
            $expected,
            static::textField('post_delegator', 'title', ['placeholder' => true, 'object' => $this->postDelegator])
        );
    }

    public function testTextFieldPlaceholderWithStringValue() {
        Lang::setLocale('placeholder');

        $this->assertDomEquals(
            '<input id="post_cost" name="post[cost]" placeholder="HOW MUCH?" type="text" />',
            static::textField('post', 'cost', ['placeholder' => 'HOW MUCH?', 'object' => $this->post])
        );
    }

    public function testTextFieldPlaceholderWithHumanAttributeNameAndValue() {
        Lang::setLocale('placeholder');

        $this->assertDomEquals(
            '<input id="post_cost" name="post[cost]" placeholder="Pounds" type="text" />',
            static::textField('post', 'cost', ['placeholder' => new Stringable('uk'), 'object' => $this->post])
        );
    }

    public function testTextFieldPlaceholderWithTranslationAndValue() {
        Lang::setLocale('placeholder');

        $expected = '<input id="post_written_on" name="post[written_on]" placeholder="Escrito en"'
            . ' type="text" value="2004-06-15" />';
        $this->assertDomEquals(
            $expected,
            static::textField('post', 'written_on', [
                'placeholder' => new Stringable('spanish'),
                'object' => $this->post
            ])
        );
    }

    public function testTextFieldPlaceholderWithTranslationAndNestedAttributes() {
        Lang::setLocale('placeholder');

        $rendered = static::formWith(
            model: $this->post,
            options: ['html' => ['id' => 'create-post']],
            block: function ($f) {
                return $f->fieldsFor('comments', null, [], function ($cf) {
                    return $cf->textField('body', ['placeholder' => true]);
                });
            }
        );

        $expected = $this->wholeForm('/posts/123', 'create-post', null, ['method' => 'patch'], function () {
            return '<input id="post_comments_attributes_0_body" name="post[comments_attributes][0][body]"'
                . ' placeholder="Write body here" type="text" />';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testTextFieldPlaceholderWithTranslationFallbackAndNestedAttributes() {
        Lang::setLocale('placeholder');

        $rendered = static::formWith(
            model: $this->post,
            options: ['html' => ['id' => 'create-post']],
            block: function ($f) {
                return $f->fieldsFor('tags', null, [], function ($cf) {
                    return $cf->textField('value', ['placeholder' => true]);
                });
            }
        );

        $expected = $this->wholeForm('/posts/123', 'create-post', null, ['method' => 'patch'], function () {
            return '<input id="post_tags_attributes_0_value" name="post[tags_attributes][0][value]"'
                . ' placeholder="Tag" type="text" value="new tag" />';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testTextField() {
        $this->assertDomEquals(
            '<input id="post_title" name="post[title]" type="text" value="Hello World" />',
            static::textField("post", "title", ['object' => $this->post])
        );
        $this->assertDomEquals(
            '<input id="post_title" name="post[title]" type="password" />',
            static::passwordField("post", "title", ['object' => $this->post])
        );
        $this->assertDomEquals(
            '<input id="post_title" name="post[title]" type="password" value="Hello World" />',
            static::passwordField("post", "title", ['value' => $this->post->title])
        );
        $this->assertDomEquals(
            '<input id="person_name" name="person[name]" type="password" />',
            static::passwordField("person", "name")
        );
    }

    public function testTextFieldWithEscapes() {
        $this->post->title = '<b>Hello World</b>';
        $this->assertDomEquals(
            '<input id="post_title" name="post[title]" type="text" value="&lt;b&gt;Hello World&lt;/b&gt;" />',
            static::textField('post', 'title', ['object' => $this->post])
        );
    }

    public function testTextFieldWithHtmlEntities() {
        $this->post->title = 'The HTML Entity for & is &amp;';
        $this->assertDomEquals(
            '<input id="post_title" name="post[title]" type="text" value="The HTML Entity for &amp; is &amp;amp;" />',
            static::textField('post', 'title', ['object' => $this->post])
        );
    }

    public function testTextFieldWithOptions() {
        $expected = '<input id="post_title" name="post[title]" size="35" type="text" value="Hello World" />';
        $this->assertDomEquals(
            $expected,
            static::textField('post', 'title', ['size' => 35, 'object' => $this->post])
        );
    }

    public function testTextFieldAssumingSize() {
        $expected = '<input id="post_title" name="post[title]" maxlength="35" size="35" type="text"'
            . ' value="Hello World" />';

        $this->assertDomEquals(
            $expected,
            static::textField('post', 'title', ['maxlength' => 35, 'object' => $this->post])
        );
    }

    public function testTextFieldRemovingSize() {
        $expected = '<input id="post_title" name="post[title]" maxlength="35" type="text" value="Hello World" />';
        $this->assertDomEquals(
            $expected,
            static::textField('post', 'title', ['maxlength' => 35, 'size' => null, 'object' => $this->post])
        );
    }

    public function testTextFieldWithNullValue() {
        $this->assertDomEquals(
            '<input id="post_title" name="post[title]" type="text" />',
            static::textField('post', 'title', ['value' => null])
        );
    }

    public function testTextFieldWithNullName() {
        $this->assertDomEquals(
            '<input id="post_title" type="text" value="Hello World" />',
            static::textField('post', 'title', ['name' => null, 'object' => $this->post])
        );
    }

    public function testTextFieldDoesntChangeParamValues() {
        $objectName = 'post[]';
        $expected = '<input id="post_123_title" name="post[123][title]" type="text" value="Hello World" />';
        $this->assertDomEquals($expected, static::textField($objectName, 'title', ['object' => $this->post]));
    }

    public function testTextFieldWithCustomType() {
        $this->assertDomEquals(
            '<input id="user_email" name="user[email]" type="email" />',
            static::textField('user', 'email', ['type' => 'email'])
        );
    }

    public function testCheckBoxIsHtmlString() {
        $this->assertInstanceOf(HtmlString::class, static::checkBox('post', 'secret'));
    }

    public function testCheckBoxCheckedIfObjectValueSameAsCheckedValue() {
        $expected = '<input name="post[secret]" type="hidden" value="0" autocomplete="off" />'
            . '<input checked="checked" id="post_secret" name="post[secret]" type="checkbox" value="1" />';

        $this->assertDomEquals($expected, static::checkBox('post', 'secret', ['object' => $this->post]));
    }

    public function testCheckBoxUncheckedIfObjectValueSameAsUncheckedValue() {
        $this->post->secret = 0;

        $expected = '<input name="post[secret]" type="hidden" value="0" autocomplete="off" />'
            . '<input id="post_secret" name="post[secret]" type="checkbox" value="1" />';

        $this->assertDomEquals($expected, static::checkBox('post', 'secret', ['object' => $this->post]));
    }

    public function testCheckBoxCheckedIfCheckedOptionPresent() {
        $expected = '<input name="post[secret]" type="hidden" value="0" autocomplete="off" />'
            . '<input checked="checked" id="post_secret" name="post[secret]" type="checkbox" value="1" />';

        $this->assertDomEquals($expected, static::checkBox('post', 'secret', ['checked' => 'checked']));
    }

    public function testCheckBoxCheckedIfObjectValueTrue() {
        $this->post->secret = true;

        $expected = '<input name="post[secret]" type="hidden" value="0" autocomplete="off" />'
            . '<input checked="checked" id="post_secret" name="post[secret]" type="checkbox" value="1" />';

        $this->assertDomEquals($expected, static::checkBox('post', 'secret', ['object' => $this->post]));
    }

    public function testCheckBoxCheckedIfObjectValueIncludesCheckedValue() {
        $this->post->secret = ['0'];
        $expected = '<input name="post[secret]" type="hidden" value="0" autocomplete="off" />'
            . '<input id="post_secret" name="post[secret]" type="checkbox" value="1" />';
        $this->assertDomEquals($expected, static::checkBox('post', 'secret', ['object' => $this->post]));

        $this->post->secret = ['1'];
        $expected = '<input name="post[secret]" type="hidden" value="0" autocomplete="off" />'
            . '<input checked="checked" id="post_secret" name="post[secret]" type="checkbox" value="1" />';
        $this->assertDomEquals($expected, static::checkBox('post', 'secret', ['object' => $this->post]));
    }

    public function testCheckBoxWithIncludeHiddenFalse() {
        $this->post->secret = false;

        $expected = '<input id="post_secret" name="post[secret]" type="checkbox" value="1" />';

        $this->assertDomEquals(
            $expected,
            static::checkBox('post', 'secret', ['include_hidden' => false, 'object' => $this->post])
        );
    }

    public function testCheckBoxWithStringObjectValues() {
        $this->post->secret = 'on';
        $expected = '<input name="post[secret]" type="hidden" value="off" autocomplete="off" />'
            . '<input checked="checked" id="post_secret" name="post[secret]" type="checkbox" value="on" />';

        $this->assertDomEquals(
            $expected,
            static::checkBox('post', 'secret', ['object' => $this->post], 'on', 'off')
        );

        $this->post->secret = 'off';
        $expected = '<input name="post[secret]" type="hidden" value="off" autocomplete="off" />'
            . '<input id="post_secret" name="post[secret]" type="checkbox" value="on" />';

        $this->assertDomEquals(
            $expected,
            static::checkBox('post', 'secret', ['object' => $this->post], 'on', 'off')
        );
    }

    public function testCheckBoxWithBooleanObjectValues() {
        $this->post->secret = false;
        $expected = '<input name="post[secret]" type="hidden" value="true" autocomplete="off" />'
            . '<input checked="checked" id="post_secret" name="post[secret]" type="checkbox" value="false" />';

        $this->assertDomEquals(
            $expected,
            static::checkBox('post', 'secret', ['object' => $this->post], false, true)
        );

        $this->post->secret = true;
        $expected = '<input name="post[secret]" type="hidden" value="true" autocomplete="off" />'
            . '<input id="post_secret" name="post[secret]" type="checkbox" value="false" />';

        $this->assertDomEquals(
            $expected,
            static::checkBox('post', 'secret', ['object' => $this->post], false, true)
        );
    }

    public function testCheckBoxWithIntegerObjectValues() {
        $this->post->secret = 0;
        $expected = '<input name="post[secret]" type="hidden" value="1" autocomplete="off" />'
            . '<input checked="checked" id="post_secret" name="post[secret]" type="checkbox" value="0" />';

        $this->assertDomEquals(
            $expected,
            static::checkBox('post', 'secret', ['object' => $this->post], 0, 1)
        );

        $this->post->secret = 1;
        $expected = '<input name="post[secret]" type="hidden" value="1" autocomplete="off" />'
            . '<input id="post_secret" name="post[secret]" type="checkbox" value="0" />';

        $this->assertDomEquals($expected, static::checkBox('post', 'secret', ['object' => $this->post], 0, 1));

        $this->post->secret = 2;
        $expected = '<input name="post[secret]" type="hidden" value="1" autocomplete="off" />'
            . '<input id="post_secret" name="post[secret]" type="checkbox" value="0" />';

        $this->assertDomEquals($expected, static::checkBox('post', 'secret', ['object' => $this->post], 0, 1));
    }

    public function testCheckBoxWithFloatObjectValues() {
        $this->post->secret = 0.0;
        $expected = '<input name="post[secret]" type="hidden" value="1" autocomplete="off" />'
            . '<input checked="checked" id="post_secret" name="post[secret]" type="checkbox" value="0" />';

        $this->assertDomEquals($expected, static::checkBox('post', 'secret', ['object' => $this->post], 0, 1));

        $this->post->secret = 1.1;
        $expected = '<input name="post[secret]" type="hidden" value="1" autocomplete="off" />'
            . '<input id="post_secret" name="post[secret]" type="checkbox" value="0" />';

        $this->assertDomEquals($expected, static::checkBox('post', 'secret', ['object' => $this->post], 0, 1));

        $this->post->secret = 2.2;
        $expected = '<input name="post[secret]" type="hidden" value="1" autocomplete="off" />'
            . '<input id="post_secret" name="post[secret]" type="checkbox" value="0" />';

        $this->assertDomEquals($expected, static::checkBox('post', 'secret', ['object' => $this->post], 0, 1));
    }

    public function testCheckBoxWithNullUncheckedValue() {
        $this->post->secret = 'on';
        $expected = '<input checked="checked" id="post_secret" name="post[secret]" type="checkbox" value="on" />';

        $this->assertDomEquals($expected, static::checkBox('post', 'secret', ['object' => $this->post], 'on', null));
    }

    public function testCheckBoxWithNullUncheckedValueIsHtmlString() {
        $this->assertInstanceOf(HtmlString::class, static::checkBox('post', 'secret', [], 'on', null));
    }

    public function testCheckBoxWithMultipleBehavior() {
        $this->post->comment_ids = [2, 3];

        $expected = '<input name="post[comment_ids][]" type="hidden" value="0" autocomplete="off" />'
            . '<input id="post_comment_ids_1" name="post[comment_ids][]" type="checkbox" value="1" />';
        $this->assertDomEquals(
            $expected,
            static::checkBox('post', 'comment_ids', ['multiple' => true, 'object' => $this->post], 1)
        );

        $expected = '<input name="post[comment_ids][]" type="hidden" value="0" autocomplete="off" />'
            . '<input checked="checked" id="post_comment_ids_3" name="post[comment_ids][]" type="checkbox"'
            . ' value="3" />';
        $this->assertDomEquals(
            $expected,
            static::checkBox('post', 'comment_ids', ['multiple' => true, 'object' => $this->post], 3)
        );
    }

    public function testCheckBoxWithMultipleBehaviorAndCollectionValue() {
        $this->post->comment_ids = collect([2, 3]);

        $expected = '<input name="post[comment_ids][]" type="hidden" value="0" autocomplete="off" />'
            . '<input id="post_comment_ids_1" name="post[comment_ids][]" type="checkbox" value="1" />';
        $this->assertDomEquals(
            $expected,
            static::checkBox('post', 'comment_ids', ['multiple' => true, 'object' => $this->post], 1)
        );

        $expected = '<input name="post[comment_ids][]" type="hidden" value="0" autocomplete="off" />'
            . '<input checked="checked" id="post_comment_ids_3" name="post[comment_ids][]" type="checkbox"'
            . ' value="3" />';
        $this->assertDomEquals(
            $expected,
            static::checkBox('post', 'comment_ids', ['multiple' => true, 'object' => $this->post], 3)
        );
    }

    public function testCheckBoxWithMultipleBehaviorAndIndex() {
        $this->post->comment_ids = [2, 3];

        $expected = '<input name="post[foo][comment_ids][]" type="hidden" value="0" autocomplete="off" />'
            . '<input id="post_foo_comment_ids_1" name="post[foo][comment_ids][]" type="checkbox" value="1" />';
        $this->assertDomEquals(
            $expected,
            static::checkBox('post', 'comment_ids', ['multiple' => true, 'index' => 'foo', 'object' => $this->post], 1)
        );

        $expected = '<input name="post[bar][comment_ids][]" type="hidden" value="0" autocomplete="off" />'
            . '<input checked="checked" id="post_bar_comment_ids_3" name="post[bar][comment_ids][]" type="checkbox"'
            . ' value="3" />';
        $this->assertDomEquals(
            $expected,
            static::checkBox('post', 'comment_ids', ['multiple' => true, 'index' => 'bar', 'object' => $this->post], 3)
        );
    }

    public function teestCheckBoxDisabledDisablesHiddenField() {
        $expected = '<input name="post[secret]" type="hidden" value="0" disabled="disabled" autocomplete="off"/>'
            . '<input checked="checked" disabled="disabled" id="post_secret" name="post[secret]"'
            . ' type="checkbox" value="1" />';
        $this->assertDomEquals(
            $expected,
            static::checkBox("post", "secret", ['disabled' => true, 'object' => $this->post])
        );
    }

    public function testCheckBoxHtml5FormAttribute() {
        $expected = '<input form="new_form" name="post[secret]" type="hidden" value="0" autocomplete="off" />'
            . '<input checked="checked" form="new_form" id="post_secret" name="post[secret]"'
            . ' type="checkbox" value="1" />';
        $this->assertDomEquals(
            $expected,
            static::checkBox('post', 'secret', ['form' => 'new_form', 'object' => $this->post])
        );
    }

    public function testColorFieldWithValidHexString() {
        $expected = '<input id="car_color" name="car[color]" type="color" value="#000fff" />';
        $this->assertDomEquals($expected, static::colorField('car', 'color', ['object' => $this->car]));
    }

    public function testColorFieldWithInvalidHexString() {
        $expected = '<input id="car_color" name="car[color]" type="color" value="#000000" />';
        $this->car->color = '#1234TR';
        $this->assertDomEquals($expected, static::colorField('car', 'color', ['object' => $this->car]));
    }

    public function testColorFieldWithValueString() {
        $expected = '<input id="car_color" name="car[color]" type="color" value="#00FF00" />';
        $this->assertDomEquals($expected, static::colorField('car', 'color', ['value' => '#00FF00']));
    }

    public function testDateField() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="date" value="2004-06-15" />';
        $this->assertDomEquals($expected, static::dateField('post', 'written_on', ['object' => $this->post]));
    }

    public function testDateFieldWithDatetimeValue() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="date" value="2004-06-15" />';
        $this->post->written_on = new DateTime('2004-06-15T01:02:03+00:00');
        $this->assertDomEquals($expected, static::dateField('post', 'written_on', ['object' => $this->post]));
    }

    public function testDateFieldWithCarbonValue() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="date" value="2004-06-15" />';
        $this->post->written_on = Carbon::parse('2004-06-15T01:02:03+00:00');
        $this->assertDomEquals($expected, static::dateField('post', 'written_on', ['object' => $this->post]));
    }

    public function testDateFieldWithTimestampValue() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="date" value="2004-06-15" />';
        $this->post->written_on = mktime(1, 2, 3, 6, 15, 2004);
        $this->assertDomEquals($expected, static::dateField('post', 'written_on', ['object' => $this->post]));
    }

    public function testDateFieldWithExtraAttrs() {
        $expected = '<input id="post_written_on" step="2" max="2010-08-15" min="2000-06-15" name="post[written_on]"'
            . ' type="date" value="2004-06-15" />';
        $this->post->written_on = new DateTime('2004-06-15');
        $opts = [
            'min' => new DateTime('2000-06-15'),
            'max' => new DateTime('2010-08-15'),
            'step' => 2,
            'object' => $this->post
        ];

        $this->assertDomEquals($expected, static::dateField('post', 'written_on', $opts));
    }

    public function testDateFieldWithValue() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="date" value="2013-06-29" />';

        $this->assertDomEquals($expected, static::dateField('post', 'written_on', [
            'value' => new DateTime('2013-06-29')
        ]));
        $this->assertDomEquals($expected, static::dateField('post', 'written_on', [
            'value' => Carbon::parse('2013-06-29')
        ]));
        $this->assertDomEquals($expected, static::dateField('post', 'written_on', [
            'value' => mktime(1, 2, 3, 6, 29, 2013)
        ]));
    }

    public function testDateFieldWithNullValue() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="date" />';
        $this->post->written_on = null;
        $this->assertDomEquals($expected, static::dateField('post', 'written_on', ['object' => $this->post]));
    }

    public function testDateFieldWithStringMinMax() {
        $expected = '<input id="post_written_on" max="2010-08-15" min="2000-06-15" name="post[written_on]"'
            . ' type="date" value="2004-06-15" />';
        $this->post->written_on = new DateTime('2004-06-15');
        $opts = [
            'min' => '2000-06-15',
            'max' => '2010-08-15',
            'object' => $this->post
        ];

        $this->assertDomEquals($expected, static::dateField('post', 'written_on', $opts));
    }

    public function testDateFieldWithInvalidStringMinMax() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="date" value="2004-06-15" />';
        $this->post->written_on = new DateTime('2004-06-15');
        $opts = [
            'min' => 'foo',
            'max' => 'bar',
            'object' => $this->post
        ];

        $this->assertDomEquals($expected, static::dateField('post', 'written_on', $opts));
    }

    public function testDatetimeField() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="datetime-local"'
            . ' value="2004-06-15T00:00:00" />';
        $this->assertDomEquals($expected, static::datetimeField('post', 'written_on', ['object' => $this->post]));
    }

    public function testDatetimeFieldWithDatetimeValue() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="datetime-local"'
            . ' value="2004-06-15T01:02:03" />';
        $this->post->written_on = new DateTime('2004-06-15T01:02:03');
        $this->assertDomEquals($expected, static::datetimeField('post', 'written_on', ['object' => $this->post]));
    }

    public function testDatetimeFieldWithCarbonValue() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="datetime-local"'
            . ' value="2004-06-15T01:02:03" />';
        $this->post->written_on = Carbon::parse('2004-06-15T01:02:03');
        $this->assertDomEquals($expected, static::datetimeField('post', 'written_on', ['object' => $this->post]));
    }

    public function testDatetimeFieldWithExtraAttrs() {
        $expected = '<input id="post_written_on" step="60" max="2010-08-15T10:25:00" min="2000-06-15T20:45:30"'
            . ' name="post[written_on]" type="datetime-local" value="2004-06-15T01:02:03" />';
        $this->post->written_on = new DateTime('2004-06-15T01:02:03');
        $opts = [
            'min' => new DateTime('2000-06-15T20:45:30'),
            'max' => new DateTime('2010-08-15T10:25:00'),
            'step' => 60,
            'object' => $this->post
        ];

        $this->assertDomEquals($expected, static::datetimeField('post', 'written_on', $opts));
    }

    public function testDatetimeFieldWithValueAttr() {
        // DEVIATION: rails has +00:00 TZ information in output...
        $expected = '<input id="post_written_on" name="post[written_on]" type="datetime-local"'
            . ' value="2013-06-29T13:37:00" />';

        $opts = ['value' => new DateTime('2013-06-29T13:37')];
        $this->assertDomEquals($expected, static::datetimeField('post', 'written_on', $opts));

        $opts = ['value' => Carbon::parse('2013-06-29T13:37')];
        $this->assertDomEquals($expected, static::datetimeField('post', 'written_on', $opts));

        $opts = ['value' => mktime(13, 37, 0, 6, 29, 2013)];
        $this->assertDomEquals($expected, static::datetimeField('post', 'written_on', $opts));
    }

    public function testDatetimeFieldWithNullValue() {
        // DEVIATION: rails has +00:00 TZ information in output...
        $expected = '<input id="post_written_on" name="post[written_on]" type="datetime-local" />';
        $this->post->written_on = null;
        $this->assertDomEquals($expected, static::datetimeField('post', 'written_on', ['object' => $this->post]));
    }

    public function testDatetimeFieldWithStringMinMax() {
        $expected = '<input id="post_written_on" max="2010-08-15T10:25:00" min="2000-06-15T20:45:30"'
            .' name="post[written_on]" type="datetime-local" value="2004-06-15T01:02:03" />';
        $this->post->written_on = new DateTime('2004-06-15T01:02:03');
        $opts = [
            'min' => '2000-06-15T20:45:30',
            'max' => '2010-08-15T10:25:00',
            'object' => $this->post
        ];

        $this->assertDomEquals($expected, static::datetimeField('post', 'written_on', $opts));
    }

    public function testDatetimeFieldWithInvalidStringMinMax() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="datetime-local"'
            . ' value="2004-06-15T01:02:03" />';
        $this->post->written_on = new DateTime('2004-06-15T01:02:03');
        $opts = [
            'min' => 'foo',
            'max' => 'bar',
            'object' => $this->post
        ];

        $this->assertDomEquals($expected, static::datetimeField('post', 'written_on', $opts));
    }

    public function testDatetimeLocalField() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="datetime-local"'
            . ' value="2004-06-15T00:00:00" />';
        $this->assertDomEquals($expected, static::datetimeLocalField('post', 'written_on', ['object' => $this->post]));
    }

    public function testEmailField() {
        $this->assertDomEquals(
            '<input id="user_address" name="user[address]" type="email" />',
            static::emailField('user', 'address')
        );
    }

    public function testFileFieldHasNoSize() {
        $expected = '<input id="user_avatar" name="user[avatar]" type="file" />';
        $this->assertDomEquals($expected, static::fileField("user", "avatar"));
    }

    public function testFileFieldWithMultipleBehavior() {
        $expected = '<input id="import_file" multiple="multiple" name="import[file][]" type="file" />';
        $this->assertDomEquals($expected, static::fileField("import", "file", ['multiple' => true]));
    }

    public function testFileFieldWithMultipleBehaviorConfiguredIncludeHidden() {
        static::$multipleFileFieldIncludeHidden = true;

        $expected = '<input type="hidden" name="import[file][]" autocomplete="off" value="">'
            . '<input id="import_file" multiple="multiple" name="import[file][]" type="file" />';

        $this->assertDomEquals($expected, static::fileField("import", "file", ['multiple' => true]));
    }

    public function testFileFieldWithMultipleBehaviorIncludeHiddenFalse() {
        static::$multipleFileFieldIncludeHidden = true;

        $expected = '<input id="import_file" multiple="multiple" name="import[file][]" type="file" />';
        $this->assertDomEquals(
            $expected,
            static::fileField("import", "file", ['multiple' => true, 'include_hidden' => false])
        );
    }

    public function testFileFieldWithMultipleBehaviorAndExplicitName() {
        $expected = '<input id="import_file" multiple="multiple" name="custom" type="file" />';
        $this->assertDomEquals(
            $expected,
            static::fileField('import', 'file', ['multiple' => true, 'name' => 'custom'])
        );
    }

    public function testFileFieldWithMultipleBehaviorAndExplicitNameConfiguredIncludeHidden() {
        static::$multipleFileFieldIncludeHidden = true;

        $expected = '<input type="hidden" name="custom" autocomplete="off" value="">'
            . '<input id="import_file" multiple="multiple" name="custom" type="file" />';
        $this->assertDomEquals(
            $expected,
            static::fileField('import', 'file', ['multiple' => true, 'name' => 'custom'])
        );
    }

    public function testFileFieldWithDirectUploadWhenDirectUploadsUrlIsNotDefined() {
        $expected = '<input type="file" name="import[file]" id="import_file" />';
        $this->assertDomEquals($expected, static::fileField('import', 'file', ['direct_upload' => true]));
    }

    public function testHiddenField() {
        $this->assertDomEquals(
            '<input id="post_title" name="post[title]" type="hidden" value="Hello World" autocomplete="off" />',
            static::hiddenField('post', 'title', ['object' => $this->post])
        );
    }

    public function testHiddenFieldWithEscapes() {
        $this->post->title = '<b>Hello World</b>';

        $expected = '<input id="post_title" name="post[title]" type="hidden" value="&lt;b&gt;Hello World&lt;/b&gt;"'
            .' autocomplete="off" />';

        $this->assertDomEquals($expected, static::hiddenField('post', 'title', ['object' => $this->post]));
    }

    public function testHiddenFieldWithNullValue() {
        $this->post->title = '<b>Hello World</b>';

        $expected = '<input id="post_title" name="post[title]" type="hidden" autocomplete="off" />';

        $this->assertDomEquals($expected, static::hiddenField('post', 'title', ['value' => null]));
    }

    public function testHiddenFieldWithOptions() {
        $this->assertDomEquals(
            '<input id="post_title" name="post[title]" type="hidden" value="Something Else" autocomplete="off" />',
            static::hiddenField('post', 'title', ['value' => 'Something Else'])
        );
    }

    public function testMonthField() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="month" value="2004-06" />';
        $this->assertDomEquals($expected, static::monthField('post', 'written_on', ['object' => $this->post]));
    }

    public function testMonthFieldWithNullValue() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="month" />';
        $this->post->written_on = null;

        $this->assertDomEquals($expected, static::monthField('post', 'written_on', ['object' => $this->post]));
    }

    public function testMonthFieldWithDateTimeValue() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="month" value="2004-06" />';

        $this->post->written_on = new DateTime('2004-06-15T01:02:03');
        $this->assertDomEquals($expected, static::monthField('post', 'written_on', ['object' => $this->post]));

        $this->post->written_on = Carbon::parse('2004-06-15T01:02:03');
        $this->assertDomEquals($expected, static::monthField('post', 'written_on', ['object' => $this->post]));

        $this->post->written_on = mktime(1, 2, 3, 6, 15, 2004);
        $this->assertDomEquals($expected, static::monthField('post', 'written_on', ['object' => $this->post]));
    }

    public function testMonthFieldWithExtraAttrs() {
        $expected = '<input id="post_written_on" step="2" max="2010-12" min="2000-02" name="post[written_on]"'
            . ' type="month" value="2004-06" />';
        $this->post->written_on = new DateTime('2004-06-15');
        $opts = [
            'min' => new DateTime('2000-02-13'),
            'max' => new DateTime('2010-12-23'),
            'step' => 2,
            'object' => $this->post
        ];

        $this->assertDomEquals($expected, static::monthField('post', 'written_on', $opts));
    }

    public function testNumberField() {
        $expected = '<input name="order[quantity]" max="9" id="order_quantity" type="number" min="1" />';
        $this->assertDomEquals($expected, static::numberField('order', 'quantity', ['in' => [1, 9]]));
        $expected = '<input name="order[quantity]" size="30" max="9" id="order_quantity" type="number" min="1" />';
        $this->assertDomEquals($expected, static::numberField('order', 'quantity', ['size' => 30, 'in' => [1, 9]]));
    }

    public function testRadioButton() {
        $expected = '<input checked="checked" id="post_title_hello_world" name="post[title]" type="radio"'
            . ' value="Hello World" />';
        $this->assertDomEquals(
            $expected,
            static::radioButton('post', 'title', 'Hello World', ['object' => $this->post])
        );

        $expected = '<input id="post_title_goodbye_world" name="post[title]" type="radio"'
            . ' value="Goodbye World" />';
        $this->assertDomEquals($expected, static::radioButton('post', 'title', 'Goodbye World'));

        $expected = '<input id="item_subobject_title_inside_world" name="item[subobject][title]" type="radio"'
            . ' value="inside World" />';
        $this->assertDomEquals($expected, static::radioButton('item[subobject]', 'title', 'inside World'));
    }

    public function testRadioButtonCheckedWithIntegers() {
        $this->assertDomEquals(
            '<input checked="checked" id="post_secret_1" name="post[secret]" type="radio" value="1" />',
            static::radioButton('post', 'secret', "1", ['object' => $this->post])
        );
    }

    public function testRadioButtonCheckedWithNegativeIntegerValue() {
        $this->assertDomEquals(
            '<input id="post_secret_-1" name="post[secret]" type="radio" value="-1" />',
            static::radioButton('post', 'secret', "-1", ['object' => $this->post])
        );
    }

    public function testRadioButtonRespectsPassedInId() {
        $this->assertDomEquals(
            '<input checked="checked" id="foo" name="post[secret]" type="radio" value="1" />',
            static::radioButton('post', 'secret', '1', ['id' => 'foo', 'object' => $this->post])
        );
    }

    public function testRadioButtonWithBooleans() {
        //DEVIATION: not checked in rails...
        $this->assertDomEquals(
            '<input checked="checked" id="post_secret_true" name="post[secret]" type="radio" value="true" />',
            static::radioButton('post', 'secret', true, ['object' => $this->post])
        );

        $this->assertDomEquals(
            '<input id="post_secret_false" name="post[secret]" type="radio" value="false" />',
            static::radioButton('post', 'secret', false, ['object' => $this->post])
        );
    }

    public function testRangeField() {
        $expected = '<input name="hifi[volume]" step="0.1" max="11" id="hifi_volume" type="range" min="0" />';
        $this->assertDomEquals($expected, static::rangeField("hifi", "volume", ['in' => [0, 11], 'step' => 0.1]));
        $expected = '<input name="hifi[volume]" step="0.1" size="30" max="11" id="hifi_volume" type="range" min="0" />';
        $this->assertDomEquals(
            $expected,
            static::rangeField("hifi", "volume", ['size' => 30, 'in' => [0, 11], 'step' => 0.1])
        );
    }

    public function testSearchField() {
        $expected = '<input id="contact_notes_query" name="contact[notes_query]" type="search" />';
        $this->assertDomEquals($expected, static::searchField('contact', 'notes_query'));
    }

    public function testSearchFieldWithAutosave() {
        $expected = '<input id="contact_notes_query" autosave="localhost" results="10" name="contact[notes_query]"'
            . ' type="search" />';
        $this->assertDomEquals($expected, static::searchField('contact', 'notes_query', ['autosave' => true]));
    }

    public function testSearchFieldWithAutosaveAndResultCount() {
        $expected = '<input id="contact_notes_query" autosave="localhost" results="5" name="contact[notes_query]"'
            . ' type="search" />';
        $this->assertDomEquals(
            $expected,
            static::searchField('contact', 'notes_query', ['autosave' => true, 'results' => 5])
        );
    }

    public function testSearchFieldWithOnSearchValue() {
        $expected = '<input onsearch="true" type="search" name="contact[notes_query]" id="contact_notes_query"'
            . ' incremental="true" />';
        $this->assertDomEquals($expected, static::searchField('contact', 'notes_query', ['onsearch' => true]));
    }

    public function testTelephoneField() {
        $expected = '<input id="user_cell" name="user[cell]" type="tel" />';

        $this->assertDomEquals($expected, static::telephoneField('user', 'cell'));
        $this->assertDomEquals($expected, static::phoneField('user', 'cell'));
    }

    public function testTextAreaPlaceholderWithoutTranslations() {
        Lang::setLocale('placeholder');
        $expected = '<textarea id="post_body" name="post[body]" placeholder="Body">'
            . "\n" . 'This is a post</textarea>';

        $this->assertDomEquals(
            $expected,
            static::textArea('post', 'body', ['placeholder' => true, 'object' => $this->post])
        );
    }

    public function testTextAreaPlaceholderWithTranslations() {
        Lang::setLocale('placeholder');
        $expected = '<textarea id="post_title" name="post[title]" placeholder="What is this about?">'
            . "\n" . 'Hello World</textarea>';

        $this->assertDomEquals(
            $expected,
            static::textArea('post', 'title', ['placeholder' => true, 'object' => $this->post])
        );
    }

    public function testTextAreaPlaceholderWithHumanAttributeName() {
        Lang::setLocale('placeholder');
        $expected = '<textarea id="post_cost" name="post[cost]" placeholder="Total cost">'
            . "\n" . '</textarea>';

        $this->assertDomEquals(
            $expected,
            static::textArea('post', 'cost', ['placeholder' => true, 'object' => $this->post])
        );
    }

    public function testTextAreaPlaceholderWithStringValue() {
        Lang::setLocale('placeholder');
        $expected = '<textarea id="post_cost" name="post[cost]" placeholder="HOW MUCH?">'
            . "\n" . '</textarea>';

        $this->assertDomEquals(
            $expected,
            static::textArea('post', 'cost', ['placeholder' => 'HOW MUCH?', 'object' => $this->post])
        );
    }

    public function testTextAreaPlaceholderWithHumanAttributeNameAndValue() {
        Lang::setLocale('placeholder');
        $expected = '<textarea id="post_cost" name="post[cost]" placeholder="Pounds">'
            . "\n" . '</textarea>';

        $this->assertDomEquals(
            $expected,
            static::textArea('post', 'cost', ['placeholder' => new Stringable('uk'), 'object' => $this->post])
        );
    }

    public function testTextAreaPlaceholderWithTranslationsAndValue() {
        Lang::setLocale('placeholder');
        $expected = '<textarea id="post_written_on" name="post[written_on]" placeholder="Escrito en">'
            . "\n" . '2004-06-15</textarea>';

        $this->assertDomEquals(
            $expected,
            static::textArea('post', 'written_on', [
                'placeholder' => new Stringable('spanish'),
                'object' => $this->post
            ])
        );
    }

    public function testTextAreaPlaceholderWithTranslationsAndNestedAttributes() {
        Lang::setLocale('placeholder');

        $rendered = static::formWith(
            model: $this->post,
            options: ['html' => ['id' => 'create-post']],
            block: function ($f) {
                return $f->fieldsFor('comments', null, [], function ($cf) {
                    return $cf->textArea('body', ['placeholder' => true]);
                });
            }
        );

        $expected = $this->wholeForm('/posts/123', 'create-post', null, ['method' => 'patch'], function () {
            return '<textarea id="post_comments_attributes_0_body" name="post[comments_attributes][0][body]"'
                . ' placeholder="Write body here">' . "\n" . '</textarea>';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testTextAreaPlaceholderWithTranslationFallbackAndNestedAttributes() {
        Lang::setLocale('placeholder');

        $rendered = static::formWith(
            model: $this->post,
            options: ['html' => ['id' => 'create-post']],
            block: function ($f) {
                return $f->fieldsFor('tags', null, [], function ($cf) {
                    return $cf->textArea('value', ['placeholder' => true]);
                });
            }
        );

        $expected = $this->wholeForm('/posts/123', 'create-post', null, ['method' => 'patch'], function () {
            return '<textarea id="post_tags_attributes_0_value" name="post[tags_attributes][0][value]"'
                . ' placeholder="Tag">' . "\n" . 'new tag</textarea>';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testTextArea() {
        $expected = '<textarea id="post_body" name="post[body]">'
            . "\n" . 'This is a post</textarea>';
        $this->assertDomEquals($expected, static::textArea('post', 'body', ['object' => $this->post]));
    }

    public function testTextAreaWithEscapes() {
        $this->post->body = 'Back to <i>the</i> hill and over it again!';
        $expected = '<textarea id="post_body" name="post[body]">'
            . "\n" . 'Back to &lt;i&gt;the&lt;/i&gt; hill and over it again!</textarea>';
        $this->assertDomEquals($expected, static::textArea('post', 'body', ['object' => $this->post]));
    }

    public function testTextAreaWithAlternateValue() {
        $expected = '<textarea id="post_body" name="post[body]">'
            . "\n" . 'Testing alternate values.</textarea>';

        $this->assertDomEquals($expected, static::textArea('post', 'body', ['value' => 'Testing alternate values.']));
    }

    public function testTextAreaWithNullAlternateValue() {
        $expected = '<textarea id="post_body" name="post[body]">'
            . "\n" . '</textarea>';

        $this->assertDomEquals($expected, static::textArea('post', 'body', ['value' => null]));
    }

    public function testTextAreaWithHtmlEntities() {
        $this->post->body = 'The HTML Entity for & is &amp;';
        $expected = '<textarea id="post_body" name="post[body]">'
            . "\n" . 'The HTML Entity for &amp; is &amp;amp;</textarea>';

        $this->assertDomEquals($expected, static::textArea('post', 'body', ['object' => $this->post]));
    }

    public function testTextAreaWithSizeOption() {
        $expected = '<textarea cols="183" id="post_body" name="post[body]" rows="820">'
            . "\n" . 'This is a post</textarea>';

        $this->assertDomEquals(
            $expected,
            static::textArea('post', 'body', ['size' => '183x820', 'object' => $this->post])
        );
    }

    public function testTimeField() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="time" value="00:00:00" />';
        $this->assertDomEquals($expected, static::timeField('post', 'written_on', ['object' => $this->post]));
    }

    public function testTimeFieldWithDateTimeValue() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="time" value="01:02:03" />';
        $this->post->written_on = mktime(1, 2, 3, 6, 15, 2004);

        $this->assertDomEquals($expected, static::timeField('post', 'written_on', ['object' => $this->post]));
    }

    public function testTimeFieldWithExtraAttrs() {
        $expected = '<input id="post_written_on" step="60" max="10:25:00" min="20:45:30"'
            . ' name="post[written_on]" type="time" value="01:02:03" />';
        $this->post->written_on = mktime(1, 2, 3, 6, 15, 2004);
        $opts = [
            'min' => mktime(20, 45, 30, 6, 15, 2000),
            'max' => mktime(10, 25, 0, 8, 15, 2010),
            'step' => 60,
            'object' => $this->post
        ];

        $this->assertDomEquals($expected, static::timeField('post', 'written_on', $opts));
    }

    public function testTimeFieldWithNullValue() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="time" />';
        $this->post->written_on = null;

        $this->assertDomEquals($expected, static::timeField('post', 'written_on', ['object' => $this->post]));
    }

    public function testTimeFieldWithStringMinMax() {
        $expected = '<input id="post_written_on" max="10:25:00" min="20:45:30"'
            . ' name="post[written_on]" type="time" value="01:02:03" />';
        $this->post->written_on = mktime(1, 2, 3, 6, 15, 2004);
        $opts = [
            'min' => '20:45:30',
            'max' => '10:25:00',
            'object' => $this->post
        ];

        $this->assertDomEquals($expected, static::timeField('post', 'written_on', $opts));
    }

    public function testTimeFieldWithoutSeconds() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="time" value="01:02"'
            . ' max="10:25" min="20:45" />';
        $this->post->written_on = mktime(1, 2, 3, 6, 15, 2004);
        $opts = [
            'min' => mktime(20, 45, 30, 6, 15, 2000),
            'max' => mktime(10, 25, 0, 8, 15, 2010),
            'include_seconds' => false,
            'object' => $this->post
        ];

        $this->assertDomEquals($expected, static::timeField('post', 'written_on', $opts));
    }

    public function testUrlField() {
        $this->assertDomEquals(
            '<input id="user_homepage" name="user[homepage]" type="url" />',
            static::urlField('user', 'homepage')
        );
    }

    public function testWeekField() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="week" value="2004-W25" />';
        $this->assertDomEquals($expected, static::weekField('post', 'written_on', ['object' => $this->post]));

        $expected = '<input id="post_written_on" name="post[written_on]" type="week" value="1948-W53" />';
        $this->post->written_on = Carbon::parse('1949-01-01');
        $this->assertDomEquals($expected, static::weekField('post', 'written_on', ['object' => $this->post]));
    }

    public function testWeekFieldWithNullValue() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="week" />';
        $this->post->written_on = null;
        $this->assertDomEquals($expected, static::weekField('post', 'written_on', ['object' => $this->post]));
    }

    public function testWeekFieldWithDateTimeValue() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="week" value="2004-W25" />';

        $this->post->written_on = new DateTime('2004-06-15T01:02:03');
        $this->assertDomEquals($expected, static::weekField('post', 'written_on', ['object' => $this->post]));

        $this->post->written_on = Carbon::parse('2004-06-15T01:02:03');
        $this->assertDomEquals($expected, static::weekField('post', 'written_on', ['object' => $this->post]));

        $this->post->written_on = mktime(1, 2, 3, 6, 15, 2004);
        $this->assertDomEquals($expected, static::weekField('post', 'written_on', ['object' => $this->post]));
    }

    public function testWeekFieldWithExtraAttrs() {
        $expected = '<input id="post_written_on" step="2" max="2010-W51" min="2000-W06" name="post[written_on]"'
            . ' type="week" value="2004-W25" />';
        $this->post->written_on = new DateTime('2004-06-15T01:02:03');
        $opts = [
            'min' => new DateTime('2000-02-13'),
            'max' => new DateTime('2010-12-23'),
            'step' => 2,
            'object' => $this->post
        ];

        $this->assertDomEquals($expected, static::weekField('post', 'written_on', $opts));
    }

    public function testWeekFieldWeekNumberBase() {
        $expected = '<input id="post_written_on" name="post[written_on]" type="week" value="2015-W01" />';
        $this->post->written_on = new DateTime('2015-01-01T01:02:03');
        $this->assertDomEquals($expected, static::weekField('post', 'written_on', ['object' => $this->post]));
    }

    public function testExplicitName() {
        $this->assertDomEquals(
            '<input id="post_title" name="dont guess" type="text" value="Hello World" />',
            static::textField('post', 'title', ['name' => 'dont guess', 'object' => $this->post])
        );

        $this->assertDomEquals(
            '<textarea id="post_body" name="really!">' . "\n" . 'This is a post</textarea>',
            static::textArea('post', 'body', ['name' => 'really!', 'object' => $this->post])
        );

        $this->assertDomEquals(
            '<input name="i mean it" type="hidden" value="0" autocomplete="off" />'
            . '<input checked="checked" id="post_secret" name="i mean it" type="checkbox" value="1" />',
            static::checkBox('post', 'secret', ['name' => 'i mean it', 'object' => $this->post])
        );
    }

    public function testExplicitId() {
        $this->assertDomEquals(
            '<input id="dont guess" name="post[title]" type="text" value="Hello World" />',
            static::textField('post', 'title', ['id' => 'dont guess', 'object' => $this->post])
        );

        $this->assertDomEquals(
            '<textarea id="really!" name="post[body]">' . "\n" . 'This is a post</textarea>',
            static::textArea('post', 'body', ['id' => 'really!', 'object' => $this->post])
        );

        $this->assertDomEquals(
            '<input name="post[secret]" type="hidden" value="0" autocomplete="off" />'
            . '<input checked="checked" id="i mean it" name="post[secret]" type="checkbox" value="1" />',
            static::checkBox('post', 'secret', ['id' => 'i mean it', 'object' => $this->post])
        );
    }

    public function testNullId() {
        $this->assertDomEquals(
            '<input name="post[title]" type="text" value="Hello World" />',
            static::textField('post', 'title', ['id' => null, 'object' => $this->post])
        );

        $this->assertDomEquals(
            '<textarea name="post[body]">' . "\n" . 'This is a post</textarea>',
            static::textArea('post', 'body', ['id' => null, 'object' => $this->post])
        );

        $this->assertDomEquals(
            '<input name="post[secret]" type="hidden" value="0" autocomplete="off" />'
            . '<input checked="checked" name="post[secret]" type="checkbox" value="1" />',
            static::checkBox('post', 'secret', ['id' => null, 'object' => $this->post])
        );

        $this->assertDomEquals(
            '<input type="radio" name="post[secret]" value="0" />',
            static::radioButton('post', 'secret', '0', ['id' => null, 'object' => $this->post])
        );
    }
}

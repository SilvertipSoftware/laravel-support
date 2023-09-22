<?php

namespace Tests\Blade\Directives;

use Carbon\Carbon;
use Illuminate\View\ViewException;
use Orchestra\Testbench\TestCase;
use SilvertipSoftware\LaravelSupport\Blade\ViewSupport;
use Tests\TestSupport\HtmlAssertions;

class FormHelperTest extends TestCase {
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

    public function testFormWithInline() {
        $this->assertBlade(
            '<form accept-charset="UTF-8" action="/posts" method="post"></form>',
            "@formWith(\$newPost)"
        );
    }

    public function testFormWithInlineJustUrl() {
        $this->assertBlade(
            '<form accept-charset="UTF-8" action="/path" method="post"></form>',
            "@formWith(null, null, '/path')"
        );
    }

    public function testFormWithInlineWithOptions() {
        $this->assertBlade(
            '<form accept-charset="UTF-8" action="/posts" method="post" name="go"></form>',
            "@formWith(\$newPost, null, null, null, ['html' => ['name' => 'go']])"
        );
    }

    public function testFormWith() {
        $this->assertDirectiveExists('formWith');
        $expected = '<form accept-charset="UTF-8" action="/posts" method="post"></form>';

        $this->assertBlade($expected, "@formWith(\$newPost as \$f)@endBlock");
    }

    public function testFormWithDefinesBuilderVariable() {
        $expected = '<form accept-charset="UTF-8" action="/posts" method="post">FormBuilder</form>';

        $this->assertBlade($expected, "@formWith(\$newPost as \$f){{ class_basename(\$f) }}@endBlock");
    }

    public function testFormWithDoesNotEscapeForBlade() {
        $expected = '<form accept-charset="UTF-8" action="/posts" method="post"><b>Hello form</b></form>';

        $this->assertBlade($expected, "@formWith(\$newPost as \$f)<b>Hello form</b>@endBlock");
    }

    public function testFormWithExpectsEndDirective() {
        $this->expectException(ViewException::class);
        $this->blade("@formWith(\$newPost as \$f)");
    }

    public function testFieldsFor() {
        $this->assertDirectiveExists('fhFieldsFor');

        $this->assertBlade('Hello fields', "@fhFieldsFor('post' as \$f)Hello fields @endBlock");
    }

    public function testFieldsForDoesNotEscapeForBlade() {
        $this->assertBlade('<b>Hello fields</b>', "@fhFieldsFor('post' as \$f)<b>Hello fields</b>@endBlock");
    }

    public function testFieldsForDefinesBuilderVar() {
        $this->assertBlade('FormBuilder', "@fhFieldsFor('post' as \$f){{ class_basename(\$f) }}@endBlock");
    }

    public function testFieldsForWithJustObject() {
        $this->assertBlade(
            'post[title]=Draft',
            "@fhFieldsFor(\$newPost as \$f){{ \$f->fieldName('title') }}={{ \$f->object->title }}@endBlock"
        );
    }

    public function testFieldsForWithNameAndObject() {
        $this->assertBlade(
            'art[title]=Draft',
            "@fhFieldsFor('art', \$newPost as \$f){{ \$f->fieldName('title') }}={{ \$f->object->title }}@endBlock"
        );
    }

    public function testFieldsForExpectsEndDirective() {
        $this->expectException(ViewException::class);
        $this->blade("@fhFieldsFor('post' as \$f)");
    }

    public function testFieldsForExpectsMatchingEndDirective() {
        $this->expectException(ViewException::class);
        $this->blade("@fhFieldsFor('post' as \$f) @endFhLabel");
    }

    public function testFieldsForUnderFormWith() {
        $expected = '<form accept-charset="UTF-8" action="/posts" method="post">'
            . '<label for="post_author_attributes_name">Name</label>'
            . '</form>';

        $blade = "@formWith(model: \$newPost as \$f)\n"
            . "@fieldsFor(\$f, 'author' as \$authorFields)\n"
            . "@label(\$authorFields, 'name')\n"
            . "@endBlock\n"
            . "@endBlock";

        $this->assertBlade($expected, $blade);
    }

    public function testFieldsForUnderFormWithMany() {
        $this->newPost->comments = $this->comments;

        $expected = '<form accept-charset="UTF-8" action="/posts" method="post">'
            . '<label for="post_comments_attributes_0_body">Body</label>'
            . '<label for="post_comments_attributes_1_body">Body</label>'
            . '</form>';

        $blade = "@formWith(model: \$newPost as \$f)\n"
            . "@fieldsFor(\$f, 'comments' as \$commentFields)\n"
            . "@label(\$commentFields, 'body')\n"
            . "@endBlock\n"
            . "@endBlock";

        $this->assertBlade($expected, $blade);
    }

    public function testLabel() {
        $this->assertDirectiveExists('fhLabel');

        $this->assertBlade(
            '<label for="post_title">Hi </label>',
            "@fhLabel('post', 'title' as \$f)Hi @endBlock"
        );
    }

    public function testLabelDoesNotEscapeForBlade() {
        $this->assertBlade(
            '<label for="post_title"><b>Hello label</b></label>',
            "@fhLabel('post', 'title' as \$f)<b>Hello label</b>@endBlock"
        );
    }

    public function testLabelWithBuilderAsTranslation() {
        $this->assertBlade(
            '<label for="post_title"><span>Title</span></label>',
            "@fhLabel('post', 'title' as \$translation)@contentTag('span', \$translation)@endBlock"
        );
    }

    public function testLabelWithBuilderAsBuilder() {
        $this->assertBlade(
            '<label for="post_title"><span>Title</span></label>',
            "@fhLabel('post', 'title' as \$b)@contentTag('span', \$b->translation())@endBlock"
        );
    }

    public function testLabelInline() {
        $this->assertBlade(
            '<label for="post_title">Title</label>',
            "@fhLabel('post', 'title', 'Title')"
        );
    }

    public function testLabelInlineWithAttrs() {
        $this->assertBlade(
            '<label for="post_title" class="title_label">Title</label>',
            "@fhLabel('post', 'title', 'Title', ['class' => 'title_label'])"
        );
    }

    public function testLabelInlineWithValue() {
        $this->assertBlade(
            '<label for="post_title_public">Title</label>',
            "@fhLabel('post', 'title', 'Title', ['value' => 'public'])"
        );
    }

    public function testTextField() {
        $this->assertDirectiveExists('fhTextField');

        $this->assertBlade(
            '<input id="post_title" name="post[title]" type="text" value="Draft" />',
            "@fhTextField('post', 'title', ['object' => \$newPost])"
        );

        $this->assertBlade(
            '<input id="post_title" name="post[title]" type="text" size="20"/>',
            "@fhTextField('post', 'title', ['size' => 20])"
        );
    }

    public function testPasswordField() {
        $this->assertDirectiveExists('fhPasswordField');

        $this->assertBlade(
            '<input id="user_passwd" name="user[passwd]" type="password" />',
            "@fhPasswordField('user', 'passwd')"
        );

        $this->assertBlade(
            '<input id="user_passwd" name="user[passwd]" type="password" size="20"/>',
            "@fhPasswordField('user', 'passwd', ['size' => 20])"
        );
    }

    public function testHiddenField() {
        $this->assertDirectiveExists('fhHiddenField');

        $this->assertBlade(
            '<input id="car_vin" name="car[vin]" type="hidden" autocomplete="off" value="H1234567890" />',
            "@fhHiddenField('car', 'vin', ['object' => \$car])"
        );

        $this->assertBlade(
            '<input id="user_token" name="user[token]" type="hidden" autocomplete="off" role="hidden"/>',
            "@fhHiddenField('user', 'token', ['role' => 'hidden'])"
        );
    }

    public function testFileField() {
        $this->assertDirectiveExists('fhFileField');

        $this->assertBlade(
            '<input id="car_image" name="car[image]" type="file" />',
            "@fhFileField('car', 'image')"
        );

        $this->assertBlade(
            '<input id="car_image" name="car[image]" type="file" accept="image/png,image/gif,image/jpeg" />',
            "@fhFileField('car', 'image', ['accept' => 'image/png,image/gif,image/jpeg'])"
        );
    }

    public function testTextArea() {
        $this->assertDirectiveExists('fhTextArea');

        $this->assertBlade(
            '<textarea cols="20" rows="40" id="post_body" name="post[body]">',
            "@fhTextArea('post', 'body', ['size' => '20x40'])"
        );

        $this->assertBlade(
            '<textarea id="post_body" name="post[body]">' . "\n" . 'This post exists</textarea>',
            "@fhTextArea('post', 'body', ['object' => \$existingPost])",
            [],
            false
        );

        $this->assertBlade(
            '<textarea disabled="disabled" id="post_body" name="post[body]">',
            "@fhTextArea('post', 'body', ['disabled' => true])"
        );
    }

    public function testCheckBox() {
        $this->assertDirectiveExists('fhCheckBox');

        $expected = '<input type="hidden" name="post[public]" value="0" autocomplete="off" />'
            . '<input type="checkbox" name="post[public]" id="post_public" value="1" />';

        $this->assertBlade(
            $expected,
            "@fhCheckBox('post', 'public')"
        );

        $this->newPost->public = true;
        $expected = '<input type="hidden" name="post[public]" value="0" autocomplete="off" />'
            . '<input type="checkbox" name="post[public]" id="post_public" value="1" checked="checked" />';

        $this->assertBlade(
            $expected,
            "@fhCheckBox('post', 'public', ['object' => \$newPost])"
        );
    }

    public function testCheckBoxWithArrayObjectValue() {
        $this->newPost->lucky_numbers = [11, 16, 72];
        $expected = '<input type="hidden" name="post[lucky_numbers][]" value="0" autocomplete="off" />'
            . '<input type="checkbox" name="post[lucky_numbers][]" id="post_lucky_numbers_72"'
            . ' value="72" checked="checked" />';

        $this->assertBlade(
            $expected,
            "@fhCheckBox('post', 'lucky_numbers', ['multiple' => true, 'object' => \$newPost], 72)"
        );
    }

    public function testCheckBoxWithoutHidden() {
        $expected = '<input type="checkbox" name="post[public]" id="post_public" value="1" />';

        $this->assertBlade(
            $expected,
            "@fhCheckBox('post', 'public', ['include_hidden' => false])"
        );
    }

    public function testCheckBoxValues() {
        $expected = '<input type="hidden" name="post[public]" value="nope" autocomplete="off" />'
            . '<input class="slangy" type="checkbox" name="post[public]" id="post_public" value="yup" />';

        $this->assertBlade(
            $expected,
            "@fhCheckBox('post', 'public', ['class' => 'slangy'], 'yup', 'nope')"
        );
    }

    public function testRadioButton() {
        $this->assertDirectiveExists('fhRadioButton');

        $expected = '<input type="radio" id="post_category_php" name="post[category]" value="php" />';
        $this->assertBlade($expected, "@fhRadioButton('post', 'category', 'php')");

        $this->newPost->category = 'php';
        $expected = '<input type="radio" id="post_category_php" name="post[category]" value="php" checked="checked" />';
        $this->assertBlade($expected, "@fhRadioButton('post', 'category', 'php', ['object' => \$newPost])");

        $expected = '<input type="radio" id="post_read_no" name="post[read]" value="no" />';
        $this->assertBlade($expected, "@fhRadioButton('post', 'read', 'no')");
    }

    public function testColorField() {
        $this->assertDirectiveExists('fhColorField');

        $expected = '<input type="color" id="post_bgcolor" name="post[bgcolor]" value="#000000" />';
        $this->assertBlade($expected, "@fhColorField('post', 'bgcolor')");

        $this->newPost->bgcolor = '#FF0000';
        $expected = '<input type="color" id="post_bgcolor" name="post[bgcolor]" value="#ff0000" />';
        $this->assertBlade($expected, "@fhColorField('post', 'bgcolor', ['object' => \$newPost])");

        $this->newPost->bgcolor = 'red';
        $expected = '<input type="color" id="post_bgcolor" name="post[bgcolor]" value="#000000" />';
        $this->assertBlade($expected, "@fhColorField('post', 'bgcolor', ['object' => \$newPost])");
    }

    public function testSearchField() {
        $this->assertDirectiveExists('fhSearchField');

        $expected = '<input type="search" id="post_title" name="post[title]" />';
        $this->assertBlade($expected, "@fhSearchField('post', 'title')");

        $expected = '<input type="search" id="post_title" name="post[title]" autosave="localhost" results="10" />';
        $this->assertBlade($expected, "@fhSearchField('post', 'title', ['autosave' => true])");
    }

    public function testTelField() {
        $this->assertDirectiveExists('fhTelephoneField');
        $this->assertDirectiveExists('fhPhoneField');

        $expected = '<input type="tel" id="user_cell" name="user[cell]" />';
        $this->assertBlade($expected, "@fhPhoneField('user', 'cell')");

        $expected = '<input type="tel" id="user_cell" name="user[cell]" class="missing" />';
        $this->assertBlade($expected, "@fhTelephoneField('user', 'cell', ['class' => 'missing'])");
    }

    public function testDateField() {
        $this->assertDirectiveExists('fhDateField');

        $expected = '<input type="date" id="post_as_of" name="post[as_of]" />';
        $this->assertBlade($expected, "@fhDateField('post', 'as_of')");

        $this->newPost->as_of = Carbon::parse('2023-07-01');
        $expected = '<input type="date" id="post_as_of" name="post[as_of]" value="2023-07-01" />';
        $this->assertBlade($expected, "@fhDateField('post', 'as_of', ['object' => \$newPost])");
    }

    public function testTimeField() {
        $this->assertDirectiveExists('fhTimeField');

        $expected = '<input type="time" id="user_alarm" name="user[alarm]" />';
        $this->assertBlade($expected, "@fhTimeField('user', 'alarm')");

        $expected = '<input type="time" id="user_alarm" name="user[alarm]" value="06:43:32" />';
        $this->assertBlade($expected, "@fhTimeField('user', 'alarm', ['value' => Carbon\Carbon::parse('06:43:32')])");
    }

    public function testDateTimeField() {
        $this->assertDirectiveExists('fhDatetimeField');

        $expected = '<input type="datetime-local" id="post_as_of" name="post[as_of]" />';
        $this->assertBlade($expected, "@fhDatetimeField('post', 'as_of')");

        $this->newPost->as_of = Carbon::parse('2023-07-01T06:43:32');
        $expected = '<input type="datetime-local" id="post_as_of" name="post[as_of]" value="2023-07-01T06:43:32" />';
        $this->assertBlade($expected, "@fhDatetimeField('post', 'as_of', ['object' => \$newPost])");
    }

    public function testMonthField() {
        $this->assertDirectiveExists('fhMonthField');

        $expected = '<input type="month" id="post_as_of" name="post[as_of]" />';
        $this->assertBlade($expected, "@fhMonthField('post', 'as_of')");

        $this->newPost->as_of = Carbon::parse('2023-07-01T06:43:32');
        $expected = '<input type="month" id="post_as_of" name="post[as_of]" value="2023-07" />';
        $this->assertBlade($expected, "@fhMonthField('post', 'as_of', ['object' => \$newPost])");
    }

    public function testWeekField() {
        $this->assertDirectiveExists('fhWeekField');

        $expected = '<input type="week" id="post_as_of" name="post[as_of]" />';
        $this->assertBlade($expected, "@fhWeekField('post', 'as_of')");

        $this->newPost->as_of = Carbon::parse('1984-05-12');
        $expected = '<input type="week" id="post_as_of" name="post[as_of]" value="1984-W19" />';
        $this->assertBlade($expected, "@fhWeekField('post', 'as_of', ['object' => \$newPost])");
    }

    public function testUrlField() {
        $this->assertDirectiveExists('fhUrlField');

        $expected = '<input type="url" id="post_slug" name="post[slug]" />';
        $this->assertBlade($expected, "@fhUrlField('post', 'slug')");

        $expected = '<input type="url" id="post_slug" name="post[slug]" class="int-link" />';
        $this->assertBlade($expected, "@fhUrlField('post', 'slug', ['class' => 'int-link'])");
    }

    public function testEmailField() {
        $this->assertDirectiveExists('fhEmailField');

        $expected = '<input type="email" id="post_contact" name="post[contact]" />';
        $this->assertBlade($expected, "@fhEmailField('post', 'contact')");

        $expected = '<input type="email" id="post_contact" name="post[contact]" class="int-link" />';
        $this->assertBlade($expected, "@fhEmailField('post', 'contact', ['class' => 'int-link'])");
    }

    public function testNumberField() {
        $this->assertDirectiveExists('fhNumberField');

        $expected = '<input type="number" id="post_readers" name="post[readers]" />';
        $this->assertBlade($expected, "@fhNumberField('post', 'readers')");

        $expected = '<input type="number" id="post_readers" name="post[readers]" min="0" />';
        $this->assertBlade($expected, "@fhNumberField('post', 'readers', ['min' => 0])");
    }

    public function testRangeField() {
        $this->assertDirectiveExists('fhRangeField');

        $expected = '<input type="range" id="post_readers" name="post[readers]" />';
        $this->assertBlade($expected, "@fhRangeField('post', 'readers')");

        $expected = '<input type="range" id="post_readers" name="post[readers]" min="0" max="100" />';
        $this->assertBlade($expected, "@fhRangeField('post', 'readers', ['in' => [0,100]])");
    }
}

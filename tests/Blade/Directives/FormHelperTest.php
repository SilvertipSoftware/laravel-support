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
        $this->assertDirectiveExists('endFormWith');
        $expected = '<form accept-charset="UTF-8" action="/posts" method="post"></form>';

        $this->assertBlade($expected, "@formWith(\$newPost as \$f)@endFormWith");
    }

    public function testFormWithDefinesBuilderVariable() {
        $expected = '<form accept-charset="UTF-8" action="/posts" method="post">FormBuilder</form>';

        $this->assertBlade($expected, "@formWith(\$newPost as \$f){{ class_basename(\$f) }}@endFormWith");
    }

    public function testFormWithDoesNotEscapeForBlade() {
        $expected = '<form accept-charset="UTF-8" action="/posts" method="post"><b>Hello form</b></form>';

        $this->assertBlade($expected, "@formWith(\$newPost as \$f)<b>Hello form</b>@endFormWith");
    }

    public function testFormWithExpectsEndDirective() {
        $this->expectException(ViewException::class);
        $this->blade("@formWith(\$newPost as \$f)");
    }

    public function testFormWithExpectsEndFormWithDirective() {
        $this->expectException(ViewException::class);
        $this->blade("@formWith(\$newPost as \$f)@endLabel");
    }

    public function testFieldsFor() {
        $this->assertDirectiveExists('fieldsFor');
        $this->assertDirectiveExists('endFieldsFor');
        $this->assertDirectiveExists('bldFieldsFor');
        $this->assertDirectiveExists('endBldFieldsFor');

        $this->assertBlade('Hello fields', "@fieldsFor('post' as \$f)Hello fields @endFieldsFor");
    }

    public function testFieldsForDoesNotEscapeForBlade() {
        $this->assertBlade('<b>Hello fields</b>', "@fieldsFor('post' as \$f)<b>Hello fields</b>@endFieldsFor");
    }

    public function testFieldsForDefinesBuilderVar() {
        $this->assertBlade('FormBuilder', "@fieldsFor('post' as \$f){{ class_basename(\$f) }}@endFieldsFor");
    }

    public function testFieldsForWithJustObject() {
        $this->assertBlade(
            'post[title]=Draft',
            "@fieldsFor(\$newPost as \$f){{ \$f->fieldName('title') }}={{ \$f->object->title }}@endFieldsFor"
        );
    }

    public function testFieldsForWithNameAndObject() {
        $this->assertBlade(
            'art[title]=Draft',
            "@fieldsFor('art', \$newPost as \$f){{ \$f->fieldName('title') }}={{ \$f->object->title }}@endFieldsFor"
        );
    }

    public function testFieldsForExpectsEndDirective() {
        $this->expectException(ViewException::class);
        $this->blade("@fieldsFor('post' as \$f)");
    }

    public function testFieldsForExpectsMatchingEndDirective() {
        $this->expectException(ViewException::class);
        $this->blade("@fieldsFor('post' as \$f) @endLabel");
    }

    public function testFieldsForUnderFormWith() {
        $expected = '<form accept-charset="UTF-8" action="/posts" method="post">'
            . '<label for="post_author_attributes_name">Name</label>'
            . '</form>';

        $blade = "@formWith(model: \$newPost as \$f)\n"
            . "@bldFieldsFor(\$f, 'author' as \$authorFields)\n"
            . "@bldLabel(\$authorFields, 'name')\n"
            . "@endBldFieldsFor\n"
            . "@endFormWith";

        $this->assertBlade($expected, $blade);
    }

    public function testFieldsForUnderFormWithMany() {
        $this->newPost->comments = $this->comments;

        $expected = '<form accept-charset="UTF-8" action="/posts" method="post">'
            . '<label for="post_comments_attributes_0_body">Body</label>'
            . '<label for="post_comments_attributes_1_body">Body</label>'
            . '</form>';

        $blade = "@formWith(model: \$newPost as \$f)\n"
            . "@bldFieldsFor(\$f, 'comments' as \$commentFields)\n"
            . "@bldLabel(\$commentFields, 'body')\n"
            . "@endBldFieldsFor\n"
            . "@endFormWith";

        $this->assertBlade($expected, $blade);
    }

    public function testLabel() {
        $this->assertDirectiveExists('label');
        $this->assertDirectiveExists('endLabel');
        $this->assertDirectiveExists('bldLabel');
        $this->assertDirectiveExists('endBldLabel');

        $this->assertBlade(
            '<label for="post_title">Hi </label>',
            "@label('post', 'title' as \$f)Hi @endLabel"
        );
    }

    public function testLabelDoesNotEscapeForBlade() {
        $this->assertBlade(
            '<label for="post_title"><b>Hello label</b></label>',
            "@label('post', 'title' as \$f)<b>Hello label</b>@endLabel"
        );
    }

    public function testLabelWithBuilderAsTranslation() {
        $this->assertBlade(
            '<label for="post_title"><span>Title</span></label>',
            "@label('post', 'title' as \$translation)@contentTag('span', \$translation)@endLabel"
        );
    }

    public function testLabelWithBuilderAsBuilder() {
        $this->assertBlade(
            '<label for="post_title"><span>Title</span></label>',
            "@label('post', 'title' as \$b)@contentTag('span', \$b->translation())@endLabel"
        );
    }

    public function testLabelInline() {
        $this->assertBlade(
            '<label for="post_title">Title</label>',
            "@label('post', 'title', 'Title')"
        );
    }

    public function testLabelInlineWithAttrs() {
        $this->assertBlade(
            '<label for="post_title" class="title_label">Title</label>',
            "@label('post', 'title', 'Title', ['class' => 'title_label'])"
        );
    }

    public function testLabelInlineWithValue() {
        $this->assertBlade(
            '<label for="post_title_public">Title</label>',
            "@label('post', 'title', 'Title', ['value' => 'public'])"
        );
    }

    public function testTextField() {
        $this->assertDirectiveExists('textField');
        $this->assertDirectiveExists('bldTextField');
        $this->assertDirectiveNotExists('endTextField');
        $this->assertDirectiveNotExists('endBldTextField');

        $this->assertBlade(
            '<input id="post_title" name="post[title]" type="text" value="Draft" />',
            "@textField('post', 'title', ['object' => \$newPost])"
        );

        $this->assertBlade(
            '<input id="post_title" name="post[title]" type="text" size="20"/>',
            "@textField('post', 'title', ['size' => 20])"
        );
    }

    public function testPasswordField() {
        $this->assertDirectiveExists('passwordField');
        $this->assertDirectiveExists('bldPasswordField');
        $this->assertDirectiveNotExists('endPasswordField');
        $this->assertDirectiveNotExists('endBldPasswordField');

        $this->assertBlade(
            '<input id="user_passwd" name="user[passwd]" type="password" />',
            "@passwordField('user', 'passwd')"
        );

        $this->assertBlade(
            '<input id="user_passwd" name="user[passwd]" type="password" size="20"/>',
            "@passwordField('user', 'passwd', ['size' => 20])"
        );
    }

    public function testHiddenField() {
        $this->assertDirectiveExists('hiddenField');
        $this->assertDirectiveExists('bldHiddenField');
        $this->assertDirectiveNotExists('endHiddenField');
        $this->assertDirectiveNotExists('endBldHiddenField');

        $this->assertBlade(
            '<input id="car_vin" name="car[vin]" type="hidden" autocomplete="off" value="H1234567890" />',
            "@hiddenField('car', 'vin', ['object' => \$car])"
        );

        $this->assertBlade(
            '<input id="user_token" name="user[token]" type="hidden" autocomplete="off" role="hidden"/>',
            "@hiddenField('user', 'token', ['role' => 'hidden'])"
        );
    }

    public function testFileField() {
        $this->assertDirectiveExists('fileField');
        $this->assertDirectiveExists('bldFileField');
        $this->assertDirectiveNotExists('endFileField');
        $this->assertDirectiveNotExists('endBldFileField');

        $this->assertBlade(
            '<input id="car_image" name="car[image]" type="file" />',
            "@fileField('car', 'image')"
        );

        $this->assertBlade(
            '<input id="car_image" name="car[image]" type="file" accept="image/png,image/gif,image/jpeg" />',
            "@fileField('car', 'image', ['accept' => 'image/png,image/gif,image/jpeg'])"
        );
    }

    public function testTextArea() {
        $this->assertDirectiveExists('textArea');
        $this->assertDirectiveExists('bldTextField');
        $this->assertDirectiveNotExists('endTextField');
        $this->assertDirectiveNotExists('endBldTextField');

        $this->assertBlade(
            '<textarea cols="20" rows="40" id="post_body" name="post[body]">',
            "@textArea('post', 'body', ['size' => '20x40'])"
        );

        $this->assertBlade(
            '<textarea id="post_body" name="post[body]">' . "\n" . 'This post exists</textarea>',
            "@textArea('post', 'body', ['object' => \$existingPost])",
            [],
            false
        );

        $this->assertBlade(
            '<textarea disabled="disabled" id="post_body" name="post[body]">',
            "@textArea('post', 'body', ['disabled' => true])"
        );
    }

    public function testCheckBox() {
        $this->assertDirectiveExists('checkBox');
        $this->assertDirectiveExists('bldCheckBox');
        $this->assertDirectiveNotExists('endCheckBox');
        $this->assertDirectiveNotExists('endBldCheckBox');

        $expected = '<input type="hidden" name="post[public]" value="0" autocomplete="off" />'
            . '<input type="checkbox" name="post[public]" id="post_public" value="1" />';

        $this->assertBlade(
            $expected,
            "@checkBox('post', 'public')"
        );

        $this->newPost->public = true;
        $expected = '<input type="hidden" name="post[public]" value="0" autocomplete="off" />'
            . '<input type="checkbox" name="post[public]" id="post_public" value="1" checked="checked" />';

        $this->assertBlade(
            $expected,
            "@checkBox('post', 'public', ['object' => \$newPost])"
        );
    }

    public function testCheckBoxWithArrayObjectValue() {
        $this->newPost->lucky_numbers = [11, 16, 72];
        $expected = '<input type="hidden" name="post[lucky_numbers][]" value="0" autocomplete="off" />'
            . '<input type="checkbox" name="post[lucky_numbers][]" id="post_lucky_numbers_72"'
            . ' value="72" checked="checked" />';

        $this->assertBlade(
            $expected,
            "@checkBox('post', 'lucky_numbers', ['multiple' => true, 'object' => \$newPost], 72)"
        );
    }

    public function testCheckBoxWithoutHidden() {
        $expected = '<input type="checkbox" name="post[public]" id="post_public" value="1" />';

        $this->assertBlade(
            $expected,
            "@checkBox('post', 'public', ['include_hidden' => false])"
        );
    }

    public function testCheckBoxValues() {
        $expected = '<input type="hidden" name="post[public]" value="nope" autocomplete="off" />'
            . '<input class="slangy" type="checkbox" name="post[public]" id="post_public" value="yup" />';

        $this->assertBlade(
            $expected,
            "@checkBox('post', 'public', ['class' => 'slangy'], 'yup', 'nope')"
        );
    }

    public function testRadioButton() {
        $this->assertDirectiveExists('radioButton');
        $this->assertDirectiveExists('bldRadioButton');
        $this->assertDirectiveNotExists('endRadioButton');
        $this->assertDirectiveNotExists('endBldRadioButton');

        $expected = '<input type="radio" id="post_category_php" name="post[category]" value="php" />';
        $this->assertBlade($expected, "@radioButton('post', 'category', 'php')");

        $this->newPost->category = 'php';
        $expected = '<input type="radio" id="post_category_php" name="post[category]" value="php" checked="checked" />';
        $this->assertBlade($expected, "@radioButton('post', 'category', 'php', ['object' => \$newPost])");

        $expected = '<input type="radio" id="post_read_no" name="post[read]" value="no" />';
        $this->assertBlade($expected, "@radioButton('post', 'read', 'no')");
    }

    public function testColorField() {
        $this->assertDirectiveExists('colorField');
        $this->assertDirectiveExists('bldColorField');
        $this->assertDirectiveNotExists('endColorField');
        $this->assertDirectiveNotExists('endBldColorField');

        $expected = '<input type="color" id="post_bgcolor" name="post[bgcolor]" value="#000000" />';
        $this->assertBlade($expected, "@colorField('post', 'bgcolor')");

        $this->newPost->bgcolor = '#FF0000';
        $expected = '<input type="color" id="post_bgcolor" name="post[bgcolor]" value="#ff0000" />';
        $this->assertBlade($expected, "@colorField('post', 'bgcolor', ['object' => \$newPost])");

        $this->newPost->bgcolor = 'red';
        $expected = '<input type="color" id="post_bgcolor" name="post[bgcolor]" value="#000000" />';
        $this->assertBlade($expected, "@colorField('post', 'bgcolor', ['object' => \$newPost])");
    }

    public function testSearchField() {
        $this->assertDirectiveExists('searchField');
        $this->assertDirectiveExists('bldSearchField');
        $this->assertDirectiveNotExists('endSearchField');
        $this->assertDirectiveNotExists('endBldSearchField');

        $expected = '<input type="search" id="post_title" name="post[title]" />';
        $this->assertBlade($expected, "@searchField('post', 'title')");

        $expected = '<input type="search" id="post_title" name="post[title]" autosave="localhost" results="10" />';
        $this->assertBlade($expected, "@searchField('post', 'title', ['autosave' => true])");
    }

    public function testTelField() {
        $this->assertDirectiveExists('telephoneField');
        $this->assertDirectiveNotExists('endTelephoneField');
        $this->assertDirectiveExists('phoneField');
        $this->assertDirectiveNotExists('endPhoneField');

        $expected = '<input type="tel" id="user_cell" name="user[cell]" />';
        $this->assertBlade($expected, "@phoneField('user', 'cell')");

        $expected = '<input type="tel" id="user_cell" name="user[cell]" class="missing" />';
        $this->assertBlade($expected, "@telephoneField('user', 'cell', ['class' => 'missing'])");
    }

    public function testDateField() {
        $this->assertDirectiveExists('dateField');
        $this->assertDirectiveExists('bldDateField');
        $this->assertDirectiveNotExists('endDateField');
        $this->assertDirectiveNotExists('endBldDateField');

        $expected = '<input type="date" id="post_as_of" name="post[as_of]" />';
        $this->assertBlade($expected, "@dateField('post', 'as_of')");

        $this->newPost->as_of = Carbon::parse('2023-07-01');
        $expected = '<input type="date" id="post_as_of" name="post[as_of]" value="2023-07-01" />';
        $this->assertBlade($expected, "@dateField('post', 'as_of', ['object' => \$newPost])");
    }

    public function testTimeField() {
        $this->assertDirectiveExists('timeField');
        $this->assertDirectiveExists('bldTimeField');
        $this->assertDirectiveNotExists('endTimeField');
        $this->assertDirectiveNotExists('endBldTimeField');

        $expected = '<input type="time" id="user_alarm" name="user[alarm]" />';
        $this->assertBlade($expected, "@timeField('user', 'alarm')");

        $expected = '<input type="time" id="user_alarm" name="user[alarm]" value="06:43:32" />';
        $this->assertBlade($expected, "@timeField('user', 'alarm', ['value' => Carbon\Carbon::parse('06:43:32')])");
    }

    public function testDateTimeField() {
        $this->assertDirectiveExists('datetimeField');
        $this->assertDirectiveExists('bldDatetimeField');
        $this->assertDirectiveNotExists('endDatetimeField');
        $this->assertDirectiveNotExists('endBldDatetimeField');

        $expected = '<input type="datetime-local" id="post_as_of" name="post[as_of]" />';
        $this->assertBlade($expected, "@datetimeField('post', 'as_of')");

        $this->newPost->as_of = Carbon::parse('2023-07-01T06:43:32');
        $expected = '<input type="datetime-local" id="post_as_of" name="post[as_of]" value="2023-07-01T06:43:32" />';
        $this->assertBlade($expected, "@datetimeField('post', 'as_of', ['object' => \$newPost])");
    }

    public function testMonthField() {
        $this->assertDirectiveExists('monthField');
        $this->assertDirectiveExists('bldMonthField');
        $this->assertDirectiveNotExists('endMonthField');
        $this->assertDirectiveNotExists('endBldMonthField');

        $expected = '<input type="month" id="post_as_of" name="post[as_of]" />';
        $this->assertBlade($expected, "@monthField('post', 'as_of')");

        $this->newPost->as_of = Carbon::parse('2023-07-01T06:43:32');
        $expected = '<input type="month" id="post_as_of" name="post[as_of]" value="2023-07" />';
        $this->assertBlade($expected, "@monthField('post', 'as_of', ['object' => \$newPost])");
    }

    public function testWeekField() {
        $this->assertDirectiveExists('weekField');
        $this->assertDirectiveExists('bldWeekField');
        $this->assertDirectiveNotExists('endWeekField');
        $this->assertDirectiveNotExists('endBldWeekField');

        $expected = '<input type="week" id="post_as_of" name="post[as_of]" />';
        $this->assertBlade($expected, "@weekField('post', 'as_of')");

        $this->newPost->as_of = Carbon::parse('1984-05-12');
        $expected = '<input type="week" id="post_as_of" name="post[as_of]" value="1984-W19" />';
        $this->assertBlade($expected, "@weekField('post', 'as_of', ['object' => \$newPost])");
    }

    public function testUrlField() {
        $this->assertDirectiveExists('urlField');
        $this->assertDirectiveExists('bldUrlField');
        $this->assertDirectiveNotExists('endUrlField');
        $this->assertDirectiveNotExists('endBldUrlField');

        $expected = '<input type="url" id="post_slug" name="post[slug]" />';
        $this->assertBlade($expected, "@urlField('post', 'slug')");

        $expected = '<input type="url" id="post_slug" name="post[slug]" class="int-link" />';
        $this->assertBlade($expected, "@urlField('post', 'slug', ['class' => 'int-link'])");
    }

    public function testEmailField() {
        $this->assertDirectiveExists('emailField');
        $this->assertDirectiveExists('bldEmailField');
        $this->assertDirectiveNotExists('endEmailField');
        $this->assertDirectiveNotExists('endBldEmailField');

        $expected = '<input type="email" id="post_contact" name="post[contact]" />';
        $this->assertBlade($expected, "@emailField('post', 'contact')");

        $expected = '<input type="email" id="post_contact" name="post[contact]" class="int-link" />';
        $this->assertBlade($expected, "@emailField('post', 'contact', ['class' => 'int-link'])");
    }

    public function testNumberField() {
        $this->assertDirectiveExists('numberField');
        $this->assertDirectiveExists('bldNumberField');
        $this->assertDirectiveNotExists('endNumberField');
        $this->assertDirectiveNotExists('endBldNumberField');

        $expected = '<input type="number" id="post_readers" name="post[readers]" />';
        $this->assertBlade($expected, "@numberField('post', 'readers')");

        $expected = '<input type="number" id="post_readers" name="post[readers]" min="0" />';
        $this->assertBlade($expected, "@numberField('post', 'readers', ['min' => 0])");
    }

    public function testRangeField() {
        $this->assertDirectiveExists('rangeField');
        $this->assertDirectiveExists('bldRangeField');
        $this->assertDirectiveNotExists('endRangeField');
        $this->assertDirectiveNotExists('endBldRangeField');

        $expected = '<input type="range" id="post_readers" name="post[readers]" />';
        $this->assertBlade($expected, "@rangeField('post', 'readers')");

        $expected = '<input type="range" id="post_readers" name="post[readers]" min="0" max="100" />';
        $this->assertBlade($expected, "@rangeField('post', 'readers', ['in' => [0,100]])");
    }
}

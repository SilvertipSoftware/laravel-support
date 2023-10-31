<?php

namespace Tests\Blade\FormHelper;

require_once __DIR__ . '/../../models/TestFormModels.php';

use App\Models\Car;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostDelegator;
use App\Models\Tag;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Stringable;
use Orchestra\Testbench\TestCase;
use RuntimeException;
use SilvertipSoftware\LaravelSupport\Blade\FormBuilder;
use SilvertipSoftware\LaravelSupport\Blade\FormHelper;
use SilvertipSoftware\LaravelSupport\Blade\FormOptionsHelper;
use SilvertipSoftware\LaravelSupport\Libs\StrUtils;
use SilvertipSoftware\LaravelSupport\Routing\RestRouter;
use Tests\TestSupport\HtmlAssertions;

class FormTest extends TestCase {
    use HtmlAssertions,
        FormHelper,
        FormOptionsHelper,
        FormHelperFixtures;

    public function setUp(): void {
        parent::setUp();
        static::$protectAgainstForgery = false;

        $this->createFixtures();
    }

    public function tearDown(): void {
        parent::tearDown();
        static::$formWithGeneratesIds = true;
        static::$protectAgainstForgery = true;
        static::$multipleFileFieldIncludeHidden = false;
        static::$defaultFormBuilderClass = FormBuilder::class;
        RestRouter::$shallowResources = true;
    }

    public function testLabel() {
        $this->assertDomEquals(
            '<label for="post_title">Title</label>',
            static::label('post', 'title')
        );
        $this->assertDomEquals(
            '<label for="post_title">Title goes here</label>',
            static::label('post', 'title', 'Title goes here')
        );
        $this->assertDomEquals(
            '<label class="title_label" for="post_title">Title</label>',
            static::label('post', 'title', null, ['class' => 'title_label'])
        );
    }

    public function testLabelWithTranslation() {
        Lang::setLocale('label');

        $this->assertDomEquals(
            '<label for="post_body">Write entire text here</label>',
            static::label('post', 'body')
        );
    }

    public function testLabelWithHumanAttributeName() {
        Lang::setLocale('label');

        $this->assertDomEquals(
            '<label for="post_cost">Total cost</label>',
            static::label('post', 'cost', ['object' => $this->post])
        );
    }

    public function testLabelWithHumanAttributeNameAndOptions() {
        Lang::setLocale('label');

        $this->assertDomEquals(
            '<label for="post_language_spanish">Español</label>',
            static::label('post', 'language', ['value' => 'spanish', 'object' => $this->post])
        );
    }

    public function testLabelWithTranslationAndOptions() {
        Lang::setLocale('label');

        $this->assertDomEquals(
            '<label for="post_body" class="post_body">Write entire text here</label>',
            static::label('post', 'body', ['class' => 'post_body', 'object' => $this->post])
        );
    }

    public function testLabelWithTranslationAndValue() {
        Lang::setLocale('label');

        $this->assertDomEquals(
            '<label for="post_color_red">Rojo</label>',
            static::label('post', 'color', ['value' => 'red', 'object' => $this->post])
        );
    }

    public function testLabelWithTranslationAndNestedAttributes() {
        Lang::setLocale('label');

        $rendered = static::formWith(
            model: $this->post,
            options: ['html' => ['id' => 'create-post']],
            block: function ($f) {
                return $f->fieldsFor('comments', null, [], function ($cf) {
                    return $cf->label('body');
                });
            }
        );

        $expected = $this->wholeForm('/posts/123', 'create-post', null, ['method' => 'patch'], function () {
            return '<label for="post_comments_attributes_0_body">Write body here</label>';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testLabelWithTranslationFallbackAndNestedAttributes() {
        Lang::setLocale('label');

        $rendered = static::formWith(
            model: $this->post,
            options: ['html' => ['id' => 'create-post']],
            block: function ($f) {
                return $f->fieldsFor('tags', null, [], function ($cf) {
                    return $cf->label('value');
                });
            }
        );

        $expected = $this->wholeForm('/posts/123', 'create-post', null, ['method' => 'patch'], function () {
            return '<label for="post_tags_attributes_0_value">Tag</label>';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testLabelWithNonModels() {
        $record = new \stdClass(['name' => 'ok']);
        $opts = [
            'as' => 'person',
            'html' => ['id' => 'create-person']
        ];

        $actual = static::formWith(model: $record, scope: 'person', url: '/an', options: $opts, block: function ($f) {
            return $f->label('name');
        });

        $expected = $this->wholeForm('/an', 'create-person', null, ['method' => 'post'], function () {
            return '<label for="person_name">Name</label>';
        });

        $this->assertDomEquals($expected, $actual);
    }

    public function testLabelWithCustomForAttribute() {
        $this->assertDomEquals(
            '<label for="my_for">Title</label>',
            static::label('post', 'title', null, ['for' => 'my_for', 'object' => $this->post])
        );
    }

    public function testLabelWithCustomIdAttribute() {
        $this->assertDomEquals(
            '<label for="post_title" id="my_id">Title</label>',
            static::label('post', 'title', null, ['id' => 'my_id', 'object' => $this->post])
        );
    }

    public function testLabelWithCustomForAnIdAttributes() {
        $this->assertDomEquals(
            '<label for="my_for" id="my_id">Title</label>',
            static::label('post', 'title', null, ['for' => 'my_for', 'id' => 'my_id', 'object' => $this->post])
        );
    }

    public function testLabelForUseWithRadioButtonsWithValue() {
        $this->assertDomEquals(
            '<label for="post_title_great_title">The title goes here</label>',
            static::label('post', 'title', 'The title goes here', ['value' => 'great_title', 'object' => $this->post])
        );
        $this->assertDomEquals(
            '<label for="post_title_great_title">The title goes here</label>',
            static::label('post', 'title', 'The title goes here', ['value' => 'great title', 'object' => $this->post])
        );
    }

    public function testLabelWithBlock() {
        $this->assertDomEquals(
            '<label for="post_title">The title, please:</label>',
            static::label('post', 'title', block: function () {
                return 'The title, please:';
            })
        );
    }

    public function testLabelWithBlockAndHtml() {
        $this->assertDomEquals(
            '<label for="post_terms">Accept <a href="/terms">Terms</a>.</label>',
            static::label('post', 'terms', ['object' => $this->post], block: function () {
                return new HtmlString('Accept <a href="/terms">Terms</a>.');
            })
        );
    }

    public function testLabelWithBlockAndOptions() {
        $this->assertDomEquals(
            '<label for="my_for">The title, please:</label>',
            static::label('post', 'title', ['for' => 'my_for', 'object' => $this->post], block: function () {
                return 'The title, please:';
            })
        );
    }

    public function testLabelWithBlockAndBuilder() {
        Lang::setLocale('label');

        $this->assertDomEquals(
            '<label for="post_body"><b>Write entire text here</b></label>',
            static::label('post', 'body', ['object' => $this->post], block: function ($b) {
                return new HtmlString('<b>' . $b->translation() . '</b>');
            })
        );
    }

    public function testLabelWithToModel() {
        $this->assertDomEquals(
            '<label for="post_delegator_title">Delegate Title</label>',
            static::label('post_delegator', 'title', ['object' => $this->postDelegator])
        );
    }

    public function testLabelWithToModelAndOverriddenModelName() {
        Lang::setLocale('label');

        $this->assertDomEquals(
            '<label for="post_delegator_title">Delegate model_name title</label>',
            static::label('post_delegator', 'title', ['object' => $this->postDelegator])
        );
    }

    public function testLabelWithErrors() {
        $expected = '<div class="field_with_errors"><label for="post_body">Body</label></div>';

        $this->assertDomEquals($expected, static::label('post', 'body', ['object' => $this->badPost]));
    }

    public function testFieldsFor() {
        $rendered = static::fieldsFor('post', $this->post, [], function ($f) {
            return $f->textField('title')
                . $f->textArea('body')
                . $f->checkBox('secret');
        });

        $expected = '<input name="post[title]" type="text" id="post_title" value="Hello World" />'
            . '<textarea name="post[body]" id="post_body">' . "\n" . 'This is a post</textarea>'
            . '<input name="post[secret]" type="hidden" value="0" autocomplete="off" />'
            . '<input name="post[secret]" checked="checked" type="checkbox" id="post_secret" value="1" />';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFieldsForFieldId() {
        static::fieldsFor('post', $this->post, block: function ($f) {
            $this->assertEquals('post_title', $f->fieldId('title'));
        });
    }

    public function testFieldsForWithIndex() {
        $rendered = static::fieldsFor('post[]', $this->post, [], function ($f) {
            return $f->textField('title')
                . $f->textArea('body')
                . $f->checkBox('secret');
        });

        $expected = '<input name="post[123][title]" type="text" id="post_123_title" value="Hello World" />'
            . '<textarea name="post[123][body]" id="post_123_body">' . "\n" . 'This is a post</textarea>'
            . '<input name="post[123][secret]" type="hidden" value="0" autocomplete="off" />'
            . '<input name="post[123][secret]" checked="checked" type="checkbox" id="post_123_secret" value="1" />';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFieldsForFieldIdWithIndexOption() {
        static::fieldsFor('post', $this->post, [], function ($f) {
            $this->assertEquals('post_5_title', $f->fieldId('title', [], null, 5));
        });
    }

    public function testFieldsForWithNullIndexOverride() {
        $rendered = static::fieldsFor('post[]', $this->post, ['index' => null], function ($f) {
            return $f->textField('title')
                . $f->textArea('body')
                . $f->checkBox('secret');
        });

        $expected = '<input name="post[][title]" type="text" id="post__title" value="Hello World" />'
            . '<textarea name="post[][body]" id="post__body">' . "\n" . 'This is a post</textarea>'
            . '<input name="post[][secret]" type="hidden" value="0" autocomplete="off" />'
            . '<input name="post[][secret]" checked="checked" type="checkbox" id="post__secret" value="1" />';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFieldsForWithNullIndexOverrideFieldId() {
        static::fieldsFor('post', $this->post, ['index' => 1], function ($f) {
            $this->assertEquals('post_1_title', $f->fieldId('title'));
        });
    }

    public function testFieldsForWithIndexOverride() {
        $rendered = static::fieldsFor('post[]', $this->post, ['index' => 'abc'], function ($f) {
            return $f->textField('title')
                . $f->textArea('body')
                . $f->checkBox('secret');
        });

        $expected = '<input name="post[abc][title]" type="text" id="post_abc_title" value="Hello World" />'
            . '<textarea name="post[abc][body]" id="post_abc_body">' . "\n" . 'This is a post</textarea>'
            . '<input name="post[abc][secret]" type="hidden" value="0" autocomplete="off" />'
            . '<input name="post[abc][secret]" checked="checked" type="checkbox" id="post_abc_secret" value="1" />';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFieldsForWithOnlyObject() {
        $rendered = static::fieldsFor($this->post, block: function ($f) {
            return $f->textField('title')
                . $f->textArea('body')
                . $f->checkBox('secret');
        });

        $expected = '<input name="post[title]" type="text" id="post_title" value="Hello World" />'
            . '<textarea name="post[body]" id="post_body">' . "\n" . 'This is a post</textarea>'
            . '<input name="post[secret]" type="hidden" value="0" autocomplete="off" />'
            . '<input name="post[secret]" checked="checked" type="checkbox" id="post_secret" value="1" />';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFieldsForObjectWithBracketedName() {
        $rendered = static::fieldsFor('author[post]', $this->post, [], function ($f) {
            return $f->label('title')
                . $f->textField('title');
        });

        $expected = '<label for="author_post_title">Title</label>'
            . '<input name="author[post][title]" type="text" id="author_post_title" value="Hello World" />';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFieldsForObjectWithBracketedNameAndIndex() {
        $rendered = static::fieldsFor('author[post]', $this->post, ['index' => 1], function ($f) {
            return $f->label('title')
                . $f->textField('title');
        });

        $expected = '<label for="author_post_1_title">Title</label>'
            . '<input name="author[post][1][title]" type="text" id="author_post_1_title" value="Hello World" />';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithAndFieldsFor() {
        $rendered = static::formWith(
            model: $this->post,
            options: ['as' => 'post', 'html' => ['id' => 'create-post']],
            block: function ($f) {
                return $f->textField('title')
                    . $f->textArea('body')
                    . static::fieldsFor('parent_post', $this->post, [], function ($pf) {
                        return $pf->checkBox('secret');
                    });
            }
        );

        $expected = $this->wholeForm('/posts/123', 'create-post', null, ['method' => 'patch'], function () {
            return '<input name="post[title]" type="text" id="post_title" value="Hello World" />'
                . '<textarea name="post[body]" id="post_body">' . "\n" . 'This is a post</textarea>'
                . '<input name="parent_post[secret]" type="hidden" value="0" autocomplete="off" />'
                . '<input name="parent_post[secret]" checked="checked" type="checkbox" id="parent_post_secret"'
                . ' value="1" />';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithAndFieldsForWithObject() {
        $rendered = static::formWith(
            model: $this->post,
            options: ['as' => 'post', 'html' => ['id' => 'create-post']],
            block: function ($f) {
                return $f->textField('title')
                    . $f->textArea('body')
                    . $f->fieldsFor($this->comment, null, [], function ($cf) {
                        return $cf->textField('name');
                    });
            }
        );

        $expected = $this->wholeForm('/posts/123', 'create-post', null, ['method' => 'patch'], function () {
            return '<input name="post[title]" type="text" id="post_title" value="Hello World" />'
                . '<textarea name="post[body]" id="post_body">' . "\n" . 'This is a post</textarea>'
                . '<input name="post[comment][name]" type="text" id="post_comment_name"'
                . ' value="new comment" />';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithAndFieldsForWithNonNestedRelationshipAndWithoutObject() {
        $rendered = static::formWith(model: $this->post, block: function ($f) {
            return $f->fieldsFor('category', null, [], function ($c) {
                return $c->textField('name');
            });
        });

        $expected = $this->wholeForm('/posts/123', null, null, ['method' => 'patch'], function () {
            return '<input name="post[category][name]" type="text" id="post_category_name" />';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithSpecifiedLabelledBuilder() {
        $rendered = static::formWith(
            model: $this->post,
            options: ['builder' => $this->labelledBuilderClass()],
            block: function ($f) {
                return $f->textField('title') . $f->textArea('body') . $f->checkBox('secret');
            }
        );

        $expected = $this->wholeForm('/posts/123', null, null, ['method' => 'patch'], function () {
            return '<label for="title">Title:</label> '
                . '<input name="post[title]" type="text" id="post_title" value="Hello World" /><br/>'
                . '<label for="body">Body:</label> '
                . '<textarea name="post[body]" id="post_body">' . "\n" . 'This is a post</textarea><br/>'
                . '<label for="secret">Secret:</label> '
                . '<input name="post[secret]" type="hidden" value="0" autocomplete="off" />'
                . '<input name="post[secret]" checked="checked" type="checkbox" id="post_secret" value="1" /><br/>';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testDefaultFormBuilder() {
        static::$defaultFormBuilderClass = $this->labelledBuilderClass();

        $rendered = static::formWith(model: $this->post, block: function ($f) {
            return $f->textField('title') . $f->textArea('body') . $f->checkBox('secret');
        });

        $expected = $this->wholeForm('/posts/123', null, null, ['method' => 'patch'], function () {
            return '<label for="title">Title:</label> '
                . '<input name="post[title]" type="text" id="post_title" value="Hello World" /><br/>'
                . '<label for="body">Body:</label> '
                . '<textarea name="post[body]" id="post_body">' . "\n" . 'This is a post</textarea><br/>'
                . '<label for="secret">Secret:</label> '
                . '<input name="post[secret]" type="hidden" value="0" autocomplete="off" />'
                . '<input name="post[secret]" checked="checked" type="checkbox" id="post_secret" value="1" /><br/>';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFieldsForWithLabelledBuilder() {
        $rendered = static::fieldsFor('post', $this->post, ['builder' => $this->labelledBuilderClass()], function ($f) {
            return $f->textField('title') . $f->textArea('body') . $f->checkBox('secret');
        });

        $expected = '<label for="title">Title:</label> '
            . '<input name="post[title]" type="text" id="post_title" value="Hello World" /><br/>'
            . '<label for="body">Body:</label> '
            . '<textarea name="post[body]" id="post_body">' . "\n" . 'This is a post</textarea><br/>'
            . '<label for="secret">Secret:</label> '
            . '<input name="post[secret]" type="hidden" value="0" autocomplete="off" />'
            . '<input name="post[secret]" checked="checked" type="checkbox" id="post_secret" value="1" /><br/>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormForWithLabelledBuilderWithNestedFieldsForWithoutOptions() {
        $clazz = null;
        $builderClazz = $this->labelledBuilderClass();

        static::formWith(model: $this->post, options: ['builder' => $builderClazz], block: function ($f) use (&$clazz) {
            return $f->fieldsFor('comments', new Comment(), [], function ($nf) use (&$clazz) {
                $clazz = get_class($nf);
            });
        });

        $this->assertEquals($builderClazz, $clazz);
    }

    public function testFieldIdWithModel() {
        $value = static::fieldId(new Post(), 'title');
        $this->assertEquals('post_title', $value);
    }

    public function testFieldForFieldIdWithIndex() {
        $rendered = static::formWith(model: new Post(), options: ['index' => 1], block: function ($f) {
            return $f->textField('title', ['aria' => ['describedby' => $f->fieldId('title', 'error')]])
                . static::tag()->span('is blank', ['id' => $f->fieldId('title', 'error')]);
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input id="post_1_title" name="post[1][title]" type="text" aria-describedby="post_1_title_error">'
                . '<span id="post_1_title_error">is blank</span>';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFieldForFieldIdWithNamespace() {
        $rendered = static::formWith(model: new Post(), options: ['namespace' => 'special'], block: function ($f) {
            return $f->label('title')
                . $f->textField('title', ['aria' => ['describedby' => $f->fieldId('title', 'error')]])
                . static::tag()->span('is blank', ['id' => $f->fieldId('title', 'error')]);
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<label for="special_post_title">Title</label>'
                . '<input id="special_post_title" name="post[title]" type="text"'
                . ' aria-describedby="special_post_title_error">'
                . '<span id="special_post_title_error">is blank</span>';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFieldWithFieldNameWithBlankScopeAndMultiple() {
        $rendered = static::formWith(model: new Post(), scope: '', block: function ($f) {
            return $f->textField('title', ['name' => $f->fieldName('title', [], true)]);
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input id="title" name="title[]" type="text">';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithFieldNameWithoutMethodNamesOrMulitpleOrIndex() {
        $rendered = static::formWith(model: new Post(), block: function ($f) {
            return $f->textField('title', ['name' => $f->fieldName('title')]);
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input id="post_title" name="post[title]" type="text">';
        });
        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithFieldNameWithoutMethodNamesAndMulitple() {
        $rendered = static::formWith(model: new Post(), block: function ($f) {
            return $f->textField('title', ['name' => $f->fieldName('title', [], true)]);
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input id="post_title" name="post[title][]" type="text">';
        });
        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithFieldNameWithoutMethodNamesAndIndex() {
        $rendered = static::formWith(model: new Post(), options: ['index' => 1], block: function ($f) {
            return $f->textField('title', ['name' => $f->fieldName('title')]);
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input id="post_1_title" name="post[1][title]" type="text">';
        });
        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormForFieldNameWithoutMethodNamesAndIndexAndMultiple() {
        $rendered = static::formWith(model: new Post(), options: ['index' => 1], block: function ($f) {
            return $f->textField('title', ['name' => $f->fieldName('title', [], true)]);
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input id="post_1_title" name="post[1][title][]" type="text">';
        });
        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithFieldNameWithMethodNames() {
        $rendered = static::formWith(model: new Post(), block: function ($f) {
            return $f->textField('title', ['name' => $f->fieldName('title', 'subtitle')]);
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input id="post_title" name="post[title][subtitle]" type="text">';
        });
        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithFieldNameWithMethodNamesAndIndex() {
        $rendered = static::formWith(model: new Post(), options: ['index' => 1], block: function ($f) {
            return $f->textField('title', ['name' => $f->fieldName('title', 'subtitle')]);
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input id="post_1_title" name="post[1][title][subtitle]" type="text">';
        });
        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithFieldNameWithMethodNamesAndMultiple() {
        $rendered = static::formWith(model: new Post(), block: function ($f) {
            return $f->textField('title', ['name' => $f->fieldName('title', 'subtitle', true)]);
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input id="post_title" name="post[title][subtitle][]" type="text">';
        });
        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithFieldNameWithMethodNamesAndMultipleAndIndex() {
        $rendered = static::formWith(model: new Post(), options: ['index' => 1], block: function ($f) {
            return $f->textField('title', ['name' => $f->fieldName('title', 'subtitle', true)]);
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input id="post_1_title" name="post[1][title][subtitle][]" type="text">';
        });
        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithFieldIdWithNamespaceAndIndex() {
        $rendered = static::formWith(
            model: new Post(),
            options: ['namespace' => 'special', 'index' => 1],
            block: function ($f) {
                return $f->textField('title', ['aria' => ['describedby' => $f->fieldId('title', 'error')]])
                    . static::tag()->span('is blank', ['id' => $f->fieldId('title', 'error')]);
            }
        );

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input id="special_post_1_title" name="post[1][title]" type="text"'
                . ' aria-describedby="special_post_1_title_error">'
                . '<span id="special_post_1_title_error">is blank</span>';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithNestedAttributesFieldId() {
        list($post, $comment, $tag) = [new Post(), new Comment(), new Tag()];
        $comment->relevances = collect([$tag]);
        $post->comments = collect([$comment]);

        $rendered = static::formWith(model: $post, block: function ($f) {
            return $f->fieldsFor('comments', null, [], function ($cf) {
                return $cf->fieldId('relevances_attributes');
            });
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return 'post_comments_attributes_0_relevances_attributes';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithNestedAttributesFieldName() {
        list($post, $comment, $tag) = [new Post(), new Comment(), new Tag()];
        $comment->relevances = collect([$tag]);
        $post->comments = collect([$comment]);

        $rendered = static::formWith(model: $post, block: function ($f) {
            return $f->fieldsFor('comments', null, [], function ($cf) {
                return $cf->fieldName('relevances_attributes');
            });
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return 'post[comments_attributes][0][relevances_attributes]';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithNestedAttributesFieldNameMultiple() {
        list($post, $comment, $tag) = [new Post(), new Comment(), new Tag()];
        $comment->relevances = collect([$tag]);
        $post->comments = collect([$comment]);

        $rendered = static::formWith(model: $post, block: function ($f) {
            return $f->fieldsFor('comments', null, [], function ($cf) {
                return $cf->fieldName('relevances_attributes', [], true);
            });
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return 'post[comments_attributes][0][relevances_attributes][]';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithCollectionRadioButtons() {
        $post = new Post(['active' => false]);

        $ident = function ($v) {
            return $v;
        };
        $toString = function ($v) {
            return $v ? 'true' : 'false';
        };

        $rendered = static::formWith(model: $post, block: function ($f) use ($ident, $toString) {
            return $f->collectionRadioButtons('active', [true, false], $ident, $toString);
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input type="hidden" name="post[active]" value="" autocomplete="off" />'
                . '<input id="post_active_true" name="post[active]" type="radio" value="true" />'
                . '<label for="post_active_true">true</label>'
                . '<input checked="checked" id="post_active_false" name="post[active]" type="radio" value="false" />'
                . '<label for="post_active_false">false</label>';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithCollectionRadioButtonsWithCustomBuilderBlock() {
        $post = new Post(['active' => false]);


        $ident = function ($v) {
            return $v;
        };
        $toString = function ($v) {
            return $v ? 'true' : 'false';
        };

        $rendered = static::formWith(model: $post, block: function ($f) use ($ident, $toString) {
            return $f->collectionRadioButtons('active', [true, false], $ident, $toString, [], [], function ($b) {
                return $b->label([], function () use ($b) {
                    return new HtmlString($b->radioButton() . $b->text);
                });
            });
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input type="hidden" name="post[active]" value="" autocomplete="off" />'
                . '<label for="post_active_true">'
                . '<input id="post_active_true" name="post[active]" type="radio" value="true" />'
                . 'true</label>'
                . '<label for="post_active_false">'
                . '<input checked="checked" id="post_active_false" name="post[active]" type="radio" value="false" />'
                . 'false</label>';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithCollectionRadioButtonsWithCustomBuilderBlockDoesNotLeakTemplate() {
        $post = new Post(['id' => 1, 'active' => false]);


        $ident = function ($v) {
            return $v;
        };
        $toString = function ($v) {
            return $v ? 'true' : 'false';
        };

        $rendered = static::formWith(model: $post, block: function ($f) use ($ident, $toString) {
            $radios = $f->collectionRadioButtons('active', [true, false], $ident, $toString, [], [], function ($b) {
                return $b->label([], function () use ($b) {
                    return new HtmlString($b->radioButton() . $b->text);
                });
            });

            return $radios . $f->hiddenField('id');
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input type="hidden" name="post[active]" value="" autocomplete="off" />'
                . '<label for="post_active_true">'
                . '<input id="post_active_true" name="post[active]" type="radio" value="true" />'
                . 'true</label>'
                . '<label for="post_active_false">'
                . '<input checked="checked" id="post_active_false" name="post[active]" type="radio" value="false" />'
                . 'false</label>'
                . '<input id="post_id" name="post[id]" type="hidden" value="1" autocomplete="off" />';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithNamespaceAndWithCollectionRadioButtons() {
        $post = new Post(['active' => false]);


        $ident = function ($v) {
            return $v;
        };
        $toString = function ($v) {
            return $v ? 'true' : 'false';
        };

        $rendered = static::formWith(
            model: $post,
            options: ['namespace' => 'foo'],
            block: function ($f) use ($ident, $toString) {
                return $f->collectionRadioButtons('active', [true, false], $ident, $toString);
            }
        );

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input type="hidden" name="post[active]" value="" autocomplete="off" />'
                . '<input id="foo_post_active_true" name="post[active]" type="radio" value="true" />'
                . '<label for="foo_post_active_true">true</label>'
                . '<input checked="checked" id="foo_post_active_false" name="post[active]" type="radio"'
                . ' value="false" />'
                . '<label for="foo_post_active_false">false</label>';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithIndexAndWithCollectionRadioButtons() {
        $post = new Post(['active' => false]);


        $ident = function ($v) {
            return $v;
        };
        $toString = function ($v) {
            return $v ? 'true' : 'false';
        };

        $rendered = static::formWith(
            model: $post,
            options: ['index' => 1],
            block: function ($f) use ($ident, $toString) {
                return $f->collectionRadioButtons('active', [true, false], $ident, $toString);
            }
        );

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input type="hidden" name="post[1][active]" value="" autocomplete="off" />'
                . '<input id="post_1_active_true" name="post[1][active]" type="radio" value="true" />'
                . '<label for="post_1_active_true">true</label>'
                . '<input checked="checked" id="post_1_active_false" name="post[1][active]" type="radio"'
                . ' value="false" />'
                . '<label for="post_1_active_false">false</label>';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithCollectionCheckBoxes() {
        $post = new Post(['tag_ids' => [1, 3]]);

        $collection = [1 => 'Tag 1', 2 => 'Tag 2', 3 => 'Tag 3'];
        $valueFn = function ($v, $k) {
            return $v;
        };
        $keyFn = function ($v, $k) {
            return $k;
        };
        $rendered = static::formWith(model: $post, block: function ($f) use ($collection, $valueFn, $keyFn) {
            return $f->collectionCheckBoxes('tag_ids', $collection, $keyFn, $valueFn);
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input name="post[tag_ids][]" type="hidden" value="" autocomplete="off" />'
                . '<input checked="checked" id="post_tag_ids_1" name="post[tag_ids][]" type="checkbox" value="1" />'
                . '<label for="post_tag_ids_1">Tag 1</label>'
                . '<input id="post_tag_ids_2" name="post[tag_ids][]" type="checkbox" value="2" />'
                . '<label for="post_tag_ids_2">Tag 2</label>'
                . '<input checked="checked" id="post_tag_ids_3" name="post[tag_ids][]" type="checkbox" value="3" />'
                . '<label for="post_tag_ids_3">Tag 3</label>';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithCollectionCheckBoxesWithCustomBuilderBlock() {
        $post = new Post(['tag_ids' => [1, 3]]);

        $collection = [1 => 'Tag 1', 2 => 'Tag 2', 3 => 'Tag 3'];
        $valueFn = function ($v, $k) {
            return $v;
        };
        $keyFn = function ($v, $k) {
            return $k;
        };
        $rendered = static::formWith(model: $post, block: function ($f) use ($collection, $valueFn, $keyFn) {
            return $f->collectionCheckBoxes('tag_ids', $collection, $keyFn, $valueFn, [], [], function ($b) {
                return $b->label([], function () use ($b) {
                    return new HtmlString($b->checkBox() . $b->text);
                });
            });
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input name="post[tag_ids][]" type="hidden" value="" autocomplete="off" />'
                . '<label for="post_tag_ids_1">'
                . '<input checked="checked" id="post_tag_ids_1" name="post[tag_ids][]" type="checkbox" value="1" />'
                . 'Tag 1</label>'
                . '<label for="post_tag_ids_2">'
                . '<input id="post_tag_ids_2" name="post[tag_ids][]" type="checkbox" value="2" />'
                . 'Tag 2</label>'
                . '<label for="post_tag_ids_3">'
                . '<input checked="checked" id="post_tag_ids_3" name="post[tag_ids][]" type="checkbox" value="3" />'
                . 'Tag 3</label>';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithCollectionCheckBoxesWithCustomBuilderBlockDoesNotLeakTemplate() {
        $post = new Post(['id' => 1, 'tag_ids' => [1, 3]]);

        $collection = [1 => 'Tag 1', 2 => 'Tag 2', 3 => 'Tag 3'];
        $valueFn = function ($v, $k) {
            return $v;
        };
        $keyFn = function ($v, $k) {
            return $k;
        };
        $rendered = static::formWith(model: $post, block: function ($f) use ($collection, $valueFn, $keyFn) {
            $checks = $f->collectionCheckBoxes('tag_ids', $collection, $keyFn, $valueFn, [], [], function ($b) {
                return $b->label([], function () use ($b) {
                    return new HtmlString($b->checkBox() . $b->text);
                });
            });

            return $checks . $f->hiddenField('id');
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input name="post[tag_ids][]" type="hidden" value="" autocomplete="off" />'
                . '<label for="post_tag_ids_1">'
                . '<input checked="checked" id="post_tag_ids_1" name="post[tag_ids][]" type="checkbox" value="1" />'
                . 'Tag 1</label>'
                . '<label for="post_tag_ids_2">'
                . '<input id="post_tag_ids_2" name="post[tag_ids][]" type="checkbox" value="2" />'
                . 'Tag 2</label>'
                . '<label for="post_tag_ids_3">'
                . '<input checked="checked" id="post_tag_ids_3" name="post[tag_ids][]" type="checkbox" value="3" />'
                . 'Tag 3</label>'
                . '<input id="post_id" name="post[id]" type="hidden" value="1" autocomplete="off" />';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithNamespaceAndWithCollectionCheckBoxes() {
        $post = new Post(['tag_ids' => [1, 3]]);

        $collection = [[1, 'Tag 1']];
        $rendered = static::formWith(
            model: $post,
            options: ['namespace' => 'foo'],
            block: function ($f) use ($collection) {
                return $f->collectionCheckBoxes('tag_ids', $collection, 0, 1);
            }
        );

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input name="post[tag_ids][]" type="hidden" value="" autocomplete="off" />'
                . '<input checked="checked" id="foo_post_tag_ids_1" name="post[tag_ids][]" type="checkbox" value="1" />'
                . '<label for="foo_post_tag_ids_1">Tag 1</label>';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithIndexAndWithCollectionCheckBoxes() {
        $post = new Post(['tag_ids' => [1, 3]]);

        $collection = [[1, 'Tag 1']];
        $rendered = static::formWith(model: $post, options: ['index' => '1'], block: function ($f) use ($collection) {
            return $f->collectionCheckBoxes('tag_ids', $collection, 0, 1);
        });

        $expected = $this->wholeForm('/posts', null, null, [], function () {
            return '<input name="post[1][tag_ids][]" type="hidden" value="" autocomplete="off" />'
                . '<input checked="checked" id="post_1_tag_ids_1" name="post[1][tag_ids][]" type="checkbox"'
                . ' value="1" />'
                . '<label for="post_1_tag_ids_1">Tag 1</label>';
        });

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithRecordUrlOption() {
        $rendered = static::formWith(model: $this->post, url: $this->post, block: function ($f) {
        });
        $expected = $this->wholeForm('/posts/123', null, null, ['method' => 'patch']);
        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithExistingObject() {
        $rendered = static::formWith(model: $this->post, block: function ($f) {
        });
        $expected = $this->wholeForm('/posts/123', null, null, ['method' => 'patch']);
        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithNewObject() {
        $rendered = static::formWith(model: new Post(), block: function ($f) {
        });
        $expected = $this->wholeForm('/posts', null, null);
        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithNewObjectInList() {
        $rendered = static::formWith(model: [$this->post, $this->comment], block: function ($f) {
        });
        $expected = $this->wholeForm('/posts/123/comments', null, null);
        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithExistingObjectAndNamespaceInList() {
        $this->comment->exists = true;
        $this->comment->id = 1;
        RestRouter::$shallowResources = false;

        $rendered = static::formWith(model: ['admin', $this->post, $this->comment], block: function ($f) {
        });
        $expected = $this->wholeForm('/admin/posts/123/comments/1', null, null, ['method' => 'patch']);

        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithNewObjectAndNamespaceInList() {
        $rendered = static::formWith(model: ['admin', $this->post, $this->comment], block: function ($f) {
        });
        $expected = $this->wholeForm('/admin/posts/123/comments', null, null);
        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithExistingObjectAndCustomUrl() {
        $rendered = static::formWith(model: $this->post, url: '/super_posts', block: function ($f) {
        });
        $expected = $this->wholeForm('/super_posts', null, null, ['method' => 'patch']);
        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithDefaultMethodAsPatch() {
        $rendered = static::formWith(model: $this->post, block: function ($f) {
        });
        $expected = $this->wholeForm('/posts/123', null, null, ['method' => 'patch']);
        $this->assertDomEquals($expected, $rendered);
    }

    public function testFormWithWithDataAttributes() {
        $rendered = static::formWith(
            model: $this->post,
            options: ['data' => ['behavior' => 'stuff'], 'remote' => true],
            block: function ($f) {
            }
        );

        $this->assertMatchesRegularExpression('/data-behavior="stuff"/', $rendered);
        $this->assertMatchesRegularExpression('/data-remote="true"/', $rendered);
    }

    public function testFieldsForReturnsBlockResult() {
        $output = static::fieldsFor(new Post(), null, [], function ($f) {
            return 'fields';
        });

        $this->assertEquals('fields', $output);
    }

    public function testFormWithOnlyInstantiatesBuilderOnce() {
        $clazz = $this->labelledBuilderClass();
        $clazz::$instantiationCount = 0;

        static::formWith(model: $this->post, options: ['builder' => $clazz], block: function ($f) {
        });

        $count = $clazz::$instantiationCount;

        $this->assertEquals(1, $count);
    }

    private function labelledBuilderClass() {
        $builder = new class('', null, static::class, []) extends FormBuilder {
            public static $instantiationCount = 0;

            public function __construct($objectName, $object, $template, $options) {
                parent::__construct($objectName, $object, $template, $options);
                static::$instantiationCount++;
            }

            public function textField($field, $options = []): HtmlString {
                return $this->labelWrap($field, parent::textField($field, $options));
            }

            public function textArea($field, $options = []): HtmlString {
                return $this->labelWrap($field, parent::textArea($field, $options));
            }

            public function checkBox(
                string $field,
                array $opts = [],
                string|int|bool $checkedValue = "1",
                string|int|bool|null $uncheckedValue = "0"
            ): HtmlString {
                return $this->labelWrap($field, parent::checkBox($field, $opts, $checkedValue, $uncheckedValue));
            }

            private function labelWrap($field, $content): HtmlString {
                return new HtmlString(
                    '<label for="' . $field . '">' . StrUtils::humanize($field) . ':</label> ' . $content . '<br/>'
                );
            }
        };

        return get_class($builder);
    }

    private function otherBuilderClass() {
        $builder = new class('', null, static::class, []) extends FormBuilder {
        };

        return get_class($builder);
    }
}

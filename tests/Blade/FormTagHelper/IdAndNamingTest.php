<?php

namespace Tests\Blade;

use Illuminate\Support\HtmlString;
use PHPUnit\Framework\TestCase;
use SilvertipSoftware\LaravelSupport\Blade\FormTagHelper;
use Tests\TestSupport\HtmlAssertions;

class IdAndNamingTest extends TestCase {
    use HtmlAssertions,
        FormTagHelper;

    public function testFieldIdWithoutSuffixesOrIndex() {
        $value = static::fieldId('post', 'title');

        $this->assertEquals('post_title', $value);
    }

    public function testFieldIdWithSuffix() {
        $value = static::fieldId('post', 'title', ['error']);

        $this->assertEquals('post_title_error', $value);
    }

    public function testFieldIdWithSuffixAndIndex() {
        $value = static::fieldId('post', 'title', ['error'], 1);

        $this->assertEquals('post_1_title_error', $value);
    }

    public function testFieldIdWithNestedObjectName() {
        $value = static::fieldId('post[author]', 'name');

        $this->assertEquals('post_author_name', $value);
    }

    public function testFieldNameWithNullObjectName() {
        $value = static::fieldName(null, 'title');

        $this->assertEquals('title', $value);
    }

    public function testFieldNameWithBlankObjectName() {
        $value = static::fieldName('', 'title');

        $this->assertEquals('title', $value);
    }

    public function testFieldNameWithBlankObjectNameAndMultiple() {
        $value = static::fieldName('', 'title', [], true);

        $this->assertEquals('title[]', $value);
    }

    public function testFieldNameWithoutOtherNamesOrMultipleOrIndex() {
        $value = static::fieldName('post', 'title');

        $this->assertEquals('post[title]', $value);
    }

    public function testFieldNameWithoutOtherNamesAndMultiple() {
        $value = static::fieldName('post', 'title', [], true);

        $this->assertEquals('post[title][]', $value);
    }

    public function testFieldNameWithoutOtherNamesAndIndex() {
        $value = static::fieldName('post', 'title', [], false, 1);

        $this->assertEquals('post[1][title]', $value);
    }

    public function testFieldNameWithoutOtherNamesAndMultipleAndIndex() {
        $value = static::fieldName('post', 'title', [], true, 1);

        $this->assertEquals('post[1][title][]', $value);
    }

    public function testFieldNameWithOtherNames() {
        $value = static::fieldName('post', 'title', ['subtitle']);

        $this->assertEquals('post[title][subtitle]', $value);
    }

    public function testFieldNameWithOtherNamesAndIndex() {
        $value = static::fieldName('post', 'title', ['subtitle'], false, 1);

        $this->assertEquals('post[1][title][subtitle]', $value);
    }

    public function testFieldNameWithOtherNamesAndMultiple() {
        $value = static::fieldName('post', 'title', ['subtitle'], true);

        $this->assertEquals('post[title][subtitle][]', $value);
    }

    public function testFieldNameWithOtherNamesAndMultipleAndIndex() {
        $value = static::fieldName('post', 'title', ['subtitle'], true, 1);

        $this->assertEquals('post[1][title][subtitle][]', $value);
    }

    public function testLabelTagWithoutText() {
        $this->assertDomEquals(
            '<label for="title">Title</label>',
            static::labelTag('title')
        );
    }

    public function testLabelTagWithText() {
        $this->assertDomEquals(
            '<label for="title">My Title</label>',
            static::labelTag('title', 'My Title')
        );
    }

    public function testLabelTagClassString() {
        $this->assertDomEquals(
            '<label for="title" class="small_label">My Title</label>',
            static::labelTag('title', 'My Title', ['class' => 'small_label'])
        );
    }

    public function testLabelTagSanitizesId() {
        $this->assertValidHtmlId(static::labelTag('item[title]'), 'for');
    }

    public function testLabelTagWithCallback() {
        $this->assertDomEquals(
            '<label>Blocked</label>',
            static::labelTag(null, null, function () {
                return 'Blocked';
            })
        );
    }

    public function testLabelTagWithNameCallback() {
        $this->assertDomEquals(
            '<label for="clock">Blocked</label>',
            static::labelTag('clock', function () {
                return 'Blocked';
            })
        );
    }

    public function testLabelTagWithNameOptionsAndCallback() {
        $this->assertDomEquals(
            '<label for="clock" id="label_clock">Blocked</label>',
            static::labelTag('clock', ['id' => 'label_clock'], function () {
                return 'Blocked';
            })
        );
    }
}

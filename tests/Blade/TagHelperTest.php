<?php

namespace Tests\Blade;

use Illuminate\Support\HtmlString;
use Orchestra\Testbench\TestCase;
use SilvertipSoftware\LaravelSupport\Blade\TagHelper;
use Tests\TestSupport\HtmlAssertions;

class TagHelperTest extends TestCase {
    use HtmlAssertions,
        TagHelper;

    public static $COMMON_DANGEROUS_CHARS = "&<>\"' %*+,/;=^|";

    public function testTag() {
        $this->assertHtmlEquals('<br />', static::tag('br'));
        $this->assertHtmlEquals('<br clear="left" />', static::tag('br', ['clear' => 'left']));
        $this->assertHtmlEquals('<br>', static::tag('br', null, true));
    }

    public function testTagBuilder() {
        $this->assertHtmlEquals('<span></span>', static::tag()->span);
        $this->assertHtmlEquals('<span></span>', static::tag()->span());
        $this->assertHtmlEquals('<span class="bookmark"></span>', static::tag()->span(['class' => 'bookmark']));
    }

    public function testTagBuilderVoidTag() {
        $this->assertHtmlEquals('<br>', static::tag()->br);
        $this->assertHtmlEquals('<br class="some_class">', static::tag()->br(['class' => 'some_class']));
    }

    public function testTagBuilderVoidTagWithForcedContent() {
        $this->assertHtmlEquals('<br>some content</br>', static::tag()->br('some content'));
    }

    public function testTagBuilderSelfClosingTag() {
        $tag = static::tag();

        $html = $tag->svg($tag->use(['href' => '#cool-icon']));
        $this->assertHtmlEquals('<svg><use href="#cool-icon" /></svg>', $html);

        $html = $tag->svg($tag->circle(['cx' => '5', 'cy' => '5', 'r' => '5']));
        $this->assertHtmlEquals('<svg><circle cx="5" cy="5" r="5" /></svg>', $html);
    }

    public function testTagBuilderSelfClosingTagWithContent() {
        $tag = static::tag();

        $html = $tag->svg($tag->circle($tag->desc('A circle')));
        $this->assertHtmlEquals('<svg><circle><desc>A circle</desc></circle></svg>', $html);
    }

    public function testTagBuilderIsASingleton() {
        $tag = static::tag();

        $this->assertSame($tag, static::tag());
    }

    public function testTagOptionsWithArrayOfNumeric() {
        $html = static::tag('input', ['value' => [123, 456]]);

        $this->assertHtmlEquals('<input value="123 456" />', $html);
    }

    public function testTagOptionsWithArrayOfRandomObjects() {
        $klass = new class() {
            public function __toString() {
                return 'hello';
            }
        };

        $html = static::tag('input', ['value' => [new $klass]]);
        $this->assertHtmlEquals('<input value="hello" />', $html);
    }

    public function testTagOptionsRejectsNullOption() {
        $this->assertHtmlEquals('<p />', static::tag('p', ['ignored' => null]));
        $this->assertHtmlEquals('<p></p>', static::tag()->p(['ignored' => null]));
    }

    public function testTagOptionsAcceptsFalseOption() {
        $this->assertHtmlEquals('<p value="false" />', static::tag('p', ['value' => false]));
        $this->assertHtmlEquals('<p value="false"></p>', static::tag()->p(['value' => false]));
    }

    public function testTagOptionsAcceptsBlankOption() {
        $this->assertHtmlEquals('<p included="" />', static::tag('p', ['included' => '']));
        $this->assertHtmlEquals('<p included=""></p>', static::tag()->p(['included' => '']));
    }

    public function testTagOptionsAcceptsIntegerOptionWhenNotEscaping() {
        $this->assertHtmlEquals('<p value="42" />', static::tag('p', ['value' => 42], false, false));
    }

    public function testTagOptionsConvertsBooleanOption() {
        $str = '<p disabled="disabled" itemscope="itemscope" multiple="multiple" readonly="readonly"'
            . ' allowfullscreen="allowfullscreen" seamless="seamless" typemustmatch="typemustmatch"'
            . ' sortable="sortable" default="default" inert="inert" truespeed="truespeed"'
            . ' allowpaymentrequest="allowpaymentrequest" nomodule="nomodule" playsinline="playsinline"';

        $options = [
            'disabled' => true,
            'itemscope' => true,
            'multiple' => true,
            'readonly' => true,
            'allowfullscreen' => true,
            'seamless' => true,
            'typemustmatch' => true,
            'sortable' => true,
            'default' => true,
            'inert' => true,
            'truespeed' => true,
            'allowpaymentrequest' => true,
            'nomodule' => true,
            'playsinline' => true
        ];

        $this->assertHtmlEquals($str . ' />', static::tag('p', $options));
        $this->assertHtmlEquals($str . '></p>', static::tag()->p($options));
    }

    public function testTagBuilderDoesNotModifyHtmlSafeOptions() {
        $safe = new HtmlString('"');

        $this->assertHtmlEquals('<p value="&quot;" />', static::tag('p', ['value' => $safe]));
    }

    public function testTagWithDangerousName() {
        $tagName = str_pad('', strlen(static::$COMMON_DANGEROUS_CHARS), '_');

        $this->assertHtmlEquals('<' . $tagName . ' />', static::tag(static::$COMMON_DANGEROUS_CHARS));
        $this->assertHtmlEquals(
            '<' . $tagName . '></'. $tagName . '>',
            static::tag()->tagString(static::$COMMON_DANGEROUS_CHARS)
        );
        $this->assertHtmlEquals(
            '<' . static::$COMMON_DANGEROUS_CHARS . ' />',
            static::tag(static::$COMMON_DANGEROUS_CHARS, null, false, false)
        );
        $this->assertHtmlEquals(
            '<' . static::$COMMON_DANGEROUS_CHARS . '></' . static::$COMMON_DANGEROUS_CHARS . '>',
            static::tag()->tagString(static::$COMMON_DANGEROUS_CHARS, null, ['escape' => false])
        );
    }

    public function testTagWithDangerousAriaAttributeName() {
        $escaped = str_pad('', strlen(static::$COMMON_DANGEROUS_CHARS), '_');
        $attrs = ['aria' => [static::$COMMON_DANGEROUS_CHARS => 'the-value']];

        $this->assertHtmlEquals(
            '<the-name aria-' . $escaped . '="the-value" />',
            static::tag('the-name', $attrs)
        );
        $this->assertHtmlEquals(
            '<the-name aria-' . $escaped . '="the-value"></the-name>',
            static::tag()->tagString('the-name', $attrs)
        );

        $this->assertHtmlEquals(
            '<the-name aria-' . static::$COMMON_DANGEROUS_CHARS . '="the-value" />',
            static::tag('the-name', $attrs, false, false)
        );
        $this->assertHtmlEquals(
            '<the-name aria-' . static::$COMMON_DANGEROUS_CHARS . '="the-value"></the-name>',
            static::tag()->tagString('the-name', null, array_merge($attrs, ['escape' => false]))
        );
    }

    public function testTagWithDangerousDataAttributeName() {
        $escaped = str_pad('', strlen(static::$COMMON_DANGEROUS_CHARS), '_');
        $attrs = ['data' => [static::$COMMON_DANGEROUS_CHARS => 'the-value']];

        $this->assertHtmlEquals(
            '<the-name data-' . $escaped . '="the-value" />',
            static::tag('the-name', $attrs)
        );
        $this->assertHtmlEquals(
            '<the-name data-' . $escaped . '="the-value"></the-name>',
            static::tag()->tagString('the-name', $attrs)
        );

        $this->assertHtmlEquals(
            '<the-name data-' . static::$COMMON_DANGEROUS_CHARS . '="the-value" />',
            static::tag('the-name', $attrs, false, false)
        );
        $this->assertHtmlEquals(
            '<the-name data-' . static::$COMMON_DANGEROUS_CHARS . '="the-value"></the-name>',
            static::tag()->tagString('the-name', null, array_merge($attrs, ['escape' => false]))
        );
    }

    public function testTagWithDangerousUnknownAttributeName() {
        $escaped = str_pad('', strlen(static::$COMMON_DANGEROUS_CHARS), '_');
        $attrs = [static::$COMMON_DANGEROUS_CHARS => 'the-value'];

        $this->assertHtmlEquals(
            '<the-name ' . $escaped . '="the-value" />',
            static::tag('the-name', $attrs)
        );
        $this->assertHtmlEquals(
            '<the-name ' . $escaped . '="the-value"></the-name>',
            static::tag()->tagString('the-name', $attrs)
        );

        $this->assertHtmlEquals(
            '<the-name ' . static::$COMMON_DANGEROUS_CHARS . '="the-value" />',
            static::tag('the-name', $attrs, false, false)
        );
        $this->assertHtmlEquals(
            '<the-name ' . static::$COMMON_DANGEROUS_CHARS . '="the-value"></the-name>',
            static::tag()->tagString('the-name', null, array_merge($attrs, ['escape' => false]))
        );
    }

    public function testContentTag() {
        $html = static::contentTag('a', 'Create', ['href' => 'create']);

        $this->assertInstanceOf(HtmlString::class, $html);
        $this->assertHtmlEquals('<a href="create">Create</a>', $html);

        $this->assertHtmlEquals(
            '<p>&lt;script&gt;evil_js&lt;/script&gt;</p>',
            static::contentTag('p', '<script>evil_js</script>')
        );
        $this->assertHtmlEquals(
            '<p><script>evil_js</script></p>',
            static::contentTag('p', '<script>evil_js</script>', null, false)
        );

        $this->assertHtmlEquals(
            '<div @click="triggerNav()">test</div>',
            static::contentTag('div', 'test', ['@click' => 'triggerNav()'])
        );
    }

    public function testTagBuilderWithContent() {
        $html = static::tag()->div('Content', ['id' => 'post_1']);

        $this->assertInstanceOf(HtmlString::class, $html);
        $this->assertHtmlEquals('<div id="post_1">Content</div>', $html);

        $this->assertHtmlEquals(
            '<p>&lt;script&gt;evil_js&lt;/script&gt;</p>',
            static::tag()->p('<script>evil_js</script>')
        );
        $this->assertHtmlEquals(
            '<p><script>evil_js</script></p>',
            static::tag()->p('<script>evil_js</script>', ['escape' => false])
        );
    }

    public function testTagBuilderNested() {
        $builder = static::tag();
        $spanner = function () use ($builder) {
            return $builder->span('hello');
        };

        $this->assertHtmlEquals('<div>content</div>', $builder->div('content'));
        $this->assertHtmlEquals(
            '<div id="header"><span>hello</span></div>',
            $builder->div(['id' => 'header'], $spanner)
        );
        $this->assertHtmlEquals(
            '<div id="header"><div class="world"><span>hello</span></div></div>',
            $builder->div(['id' => 'header'], function () use ($spanner) {
                return static::tag()->div(['class' => 'world'], $spanner);
            })
        );
    }

    public function testContentTagWithEscapedArrayClass() {
        $html = static::contentTag('p', 'limelight', ['class' => ['song', 'play>']]);
        $this->assertHtmlEquals('<p class="song play&gt;">limelight</p>', $html);

        $html = static::contentTag('p', 'limelight', ['class' => ['song', 'play']]);
        $this->assertHtmlEquals('<p class="song play">limelight</p>', $html);

        $html = static::contentTag('p', 'limelight', ['class' => ['song', ['play']]]);
        $this->assertHtmlEquals('<p class="song play">limelight</p>', $html);
    }

    public function testTagBuilderWithEscapedArrayClass() {
        $builder = static::tag();

        $html = $builder->p('limelight', ['class' => ['song', 'play>']]);
        $this->assertHtmlEquals('<p class="song play&gt;">limelight</p>', $html);

        $html = $builder->p('limelight', ['class' => ['song', 'play']]);
        $this->assertHtmlEquals('<p class="song play">limelight</p>', $html);

        $html = $builder->p('limelight', ['class' => ['song', ['play']]]);
        $this->assertHtmlEquals('<p class="song play">limelight</p>', $html);
    }

    public function testContentTagWithUnescapedArrayClass() {
        $html = static::contentTag('p', 'limelight', ['class' => ['song', 'play>']], false);
        $this->assertHtmlEquals('<p class="song play>">limelight</p>', $html);

        $html = static::contentTag('p', 'limelight', ['class' => ['song', ['play>']]], false);
        $this->assertHtmlEquals('<p class="song play>">limelight</p>', $html);
    }

    public function testTagBuilderWithUnescapedArrayClass() {
        $builder = static::tag();

        $html = $builder->p('limelight', ['class' => ['song', 'play>'], 'escape' => false]);
        $this->assertHtmlEquals('<p class="song play>">limelight</p>', $html);

        $html = $builder->p('limelight', ['class' => ['song', ['play>']], 'escape' => false]);
        $this->assertHtmlEquals('<p class="song play>">limelight</p>', $html);
    }

    public function testEmptyArrayClass() {
        $this->assertHtmlEquals(
            '<p class="">limelight</p>',
            static::contentTag('p', 'limelight', ['class' => []])
        );
        $this->assertHtmlEquals(
            '<p class="">limelight</p>',
            static::contentTag('p', 'limelight', ['class' => []], false)
        );

        $this->assertHtmlEquals(
            '<p class="">limelight</p>',
            static::tag()->p('limelight', ['class' => []])
        );
        $this->assertHtmlEquals(
            '<p class="">limelight</p>',
            static::tag()->p('limelight', ['class' => [], 'escape' => false])
        );
    }

    public function testContentTagWithConditionalHashClasses() {
        $expected = '<p class="song">limelight</p>';

        $this->assertHtmlEquals(
            $expected,
            static::contentTag('p', 'limelight', ['class' => ['song' => true, 'play' => false]])
        );

        $this->assertHtmlEquals(
            $expected,
            static::contentTag('p', 'limelight', ['class' => [['song' => true], ['play' => false]]])
        );

        $this->assertHtmlEquals(
            $expected,
            static::contentTag('p', 'limelight', ['class' => [['song' => true], null, false]])
        );

        $this->assertHtmlEquals(
            $expected,
            static::contentTag('p', 'limelight', ['class' => ['song', ['foo' => false]]])
        );

        $this->assertHtmlEquals(
            '<p class="1 2 3">limelight</p>',
            static::contentTag('p', 'limelight', ['class' => [1, 2, 3]])
        );

        $klass = new class() {
            public function __toString() {
                return "1";
            }
        };

        $this->assertHtmlEquals(
            '<p class="1">limelight</p>',
            static::contentTag('p', 'limelight', ['class' => $klass])
        );

        $this->assertHtmlEquals(
            '<p class="song play">limelight</p>',
            static::contentTag('p', 'limelight', ['class' => ['song' => true, 'play' => true]])
        );

        $this->assertHtmlEquals(
            '<p class="">limelight</p>',
            static::contentTag('p', 'limelight', ['class' => ['song' => false, 'play' => false]])
        );
    }

    public function testTagBuilderWithConditionalHashClasses() {
        $expected = '<p class="song">limelight</p>';

        $this->assertHtmlEquals(
            $expected,
            static::tag()->p('limelight', ['class' => ['song' => true, 'play' => false]])
        );

        $this->assertHtmlEquals(
            $expected,
            static::tag()->p('limelight', ['class' => [['song' => true], ['play' => false]]])
        );

        $this->assertHtmlEquals(
            $expected,
            static::tag()->p('limelight', ['class' => [['song' => true], null, false]])
        );

        $this->assertHtmlEquals(
            $expected,
            static::tag()->p('limelight', ['class' => ['song', ['foo' => false]]])
        );

        $this->assertHtmlEquals(
            '<p class="song play">limelight</p>',
            static::tag()->p('limelight', ['class' => ['song' => true, 'play' => true]])
        );

        $this->assertHtmlEquals(
            '<p class="">limelight</p>',
            static::tag()->p('limelight', ['class' => ['song' => false, 'play' => false]])
        );
    }

    public function testUnescapedHashClasses() {
        $this->assertHtmlEquals(
            '<p class="song play>">limelight</p>',
            static::contentTag('p', 'limelight', ['class' => ['song' => true, 'play>' => true]], false)
        );

        $this->assertHtmlEquals(
            '<p class="song play>">limelight</p>',
            static::contentTag('p', 'limelight', ['class' => ['song', ['play>' => true]]], false)
        );

        $this->assertHtmlEquals(
            '<p class="song play>">limelight</p>',
            static::tag()->p('limelight', ['class' => ['song' => true, 'play>' => true], 'escape' => false])
        );

        $this->assertHtmlEquals(
            '<p class="song play>">limelight</p>',
            static::tag()->p('limelight', ['class' => ['song', ['play>' => true]], 'escape' => false])
        );
    }

    public function testTokenList() {
        $this->assertEquals('song play', static::tokenList(['song', ['play' => true]]));
        $this->assertEquals('song', static::tokenList(['song' => true, 'play' => false]));
        $this->assertEquals('song', static::tokenList([['song' => true], ['play' => false]]));
        $this->assertEquals('song', static::tokenList([['song' => true], null, false]));
        $this->assertEquals('song', static::tokenList(['song', ['foo' => false]]));
        $this->assertEquals('song play', static::tokenList(['song' => true, 'play' => true]));
        $this->assertEquals('', static::tokenList(['song' => false, 'play' => false]));
        $this->assertEquals('123', static::tokenList(null, '', false, 123, ['song' => false, 'play' => false]));

        $this->assertEquals('song', static::tokenList('song', 'song'));
        $this->assertEquals('song', static::tokenList('song song'));
        $this->assertEquals('song', static::tokenList("song\nsong"));

        $this->assertEquals('song', static::classNames(['song', 'song']));
    }

    public function testDataAttributes() {
        $exp = '<p data-number="1" data-string="hello" data-string-with-quotes="double&quot;quote&quot;party&quot;">'
        . 'limelight'
        . '</p>';

        $attrs = [
            'data' => [
                'number' => 1,
                'string' => 'hello',
                'string_with_quotes' => 'double"quote"party"'
            ]
        ];

        $this->assertHtmlEquals($exp, static::contentTag('p', 'limelight', $attrs));
        $this->assertHtmlEquals($exp, static::tag()->p('limelight', $attrs));
    }

    public function testCdataSection() {
        $this->assertEquals('<![CDATA[<hello world>]]>', static::cdataSection('<hello world>'));
    }

    public function testCdataSectionWithStringConversion() {
        $this->assertEquals('<![CDATA[]]>', static::cdataSection(null));
    }

    public function testCdataSectionSplit() {
        $this->assertHtmlEquals(
            '<![CDATA[hello]]]]><![CDATA[>world]]>',
            static::cdataSection('hello]]>world')
        );
        $this->assertHtmlEquals(
            '<![CDATA[hello]]]]><![CDATA[>world]]]]><![CDATA[>again]]>',
            static::cdataSection('hello]]>world]]>again')
        );
    }

    public function testTagHonorsHtmlSafetyForParamValues() {
        foreach (["1&amp;2", "1 &lt; 2", "&#8220;test&#8220;"] as $escaped) {
            $this->assertHtmlEquals(
                '<a href="' . $escaped . '" />',
                static::tag('a', ['href' => new HtmlString($escaped)])
            );
            $this->assertHtmlEquals(
                '<a href="' . $escaped . '"></a>',
                static::tag()->a(['href' => new HtmlString($escaped)])
            );
        }
    }

    public function testHonorsHtmlSafetyWithEscapedArrayClass() {
        $this->assertHtmlEquals(
            '<p class="song&gt; play>" />',
            static::tag('p', ['class' => ['song>', new HtmlString('play>')]])
        );
        $this->assertHtmlEquals(
            '<p class="song&gt; play>"></p>',
            static::tag()->p(['class' => ['song>', new HtmlString('play>')]])
        );

        $this->assertHtmlEquals(
            '<p class="song> play&gt;" />',
            static::tag('p', ['class' => [new HtmlString('song>'), 'play>']])
        );
        $this->assertHtmlEquals(
            '<p class="song> play&gt;"></p>',
            static::tag()->p(['class' => [new HtmlString('song>'), 'play>']])
        );
    }

    public function testDoesNotHonorHtmlSafetyDoubleQuotesAsAttributes() {
        $this->assertHtmlEquals(
            '<p title="&quot;">content</p>',
            static::contentTag('p', 'content', ['title' => new HtmlString('"')])
        );

        $this->assertHtmlEquals(
            '<p data-title="&quot;">content</p>',
            static::contentTag('p', 'content', ['data' => ['title' => new HtmlString('"')]])
        );
    }

    public function testSkipInvalidEscapedAttributes() {
        foreach (['&1;', '&#1dfa3;', '& #123;'] as $escaped) {
            $subd = preg_replace('/&/', '&amp;', $escaped);

            $this->assertHtmlEquals(
                '<a href="' . $subd . '" />',
                static::tag('a', ['href' => $escaped])
            );
            $this->assertHtmlEquals(
                '<a href="' . $subd . '"></a>',
                static::tag()->a(['href' => $escaped])
            );
        }
    }

    public function testDisableEscaping() {
        $this->assertHtmlEquals(
            '<a href="&amp;" />',
            static::tag('a', ['href' => '&amp;'], false, false)
        );
    }

    public function testTagBuilderDisableEscaping() {
        $this->assertHtmlEquals(
            '<a href="&amp;"></a>',
            static::tag()->a(['href' => '&amp;', 'escape' => false])
        );
        $this->assertHtmlEquals(
            '<a href="&amp;">cnt</a>',
            static::tag()->a(['href' => '&amp;', 'escape' => false], function () {
                return 'cnt';
            })
        );
        $this->assertHtmlEquals(
            '<br data-hidden="&amp;">',
            static::tag()->br(['data-hidden' => '&amp;', 'escape' => false])
        );
        $this->assertHtmlEquals(
            '<a href="&amp;">content</a>',
            static::tag()->a('content', ['href' => '&amp;', 'escape' => false])
        );
        $this->assertHtmlEquals(
            '<a href="&amp;">content</a>',
            static::tag()->a(['href' => '&amp;', 'escape' => false], function () {
                return 'content';
            })
        );
    }

    public function testMoreDataAttributes() {
        $exp = '<a data-a-float="3.14" data-a-number="1" data-string="hello" data-array="[1,2,3]"'
            . ' data-hash="{&quot;key&quot;:&quot;value&quot;}"'
            . ' data-string-with-quotes="double&quot;quote&quot;party&quot;"';
        $attrs = [
            'data' => [
                'a_float' => 3.14,
                'a_number' => 1,
                'string' => "hello",
                'array' => [1, 2, 3],
                'hash' => ['key' => "value"],
                'string_with_quotes' => 'double"quote"party"'
            ]
        ];

        $this->assertHtmlEquals($exp . ' />', static::tag('a', $attrs));
        $this->assertHtmlEquals($exp . '></a>', static::tag()->a($attrs));
    }

    public function testAriaAttributes() {
        $exp = '<a aria-a-float="3.14" aria-a-number="1" aria-truthy="true" aria-falsey="false" aria-string="hello"'
            . ' aria-array="1 2 3" aria-hash="a b" aria-tokens="a b"'
            . ' aria-string-with-quotes="double&quot;quote&quot;party&quot;"';
        $attrs = [
            'aria' => [
                null => null,
                'a_float' => 3.14,
                'a_number' => 1,
                'truthy' => true,
                'falsey' => false,
                'string' => "hello",
                'array' => [1, 2, 3],
                'empty_array' => [],
                'hash' => [
                    'a' => true,
                    'b' => 'truthy',
                    'falsey' => false,
                    null => null
                ],
                'tokens' => ['a', ['b' => true, 'c' => false]],
                'empty_tokens' => [['a' => false]],
                'string_with_quotes' => 'double"quote"party"'
            ]
        ];

        $this->assertHtmlEquals($exp . ' />', static::tag('a', $attrs));
        $this->assertHtmlEquals($exp . '></a>', static::tag()->a($attrs));
    }

    public function testLinkToDataNullEqual() {
        $divType1 = static::contentTag('div', 'test', ['data-tooltip' => null]);
        $divType2 = static::contentTag('div', 'test', ['data' => ['tooltip' => null]]);
        $this->assertEquals($divType1->toHtml(), $divType2->toHtml());

        $divType3 = static::tag()->div('test', ['data-tooltip' => null]);
        $divType4 = static::tag()->div('test', ['data' => ['tooltip' => null]]);
        $this->assertEquals($divType3->toHtml(), $divType4->toHtml());
    }

    public function testTagBuilderDasherizeNames() {
        $this->assertHtmlEquals('<img-slider></img-slider>', static::tag()->img_slider());
    }
}

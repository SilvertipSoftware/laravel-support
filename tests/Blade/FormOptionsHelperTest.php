<?php

namespace Tests\Blade;

require_once __DIR__ . '/../models/TestFormModels.php';

use App\Models\Continent;
use App\Models\Post;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\HtmlString;
use Illuminate\Support\MessageBag;
use Orchestra\Testbench\TestCase;
use RuntimeException;
use SilvertipSoftware\LaravelSupport\Blade\FormHelper;
use SilvertipSoftware\LaravelSupport\Blade\FormOptionsHelper;
use Tests\TestSupport\HtmlAssertions;

class FormOptionsHelperTest extends TestCase {
    use HtmlAssertions,
        FormHelper,
        FormOptionsHelper;

    public function setUp(): void {
        parent::setUp();
        Lang::addLines(Arr::dot([
            'date' => [
                'abbr_day_names' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
            ]
        ]), 'dates');
    }

    public function testCollectionOptions() {
        $expected = '<option value="&lt;Abe&gt;">&lt;Abe&gt; went home</option>' . "\n"
            . '<option value="Babe">Babe went home</option>' . "\n"
            . '<option value="Cabe">Cabe went home</option>';

        $this->assertDomEquals(
            $expected,
            static::optionsFromCollectionForSelect($this->dummyPosts(), 'author_name', 'title')
        );
    }

    public function testCollectionOptionsWithPreselectedValue() {
        $expected = '<option value="&lt;Abe&gt;">&lt;Abe&gt; went home</option>' . "\n"
            . '<option value="Babe" selected="selected">Babe went home</option>' . "\n"
            . '<option value="Cabe">Cabe went home</option>';

        $this->assertDomEquals(
            $expected,
            static::optionsFromCollectionForSelect($this->dummyPosts(), 'author_name', 'title', 'Babe')
        );
    }

    public function testCollectionOptionsWithPreselectedValueArray() {
        $expected = '<option value="&lt;Abe&gt;">&lt;Abe&gt; went home</option>' . "\n"
            . '<option value="Babe" selected="selected">Babe went home</option>' . "\n"
            . '<option value="Cabe" selected="selected">Cabe went home</option>';

        $this->assertDomEquals(
            $expected,
            static::optionsFromCollectionForSelect($this->dummyPosts(), 'author_name', 'title', ['Babe', 'Cabe'])
        );
    }

    public function testCollectionOptionsWithProcForSelected() {
        $expected = '<option value="&lt;Abe&gt;">&lt;Abe&gt; went home</option>' . "\n"
            . '<option value="Babe" selected="selected">Babe went home</option>' . "\n"
            . '<option value="Cabe">Cabe went home</option>';

        $this->assertDomEquals(
            $expected,
            static::optionsFromCollectionForSelect($this->dummyPosts(), 'author_name', 'title', function ($p) {
                return $p->author_name == 'Babe';
            })
        );
    }

    public function testCollectionOptionsWithDisabledValue() {
        $expected = '<option value="&lt;Abe&gt;">&lt;Abe&gt; went home</option>' . "\n"
            . '<option value="Babe" disabled="disabled">Babe went home</option>' . "\n"
            . '<option value="Cabe">Cabe went home</option>';

        $this->assertDomEquals(
            $expected,
            static::optionsFromCollectionForSelect($this->dummyPosts(), 'author_name', 'title', ['disabled' => 'Babe'])
        );
    }

    public function testCollectionOptionsWithDisabledArray() {
        $expected = '<option value="&lt;Abe&gt;">&lt;Abe&gt; went home</option>' . "\n"
            . '<option value="Babe" disabled="disabled">Babe went home</option>' . "\n"
            . '<option value="Cabe" disabled="disabled">Cabe went home</option>';

        $opts = [
            'disabled' => ['Babe', 'Cabe']
        ];

        $this->assertDomEquals(
            $expected,
            static::optionsFromCollectionForSelect($this->dummyPosts(), 'author_name', 'title', $opts)
        );
    }

    public function testCollectionOptionsWithPreselectedAndDisabledValue() {
        $expected = '<option value="&lt;Abe&gt;">&lt;Abe&gt; went home</option>' . "\n"
            . '<option value="Babe" disabled="disabled">Babe went home</option>' . "\n"
            . '<option value="Cabe" selected="selected">Cabe went home</option>';

        $opts = [
            'selected' => 'Cabe',
            'disabled' => 'Babe'
        ];

        $this->assertDomEquals(
            $expected,
            static::optionsFromCollectionForSelect($this->dummyPosts(), 'author_name', 'title', $opts)
        );
    }

    public function testCollectionOptionsWithProcForDisabled() {
        $expected = '<option value="&lt;Abe&gt;">&lt;Abe&gt; went home</option>' . "\n"
            . '<option value="Babe" disabled="disabled">Babe went home</option>' . "\n"
            . '<option value="Cabe" disabled="disabled">Cabe went home</option>';

        $opts = [
            'disabled' => function ($p) {
                return in_array($p->author_name, ['Babe', 'Cabe']);
            }
        ];

        $this->assertDomEquals(
            $expected,
            static::optionsFromCollectionForSelect($this->dummyPosts(), 'author_name', 'title', $opts)
        );
    }

    public function testCollectionOptionsWithProcForValueMethod() {
        $expected = '<option value="&lt;Abe&gt;">&lt;Abe&gt; went home</option>' . "\n"
            . '<option value="Babe">Babe went home</option>' . "\n"
            . '<option value="Cabe">Cabe went home</option>';

        $fn = function ($p) {
            return $p->author_name;
        };

        $this->assertDomEquals(
            $expected,
            static::optionsFromCollectionForSelect($this->dummyPosts(), $fn, 'title')
        );
    }

    public function testCollectionOptionsWithProcForTextMethod() {
        $expected = '<option value="&lt;Abe&gt;">&lt;Abe&gt; went home</option>' . "\n"
            . '<option value="Babe">Babe went home</option>' . "\n"
            . '<option value="Cabe">Cabe went home</option>';

        $fn = function ($p) {
            return $p->title;
        };

        $this->assertDomEquals(
            $expected,
            static::optionsFromCollectionForSelect($this->dummyPosts(), 'author_name', $fn)
        );
    }

    public function testCollectionOptionsWithElementAttributes() {
        $this->assertDomEquals(
            '<option value="CAN" class="bold">CAN</option>',
            static::optionsFromCollectionForSelect(collect([['CAN', 'CAN', ['class' => 'bold']]]), 0, 1)
        );
    }

    public function testStringOptionsForSelect() {
        $expected = '<option value="Denmark">Denmark</option>'
            . '<option value="USA">USA</option>'
            . '<option value="Sweden">Sweden</option>';

        $this->assertDomEquals($expected, static::optionsForSelect($expected));
    }

    public function testArrayOptionsForSelect() {
        $expected = '<option value="&lt;Denmark&gt;">&lt;Denmark&gt;</option>' . "\n"
            . '<option value="USA">USA</option>' . "\n"
            . '<option value="Sweden">Sweden</option>';

        $this->assertDomEquals($expected, static::optionsForSelect(['<Denmark>', 'USA', 'Sweden']));
    }

    public function testArrayOptionsForSelectWithCustomDefinedSelected() {
        $expected = '<option selected="selected" type="Coach" value="1">Ted Lasso</option>' . "\n"
            . '<option type="Coachee" value="1">Ted Lasso</option>';

        $this->assertDomEquals(
            $expected,
            static::optionsForSelect([
                ['Ted Lasso', 1, ['type' => 'Coach', 'selected' => 'selected']],
                ['Ted Lasso', 1, ['type' => 'Coachee']]
            ])
        );
    }

    public function testArrayOptionsForSelectWithCustomDefinedDisabled() {
        $expected = '<option disabled="disabled" type="Coach" value="1">Ted Lasso</option>' . "\n"
            . '<option type="Coachee" value="1">Ted Lasso</option>';

        $this->assertDomEquals(
            $expected,
            static::optionsForSelect([
                ['Ted Lasso', 1, ['type' => 'Coach', 'disabled' => 'disabled']],
                ['Ted Lasso', 1, ['type' => 'Coachee']]
            ])
        );
    }

    public function testArrayOptionsForSelectWithSelection() {
        $expected = '<option value="&lt;Denmark&gt;">&lt;Denmark&gt;</option>' . "\n"
            . '<option selected="selected" value="USA">USA</option>' . "\n"
            . '<option value="Sweden">Sweden</option>';

        $this->assertDomEquals($expected, static::optionsForSelect(['<Denmark>', 'USA', 'Sweden'], 'USA'));
    }

    public function testArrayOptionsForSelectWithSelectionArray() {
        $expected = '<option value="&lt;Denmark&gt;">&lt;Denmark&gt;</option>' . "\n"
            . '<option selected="selected" value="USA">USA</option>' . "\n"
            . '<option selected="selected" value="Sweden">Sweden</option>';

        $this->assertDomEquals($expected, static::optionsForSelect(['<Denmark>', 'USA', 'Sweden'], ['USA', 'Sweden']));
    }

    public function testArrayOptionsForSelectWithDisabledValue() {
        $expected = '<option value="&lt;Denmark&gt;">&lt;Denmark&gt;</option>' . "\n"
            . '<option disabled="disabled" value="USA">USA</option>' . "\n"
            . '<option value="Sweden">Sweden</option>';

        $this->assertDomEquals(
            $expected,
            static::optionsForSelect(['<Denmark>', 'USA', 'Sweden'], ['disabled' => 'USA'])
        );
    }

    public function testArrayOptionsForSelectWithDisabledArray() {
        $expected = '<option value="&lt;Denmark&gt;">&lt;Denmark&gt;</option>' . "\n"
            . '<option disabled="disabled" value="USA">USA</option>' . "\n"
            . '<option disabled="disabled" value="Sweden">Sweden</option>';

        $this->assertDomEquals(
            $expected,
            static::optionsForSelect(['<Denmark>', 'USA', 'Sweden'], ['disabled' => ['USA', 'Sweden']])
        );
    }

    public function testArrayOptionsForSelectWithSelectionAndDisabledValue() {
        $expected = '<option selected="selected" value="&lt;Denmark&gt;">&lt;Denmark&gt;</option>' . "\n"
            . '<option disabled="disabled" value="USA">USA</option>' . "\n"
            . '<option value="Sweden">Sweden</option>';

        $this->assertDomEquals(
            $expected,
            static::optionsForSelect(
                ['<Denmark>', 'USA', 'Sweden'],
                ['disabled' => 'USA', 'selected' => '<Denmark>']
            )
        );
    }

    public function testBooleanArrayOptionsForSelectWithSelectionAndDisabledValue() {
        $expected = '<option value="true">true</option>' . "\n"
            . '<option value="false" selected="selected">false</option>';

        $this->assertDomEquals(
            $expected,
            static::optionsForSelect([true, false], ['selected' => false, 'disabled' => null])
        );
    }

    public function testArrayOptionsForSelectSubstringsDontMatch() {
        $expected = '<option value="abe">abe</option>' . "\n"
            . '<option selected="selected" value="babe">babe</option>';
        $this->assertDomEquals(
            $expected,
            static::optionsForSelect(['abe', 'babe'], 'babe')
        );

        $expected = '<option selected="selected" value="abe">abe</option>' . "\n"
            . '<option value="babe">babe</option>';
        $this->assertDomEquals(
            $expected,
            static::optionsForSelect(['abe', 'babe'], 'abe')
        );

        $expected = '<option selected="selected" value="abe">abe</option>' . "\n"
            . '<option value="babe">babe</option>' . "\n"
            . '<option value=""></option>';
        $this->assertDomEquals(
            $expected,
            static::optionsForSelect(['abe', 'babe', null], 'abe')
        );
    }

    public function testHashOptionsForSelect() {
        $expected = '<option value="Dollar">$</option>' . "\n"
            . '<option value="&lt;Kroner&gt;">&lt;DKR&gt;</option>';

        $this->assertDomEquals(
            $expected,
            static::optionsForSelect(['$' => 'Dollar', '<DKR>' => '<Kroner>'])
        );
    }

    public function testHashOptionsForSelectWithSelectedValue() {
        $expected = '<option selected="selected" value="Dollar">$</option>' . "\n"
            . '<option value="&lt;Kroner&gt;">&lt;DKR&gt;</option>';

        $this->assertDomEquals(
            $expected,
            static::optionsForSelect(['$' => 'Dollar', '<DKR>' => '<Kroner>'], 'Dollar')
        );
    }

    public function testHashOptionsForSelectWithSelectedArray() {
        $expected = '<option selected="selected" value="Dollar">$</option>' . "\n"
            . '<option selected="selected" value="&lt;Kroner&gt;">&lt;DKR&gt;</option>';

        $this->assertDomEquals(
            $expected,
            static::optionsForSelect(['$' => 'Dollar', '<DKR>' => '<Kroner>'], ['Dollar', '<Kroner>'])
        );
    }

    public function testCollectionOptionsWithPreselectedValueAsStringAndOptionValueIsInteger() {
        $posts = $this->dummyPosts()->map(function ($p, $k) {
            $p->id = $k + 1;
            return $p;
        });

        $expected = '<option selected="selected" value="1">&lt;Abe&gt; went home</option>' . "\n"
            . '<option value="2">Babe went home</option>' . "\n"
            . '<option value="3">Cabe went home</option>';

        $this->assertDomEquals(
            $expected,
            static::optionsFromCollectionForSelect($posts, 'id', 'title', ['selected' => "1"])
        );
    }

    public function testCollectionOptionsWithPreselectedValueAsIntegerAndOptionValueIsString() {
        $posts = $this->dummyPosts()->map(function ($p, $k) {
            $p->id = '' . ($k + 1);
            return $p;
        });

        $expected = '<option value="1">&lt;Abe&gt; went home</option>' . "\n"
            . '<option value="2">Babe went home</option>' . "\n"
            . '<option selected="selected" value="3">Cabe went home</option>';

        $this->assertDomEquals(
            $expected,
            static::optionsFromCollectionForSelect($posts, 'id', 'title', ['selected' => 3])
        );
    }

    public function testCollectionOptionsWithPreselectedValueAsNull() {
        $posts = $this->dummyPosts()->map(function ($p, $k) {
            $p->id = $k + 1;
            return $p;
        });

        $expected = '<option value="1">&lt;Abe&gt; went home</option>' . "\n"
            . '<option value="2">Babe went home</option>' . "\n"
            . '<option value="3">Cabe went home</option>';

        $this->assertDomEquals(
            $expected,
            static::optionsFromCollectionForSelect($posts, 'id', 'title', ['selected' => null])
        );
    }

    public function testCollectionOptionsWithDisabledAsNull() {
        $posts = $this->dummyPosts()->map(function ($p, $k) {
            $p->id = $k + 1;
            return $p;
        });

        $expected = '<option value="1">&lt;Abe&gt; went home</option>' . "\n"
            . '<option value="2">Babe went home</option>' . "\n"
            . '<option value="3">Cabe went home</option>';

        $this->assertDomEquals(
            $expected,
            static::optionsFromCollectionForSelect($posts, 'id', 'title', ['disabled' => null])
        );
    }

    public function testCollectionOptionsWithDisabledAsArray() {
        $posts = $this->dummyPosts()->map(function ($p, $k) {
            $p->id = $k + 1;
            return $p;
        });

        $expected = '<option disabled="disabled" value="1">&lt;Abe&gt; went home</option>' . "\n"
            . '<option disabled="disabled" value="2">Babe went home</option>' . "\n"
            . '<option value="3">Cabe went home</option>';

        $this->assertDomEquals(
            $expected,
            static::optionsFromCollectionForSelect($posts, 'id', 'title', ['disabled' => [1, "2"]])
        );
    }

    public function testCollectionOptionsWithPreselectedAsStringArrayAndOptionValueIsInt() {
        $posts = $this->dummyPosts()->map(function ($p, $k) {
            $p->id = $k + 1;
            return $p;
        });

        $expected = '<option selected="selected" value="1">&lt;Abe&gt; went home</option>' . "\n"
            . '<option selected="selected" value="2">Babe went home</option>' . "\n"
            . '<option value="3">Cabe went home</option>';

        $this->assertDomEquals(
            $expected,
            static::optionsFromCollectionForSelect($posts, 'id', 'title', ["1", "2"])
        );
    }

    public function testOptionGroupsFromCollectionForSelect() {
        $expected = '<optgroup label="&lt;Africa&gt;">'
            . '<option value="&lt;sa&gt;">&lt;South Africa&gt;</option>' . "\n"
            . '<option value="so">Somalia</option></optgroup>'
            . '<optgroup label="Europe">'
            . '<option value="dk" selected="selected">Denmark</option>' . "\n"
            . '<option value="ie">Ireland</option></optgroup>';

        $this->assertDomEquals(
            $expected,
            static::optionGroupsFromCollectionForSelect(
                $this->dummyContinents(),
                'countries',
                'continent_name',
                'country_id',
                'country_name',
                'dk'
            )
        );
    }

    public function testOptionGroupsFromCollectionForSelectWithCallableGroupMethod() {
        $expected = '<optgroup label="&lt;Africa&gt;">'
            . '<option value="&lt;sa&gt;">&lt;South Africa&gt;</option>' . "\n"
            . '<option value="so">Somalia</option></optgroup>'
            . '<optgroup label="Europe">'
            . '<option value="dk" selected="selected">Denmark</option>' . "\n"
            . '<option value="ie">Ireland</option></optgroup>';

        $groupFn = function ($c) {
            return $c->countries;
        };

        $this->assertDomEquals(
            $expected,
            static::optionGroupsFromCollectionForSelect(
                $this->dummyContinents(),
                $groupFn,
                'continent_name',
                'country_id',
                'country_name',
                'dk'
            )
        );
    }

    public function testOptionGroupsFromCollectionForSelectWithCallableGroupLabelMethod() {
        $expected = '<optgroup label="&lt;Africa&gt;">'
            . '<option value="&lt;sa&gt;">&lt;South Africa&gt;</option>' . "\n"
            . '<option value="so">Somalia</option></optgroup>'
            . '<optgroup label="Europe">'
            . '<option value="dk" selected="selected">Denmark</option>' . "\n"
            . '<option value="ie">Ireland</option></optgroup>';

        $groupLabelFn = function ($c) {
            return $c->continent_name;
        };

        $this->assertDomEquals(
            $expected,
            static::optionGroupsFromCollectionForSelect(
                $this->dummyContinents(),
                'countries',
                $groupLabelFn,
                'country_id',
                'country_name',
                'dk'
            )
        );
    }

    public function testOptionGroupsFromCollectionForSelectReturnsHtmlString() {
        $this->assertInstanceOf(
            HtmlString::class,
            static::optionGroupsFromCollectionForSelect(
                $this->dummyContinents(),
                'countries',
                'continent_name',
                'country_id',
                'country_name',
                'dk'
            )
        );
    }

    public function testGroupedOptionsForSelectWithArray() {
        $expected = '<optgroup label="North America">'
            . '<option value="US">United States</option>' . "\n"
            . '<option value="Canada">Canada</option>'
            . '</optgroup><optgroup label="Europe">'
            . '<option value="GB">Great Britain</option>' . "\n"
            . '<option value="Germany">Germany</option>'
            . '</optgroup>';

        $this->assertDomEquals(
            $expected,
            static::groupedOptionsForSelect([
                [
                    'North America',
                    [['United States', 'US'], 'Canada'],
                ],
                [
                    'Europe',
                    [['Great Britain', 'GB'], 'Germany']
                ]
            ])
        );
    }

    public function testGroupedOptionsForSelectWithArrayAndHtmlAttributes() {
        $expected = '<optgroup label="North America" data-foo="bar">'
            . '<option value="US">United States</option>' . "\n"
            . '<option value="Canada">Canada</option>'
            . '</optgroup><optgroup label="Europe" disabled="disabled">'
            . '<option value="GB">Great Britain</option>' . "\n"
            . '<option value="Germany">Germany</option>'
            . '</optgroup>';

        $this->assertDomEquals(
            $expected,
            static::groupedOptionsForSelect([
                [
                    'North America',
                    [['United States', 'US'], 'Canada'],
                    ['data' => ['foo' => 'bar']]
                ],
                [
                    'Europe',
                    [['Great Britain', 'GB'], 'Germany'],
                    ['disabled' => 'disabled']
                ]
            ])
        );
    }

    public function testGroupedOptionsForSelectWithOptionalDivider() {
        $expected = '<optgroup label="----------">'
            . '<option value="US">US</option>' . "\n"
            . '<option value="Canada">Canada</option>'
            . '</optgroup>'
            . '<optgroup label="----------">'
            . '<option value="GB">GB</option>' . "\n"
            . '<option value="Germany">Germany</option>'
            . '</optgroup>';

        $this->assertDomEquals(
            $expected,
            static::groupedOptionsForSelect([['US', 'Canada'], ['GB', 'Germany']], null, ['divider' => '----------'])
        );
    }

    public function testGroupedOptionsForSelectWithSelectedAndPrompt() {
        $expected = '<option value="">Choose a product...</option>'
            . '<optgroup label="Hats">'
            . '<option value="Baseball Cap">Baseball Cap</option>' . "\n"
            . '<option selected="selected" value="Cowboy Hat">Cowboy Hat</option>'
            . '</optgroup>';

        $this->assertDomEquals(
            $expected,
            static::groupedOptionsForSelect(
                [['Hats', ['Baseball Cap', 'Cowboy Hat']]],
                'Cowboy Hat',
                ['prompt' => 'Choose a product...']
            )
        );
    }

    public function testGroupedOptionsForSelectWithSelectedAndPromptTrue() {
        $expected = '<option value="">Please select</option>'
            . '<optgroup label="Hats">'
            . '<option value="Baseball Cap">Baseball Cap</option>' . "\n"
            . '<option selected="selected" value="Cowboy Hat">Cowboy Hat</option>'
            . '</optgroup>';

        $this->assertDomEquals(
            $expected,
            static::groupedOptionsForSelect(
                [['Hats', ['Baseball Cap', 'Cowboy Hat']]],
                'Cowboy Hat',
                ['prompt' => true]
            )
        );
    }

    public function testGroupedOptionsForSelectReturnsHtmlString() {
        $this->assertInstanceOf(
            HtmlString::class,
            static::groupedOptionsForSelect([['Hats', ['Baseball Cap', 'Cowboy Hat']]])
        );
    }

    public function testGroupedOptionsForSelectWithPromptReturnsHtmlEscapedString() {
        $expected = '<option value="">&lt;Choose One&gt;</option>'
            . '<optgroup label="Hats">'
            . '<option value="Baseball Cap">Baseball Cap</option>' . "\n"
            . '<option value="Cowboy Hat">Cowboy Hat</option>'
            . '</optgroup>';

        $this->assertDomEquals(
            $expected,
            static::groupedOptionsForSelect(
                [['Hats', ['Baseball Cap', 'Cowboy Hat']]],
                null,
                ['prompt' => '<Choose One>']
            )
        );
    }

    public function testGroupedOptionsForSelectWithOptionsHash() {
        $expected = '<optgroup label="North America">'
            . '<option value="United States">United States</option>' . "\n"
            . '<option value="Canada">Canada</option>'
            . '</optgroup>'
            . '<optgroup label="Europe">'
            . '<option value="Denmark">Denmark</option>' . "\n"
            . '<option value="Germany">Germany</option>'
            . '</optgroup>';

        $this->assertDomEquals(
            $expected,
            static::groupedOptionsForSelect(
                [
                    'North America' => ['United States', 'Canada'],
                    'Europe' => ['Denmark', 'Germany']
                ]
            )
        );
    }

    public function testTimeZoneOptions() {
        $expected = '<option value="A">A</option>' . "\n"
            . '<option value="B">B</option>' . "\n"
            . '<option value="C">C</option>' . "\n"
            . '<option value="D">D</option>' . "\n"
            . '<option value="E">E</option>';

        $this->assertDomEquals(
            $expected,
            static::timeZoneOptionsForSelect(null, null, $this->dummyZones())
        );
    }

    public function testTimeZoneOptionsWithSelected() {
        $expected = '<option value="A">A</option>' . "\n"
            . '<option value="B">B</option>' . "\n"
            . '<option value="C">C</option>' . "\n"
            . '<option value="D" selected="selected">D</option>' . "\n"
            . '<option value="E">E</option>';

        $this->assertDomEquals(
            $expected,
            static::timeZoneOptionsForSelect('D', null, $this->dummyZones())
        );
    }

    public function testTimeZoneOptionsWithUnknownSelected() {
        $expected = '<option value="A">A</option>' . "\n"
            . '<option value="B">B</option>' . "\n"
            . '<option value="C">C</option>' . "\n"
            . '<option value="D">D</option>' . "\n"
            . '<option value="E">E</option>';

        $this->assertDomEquals(
            $expected,
            static::timeZoneOptionsForSelect('K', null, $this->dummyZones())
        );
    }

    public function testTimeZoneOptionsWithPriorityZones() {
        $zones = ['B', 'E'];
        $expected = '<option value="B">B</option>' . "\n"
            . '<option value="E">E</option>'
            . '<option value="" disabled="disabled">-------------</option>' . "\n"
            . '<option value="A">A</option>' . "\n"
            . '<option value="C">C</option>' . "\n"
            . '<option value="D">D</option>';

        $this->assertDomEquals(
            $expected,
            static::timeZoneOptionsForSelect(null, $zones, $this->dummyZones())
        );
    }

    public function testTimeZoneOptionsWithSelectedPriorityZones() {
        $zones = ['B', 'E'];
        $expected = '<option value="B">B</option>' . "\n"
            . '<option value="E" selected="selected">E</option>'
            . '<option value="" disabled="disabled">-------------</option>' . "\n"
            . '<option value="A">A</option>' . "\n"
            . '<option value="C">C</option>' . "\n"
            . '<option value="D">D</option>';

        $this->assertDomEquals(
            $expected,
            static::timeZoneOptionsForSelect('E', $zones, $this->dummyZones())
        );
    }

    public function testTimeZoneOptionsWithUnselectedPriorityZones() {
        $zones = ['B', 'E'];
        $expected = '<option value="B">B</option>' . "\n"
            . '<option value="E">E</option>'
            . '<option value="" disabled="disabled">-------------</option>' . "\n"
            . '<option value="A">A</option>' . "\n"
            . '<option value="C" selected="selected">C</option>' . "\n"
            . '<option value="D">D</option>';

        $this->assertDomEquals(
            $expected,
            static::timeZoneOptionsForSelect('C', $zones, $this->dummyZones())
        );
    }

    public function testTimeZoneOptionsWithRegexpPriority() {
        $expected = '<option value="B">B</option>' . "\n"
            . '<option value="E">E</option>'
            . '<option value="" disabled="disabled">-------------</option>' . "\n"
            . '<option value="A">A</option>' . "\n"
            . '<option value="C">C</option>' . "\n"
            . '<option value="D">D</option>';

        $this->assertDomEquals(
            $expected,
            static::timeZoneOptionsForSelect(null, '/B|E/', $this->dummyZones())
        );
    }

    public function testTimeZoneOptionsReturnsHtmlString() {
        $this->assertInstanceOf(HtmlString::class, static::timeZoneOptionsForSelect());
    }

    public function testSelect() {
        $post = new Post();
        $post->category = '<mus>';
        static::setContextVariables([
            'post' => $post
        ]);

        $expected = '<select id="post_category" name="post[category]">'
            . '<option value="abe">abe</option>' . "\n"
            . '<option value="&lt;mus&gt;" selected="selected">&lt;mus&gt;</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', ['abe', '<mus>', 'hest'])
        );
    }

    public function testSelectWithoutMultiple() {
        $this->assertDomEquals(
            '<select id="post_category" name="post[category]"></select>',
            static::select('post', 'category', '', [], ['multiple' => false])
        );
    }

    public function testRequiredSelectWithDefaultAndSelectedPlaceholder() {
        $expected = implode("\n", [
            '<select required="required" name="post[category]" id="post_category">'
                . '<option disabled="disabled" selected="selected" value="">Choose one</option>',
            '<option value="lifestyle">lifestyle</option>',
            '<option value="programming">programming</option>',
            '<option value="spiritual">spiritual</option></select>'
        ]);

        $this->assertDomEquals(
            $expected,
            static::select(
                'post',
                'category',
                ['lifestyle', 'programming', 'spiritual'],
                ['selected' => '', 'disabled' => '', 'prompt' => 'Choose one'],
                ['required' => true]
            )
        );
    }

    public function testSelectWithGroupedCollectionAsNestedArray() {
        $post = new Post();
        static::setContextVariables([
            'post' => $post
        ]);

        $countriesByContinent = [
            ['<Africa>', [['<South Africa>', '<sa>'], ['Somalia', 'so']]],
            ['Europe', [['Denmark', 'dk'], ['Ireland', 'ie']]]
        ];

        $expected = '<select id="post_origin" name="post[origin]">'
            . '<optgroup label="&lt;Africa&gt;">'
            . '<option value="&lt;sa&gt;">&lt;South Africa&gt;</option>' . "\n"
            . '<option value="so">Somalia</option>'
            . '</optgroup>'
            . '<optgroup label="Europe">'
            . '<option value="dk">Denmark</option>' . "\n"
            . '<option value="ie">Ireland</option>'
            . '</optgroup></select>';

        $this->assertDomEquals($expected, static::select('post', 'origin', $countriesByContinent));
    }

    public function testSelectWithGroupedCollectionAsHash() {
        $post = new Post();
        static::setContextVariables([
            'post' => $post
        ]);

        $countriesByContinent = [
            '<Africa>' => [['<South Africa>', '<sa>'], ['Somalia', 'so']],
            'Europe' => [['Denmark', 'dk'], ['Ireland', 'ie']]
        ];

        $expected = '<select id="post_origin" name="post[origin]">'
            . '<optgroup label="&lt;Africa&gt;">'
            . '<option value="&lt;sa&gt;">&lt;South Africa&gt;</option>' . "\n"
            . '<option value="so">Somalia</option>'
            . '</optgroup>'
            . '<optgroup label="Europe">'
            . '<option value="dk">Denmark</option>' . "\n"
            . '<option value="ie">Ireland</option>'
            . '</optgroup></select>';

        $this->assertDomEquals($expected, static::select('post', 'origin', $countriesByContinent));
    }

    public function testSelectWithGroupedCollectionAsNestedArrayAndHtmlAttributes() {
        $post = new Post();
        static::setContextVariables([
            'post' => $post
        ]);

        $countriesByContinent = [
            ['<Africa>', [['<South Africa>', '<sa>'], ['Somalia', 'so']], ['data' => ['foo' => 'bar']]],
            ['Europe', [['Denmark', 'dk'], ['Ireland', 'ie']], ['disabled' => 'disabled']]
        ];

        $expected = '<select id="post_origin" name="post[origin]">'
            . '<optgroup label="&lt;Africa&gt;" data-foo="bar">'
            . '<option value="&lt;sa&gt;">&lt;South Africa&gt;</option>' . "\n"
            . '<option value="so">Somalia</option>'
            . '</optgroup>'
            . '<optgroup label="Europe" disabled="disabled">'
            . '<option value="dk">Denmark</option>' . "\n"
            . '<option value="ie">Ireland</option>'
            . '</optgroup></select>';

        $this->assertDomEquals($expected, static::select('post', 'origin', $countriesByContinent));
    }

    public function testSelectWithBooleanProperty() {
        $post = new Post();
        $post->allow_comments = false;
        static::setContextVariables([
            'post' => $post
        ]);
        $expected = '<select id="post_allow_comments" name="post[allow_comments]">'
            . '<option value="true">true</option>' . "\n"
            . '<option value="false" selected="selected">false</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'allow_comments', ['true', 'false'])
        );
    }

    public function testSelectUnderFieldsFor() {
        $post = new Post();
        $post->category = '<mus>';
        static::setContextVariables([
            'post' => $post
        ]);

        $rendered = static::fieldsFor('post', $post, [], function ($f) {
            return $f->select('category', ['abe', '<mus>', 'hest']);
        });
        $expected = '<select id="post_category" name="post[category]">'
            . '<option value="abe">abe</option>' . "\n"
            . '<option value="&lt;mus&gt;" selected="selected">&lt;mus&gt;</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testSelectUnderFieldsForWithIndex() {
        $post = new Post();
        $post->category = '<mus>';
        static::setContextVariables([
            'post' => $post
        ]);

        $rendered = static::fieldsFor('post', $post, ['index' => 108], function ($f) {
            return $f->select('category', ['abe', '<mus>', 'hest']);
        });
        $expected = '<select id="post_108_category" name="post[108][category]">'
            . '<option value="abe">abe</option>' . "\n"
            . '<option value="&lt;mus&gt;" selected="selected">&lt;mus&gt;</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testSelectUnderFieldsForWithAutoIndex() {
        $post = new Post(['id' => 108, 'category' => '<mus>']);
        static::setContextVariables([
            'post' => $post
        ]);

        $rendered = static::fieldsFor('post[]', $post, [], function ($f) {
            return $f->select('category', ['abe', '<mus>', 'hest']);
        });
        $expected = '<select id="post_108_category" name="post[108][category]">'
            . '<option value="abe">abe</option>' . "\n"
            . '<option value="&lt;mus&gt;" selected="selected">&lt;mus&gt;</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testSelectUnderFieldsForWithStringAndGivenPrompt() {
        $post = new Post();
        static::setContextVariables([
            'post' => $post
        ]);

        $options = new HtmlString('<option value="abe">abe</option>'
            . '<option value="mus">mus</option>'
            . '<option value="hest">hest</option>');
        $rendered = static::fieldsFor('post', $post, [], function ($f) use ($options) {
            return $f->select('category', $options, ['prompt' => 'The prompt']);
        });
        $expected = '<select id="post_category" name="post[category]">'
            . '<option value="">The prompt</option>' . "\n"
            . $options
            . '</select>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testSelectUnderFieldsForWithBlock() {
        $post = new Post();
        static::setContextVariables(['post' => $post]);

        $rendered = static::fieldsFor('post', $post, [], function ($f) {
            return $f->select('category', null, [], [], function () {
                return static::contentTag('option', 'hello world');
            });
        });

        $expected = '<select id="post_category" name="post[category]">'
            . '<option>hello world</option>'
            . '</select>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testSelectUnderFieldsForWithBlockWithoutOptions() {
        $post = new Post();
        static::setContextVariables(['post' => $post]);

        $rendered = static::fieldsFor('post', $post, [], function ($f) {
            return $f->select('category', null, [], [], function () {
            });
        });

        $this->assertDomEquals(
            '<select id="post_category" name="post[category]"></select>',
            $rendered
        );
    }

    public function testSelectWithMultipleToAddHiddenInput() {
        $rendered = static::select('post', 'category', '', [], ['multiple' => true]);
        $expected = '<input type="hidden" name="post[category][]" value="" autocomplete="off"/>'
            . '<select multiple="multiple" id="post_category" name="post[category][]"></select>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testSelectWithMultipleAndWithoutHiddenInput() {
        $rendered = static::select('post', 'category', '', ['include_hidden' => false], ['multiple' => true]);
        $expected = '<select multiple="multiple" id="post_category" name="post[category][]"></select>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testSelectWithMultipleAndWithExplicitNameEndingWithBrackets() {
        $rendered = static::select(
            'post',
            'category',
            [],
            ['include_hidden' => false],
            ['multiple' => true, 'name' => 'post[category][]']
        );

        $this->assertDomEquals(
            '<select multiple="multiple" id="post_category" name="post[category][]"></select>',
            $rendered
        );
    }

    public function testSelectWithMultipleAndDisabledToAddDisabledHiddenInput() {
        $rendered = static::select('post', 'category', '', [], ['multiple' => true, 'disabled' => true]);
        $expected = '<input disabled="disabled" type="hidden" name="post[category][]" value="" autocomplete="off"/>'
            . '<select multiple="multiple" disabled="disabled" id="post_category" name="post[category][]"></select>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testSelectWithBlank() {
        $post = new Post(['category' => '<mus>']);
        static::setContextVariables(['post' => $post]);

        $expected = '<select id="post_category" name="post[category]">'
            . '<option value="" label=" "></option>' . "\n"
            . '<option value="abe">abe</option>' . "\n"
            . '<option value="&lt;mus&gt;" selected="selected">&lt;mus&gt;</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', ['abe', '<mus>', 'hest'], ['include_blank' => true])
        );
    }

    public function testSelectWithIncludeBlankFalseAndRequired() {
        $post = new Post(['category' => '<mus>']);
        static::setContextVariables(['post' => $post]);

        $this->expectException(RuntimeException::class);
        static::select(
            'post',
            'category',
            ['abe', '<mus>', 'hest'],
            ['include_blank' => false],
            ['required' => 'required']
        );
    }

    public function testSelectWithBlankAsString() {
        $post = new Post(['category' => '<mus>']);
        static::setContextVariables(['post' => $post]);

        $expected = '<select id="post_category" name="post[category]">'
            . '<option value="">None</option>' . "\n"
            . '<option value="abe">abe</option>' . "\n"
            . '<option value="&lt;mus&gt;" selected="selected">&lt;mus&gt;</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', ['abe', '<mus>', 'hest'], ['include_blank' => 'None'])
        );
    }

    public function testSelectWithBlankAsStringEscaped() {
        $post = new Post(['category' => '<mus>']);
        static::setContextVariables(['post' => $post]);

        $expected = '<select id="post_category" name="post[category]">'
            . '<option value="">&lt;None&gt;</option>' . "\n"
            . '<option value="abe">abe</option>' . "\n"
            . '<option value="&lt;mus&gt;" selected="selected">&lt;mus&gt;</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', ['abe', '<mus>', 'hest'], ['include_blank' => '<None>'])
        );
    }

    public function testSelectWithDefaultPrompt() {
        $post = new Post(['category' => '']);
        static::setContextVariables(['post' => $post]);
        $expected = '<select id="post_category" name="post[category]">'
            . '<option value="">Please select</option>' . "\n"
            . '<option value="abe">abe</option>' . "\n"
            . '<option value="&lt;mus&gt;">&lt;mus&gt;</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', ['abe', '<mus>', 'hest'], ['prompt' => true])
        );
    }

    public function testSelectNoPromptWhenSelectHasValue() {
        $post = new Post(['category' => '<mus>']);
        static::setContextVariables(['post' => $post]);
        $expected = '<select id="post_category" name="post[category]">'
            . '<option value="abe">abe</option>' . "\n"
            . '<option value="&lt;mus&gt;" selected="selected">&lt;mus&gt;</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', ['abe', '<mus>', 'hest'], ['prompt' => true])
        );
    }

    public function testSelectWithGivenPrompt() {
        $post = new Post(['category' => '']);
        static::setContextVariables(['post' => $post]);
        $expected = '<select id="post_category" name="post[category]">'
            . '<option value="">The prompt</option>' . "\n"
            . '<option value="abe">abe</option>' . "\n"
            . '<option value="&lt;mus&gt;">&lt;mus&gt;</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', ['abe', '<mus>', 'hest'], ['prompt' => 'The prompt'])
        );
    }

    public function testSelectWithGivenPromptEscaped() {
        $post = new Post();
        static::setContextVariables(['post' => $post]);
        $expected = '<select id="post_category" name="post[category]">'
            . '<option value="">&lt;The prompt&gt;</option>' . "\n"
            . '<option value="abe">abe</option>' . "\n"
            . '<option value="&lt;mus&gt;">&lt;mus&gt;</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', ['abe', '<mus>', 'hest'], ['prompt' => '<The prompt>'])
        );
    }

    public function testSelectWithPromptAndBlank() {
        $post = new Post(['category' => '']);
        static::setContextVariables(['post' => $post]);
        $expected = '<select id="post_category" name="post[category]">'
            . '<option value="">Please select</option>' . "\n"
            . '<option value="" label=" "></option>' . "\n"
            . '<option value="abe">abe</option>' . "\n"
            . '<option value="&lt;mus&gt;">&lt;mus&gt;</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', ['abe', '<mus>', 'hest'], ['prompt' => true, 'include_blank' => true])
        );
    }

    public function testSelectWithEmpty() {
        $post = new Post(['category' => '']);
        static::setContextVariables(['post' => $post]);
        $expected = '<select id="post_category" name="post[category]">'
            . '<option value="">Please select</option>' . "\n"
            . '<option value="" label=" "></option>' . "\n"
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', [], ['prompt' => true, 'include_blank' => true])
        );
    }

    public function testSelectWithHtmlOptions() {
        $post = new Post(['category' => '']);
        static::setContextVariables(['post' => $post]);
        $expected = '<select class="disabled" disabled="disabled" id="post_category" name="post[category]">'
            . '<option value="">Please select</option>' . "\n"
            . '<option value="" label=" "></option>' . "\n"
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select(
                'post',
                'category',
                [],
                ['prompt' => true, 'include_blank' => true],
                ['class' => 'disabled', 'disabled' => true]
            )
        );
    }

    public function testSelectWithNull() {
        $post = new Post(['category' => 'othervalue']);
        static::setContextVariables(['post' => $post]);
        $expected = '<select id="post_category" name="post[category]">'
            . '<option value=""></option>' . "\n"
            . '<option value="othervalue" selected="selected">othervalue</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', [null, "othervalue"])
        );
    }

    public function testSelectWithNullAsSelectedValue() {
        $post = new Post(['category' => null]);
        static::setContextVariables(['post' => $post]);
        $expected = '<select id="post_category" name="post[category]">'
            . '<option selected="selected" value="">none</option>' . "\n"
            . '<option value="1">programming</option>' . "\n"
            . '<option value="2">economics</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', ['none' => null, 'programming' => 1, 'economics' => 2])
        );
    }

    public function testSelectWithNullAndSelectedOptionAsNUll() {
        $post = new Post(['category' => null]);
        static::setContextVariables(['post' => $post]);
        $expected = '<select id="post_category" name="post[category]">'
            . '<option value="">none</option>' . "\n"
            . '<option value="1">programming</option>' . "\n"
            . '<option value="2">economics</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select(
                'post',
                'category',
                ['none' => null, 'programming' => 1, 'economics' => 2],
                ['selected' => null]
            )
        );
    }

    public function testSelectWithArray() {
        $continent = new Continent(['countries' => ['Denmark', 'Sweden']]);
        static::setContextVariables(['continent' => $continent]);

        $expected = '<select name="continent[countries]" id="continent_countries">'
            . '<option selected="selected" value="Denmark">Denmark</option>' . "\n"
            . '<option selected="selected" value="Sweden">Sweden</option>' . "\n"
            . '<option value="Canada">Canada</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('continent', 'countries', ['Denmark', 'Sweden', 'Canada'], ['multiple' => true])
        );
    }

    public function testRequiredSelect() {
        $expected = '<select id="post_category" name="post[category]" required="required">'
            . '<option value="" label=" "></option>' . "\n"
            . '<option value="abe">abe</option>' . "\n"
            . '<option value="mus">mus</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', ['abe', 'mus', 'hest'], [], ['required' => true])
        );
    }

    public function testRequiredSelectWithIncludeBlankPrompt() {
        $expected = '<select id="post_category" name="post[category]" required="required">'
            . '<option value="">Select one</option>' . "\n"
            . '<option value="abe">abe</option>' . "\n"
            . '<option value="mus">mus</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select(
                'post',
                'category',
                ['abe', 'mus', 'hest'],
                ['include_blank' => 'Select one'],
                ['required' => true]
            )
        );
    }

    public function testRequiredSelectWithPrompt() {
        $expected = '<select id="post_category" name="post[category]" required="required">'
            . '<option value="">Select one</option>' . "\n"
            . '<option value="abe">abe</option>' . "\n"
            . '<option value="mus">mus</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select(
                'post',
                'category',
                ['abe', 'mus', 'hest'],
                ['prompt' => 'Select one'],
                ['required' => true]
            )
        );
    }

    public function testRequiredSelectDisplaySizeEqualsOne() {
        $expected = '<select id="post_category" name="post[category]" required="required" size="1">'
            . '<option value="" label=" "></option>' . "\n"
            . '<option value="abe">abe</option>' . "\n"
            . '<option value="mus">mus</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', ['abe', 'mus', 'hest'], [], ['required' => true, 'size' => 1])
        );
    }

    public function testRequiredSelectDisplaySizeBiggerThanOne() {
        $expected = '<select id="post_category" name="post[category]" required="required" size="2">'
            . '<option value="abe">abe</option>' . "\n"
            . '<option value="mus">mus</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', ['abe', 'mus', 'hest'], [], ['required' => true, 'size' => 2])
        );
    }

    public function testRequiredSelectWithMultipleOption() {
        $expected = '<input name="post[category][]" type="hidden" value="" autocomplete="off"/>'
            . '<select id="post_category" name="post[category][]" required="required" multiple="multiple">'
            . '<option value="abe">abe</option>' . "\n"
            . '<option value="mus">mus</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', ['abe', 'mus', 'hest'], [], ['required' => true, 'multiple' => true])
        );
    }

    public function testSelectWithInteger() {
        $post = new Post(['category' => '']);
        static::setContextVariables(['post' => $post]);
        $expected = '<select id="post_category" name="post[category]">'
            . '<option value="">Please select</option>' . "\n"
            . '<option value="" label=" "></option>' . "\n"
            . '<option value="1">1</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', [1], ['prompt' => true, 'include_blank' => true])
        );
    }

    public function testListOfLists() {
        $post = new Post(['category' => '']);
        static::setContextVariables(['post' => $post]);
        $expected = '<select id="post_category" name="post[category]">'
            . '<option value="">Please select</option>' . "\n"
            . '<option value="" label=" "></option>' . "\n"
            . '<option value="number">Number</option>' . "\n"
            . '<option value="text">Text</option>' . "\n"
            . '<option value="boolean">Yes/No</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select(
                'post',
                'category',
                [['Number', 'number'], ['Text', 'text'], ['Yes/No', 'boolean']],
                ['prompt' => true, 'include_blank' => true]
            )
        );
    }

    public function testSelectWithSelectedValue() {
        $post = new Post(['category' => '<mus>']);
        static::setContextVariables(['post' => $post]);
        $expected = '<select id="post_category" name="post[category]">'
            . '<option value="abe" selected="selected">abe</option>' . "\n"
            . '<option value="&lt;mus&gt;">&lt;mus&gt;</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', ['abe', '<mus>', 'hest'], ['selected' => 'abe'])
        );
    }

    public function testSelectWithIndexOption() {
        $post = new Post(['id' => '1']);
        static::setContextVariables(['post' => $post]);
        $expected = '<select id="post__category" name="post[][category]">'
            . '<option value="abe">abe</option>' . "\n"
            . '<option value="&lt;mus&gt;">&lt;mus&gt;</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', ['abe', '<mus>', 'hest'], [], ['index' => null])
        );
    }

    public function testSelectEscapesOptions() {
        $this->assertDomEquals(
            '<select id="post_title" name="post[title]">&lt;script&gt;alert(1)&lt;/script&gt;</select>',
            static::select('post', 'title', '<script>alert(1)</script>')
        );
    }

    public function testSelectWithSelectedNull() {
        $post = new Post(['category' => '<mus>']);
        static::setContextVariables(['post' => $post]);
        $expected = '<select id="post_category" name="post[category]">'
            . '<option value="abe">abe</option>' . "\n"
            . '<option value="&lt;mus&gt;">&lt;mus&gt;</option>' . "\n"
            . '<option value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', ['abe', '<mus>', 'hest'], ['selected' => null])
        );
    }

    public function testSelectWithDisabledValue() {
        $post = new Post(['category' => '<mus>']);
        static::setContextVariables(['post' => $post]);
        $expected = '<select id="post_category" name="post[category]">'
            . '<option value="abe">abe</option>' . "\n"
            . '<option selected="selected" value="&lt;mus&gt;">&lt;mus&gt;</option>' . "\n"
            . '<option disabled="disabled" value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', ['abe', '<mus>', 'hest'], ['disabled' => 'hest'])
        );
    }

    public function testSelectNonExistingAttrWithSelectedValue() {
        $post = new Post();
        static::setContextVariables(['post' => $post]);
        $expected = '<select id="post_locale" name="post[locale]">'
            . '<option value="en">en</option>' . "\n"
            . '<option selected="selected" value="dk">dk</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'locale', ['en', 'dk'], ['selected' => 'dk'])
        );
    }

    public function testSelectWithPromptAndSelectedValue() {
        $post = new Post();
        static::setContextVariables(['post' => $post]);
        $expected = '<select id="post_category" name="post[category]">'
            . '<option value="one">one</option>' . "\n"
            . '<option selected="selected" value="two">two</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', ['one', 'two'], ['selected' => 'two', 'prompt' => true])
        );
    }

    public function testSelectWithDisabledArray() {
        $post = new Post(['category' => '<mus>']);
        static::setContextVariables(['post' => $post]);
        $expected = '<select id="post_category" name="post[category]">'
            . '<option disabled="disabled" value="abe">abe</option>' . "\n"
            . '<option selected="selected" value="&lt;mus&gt;">&lt;mus&gt;</option>' . "\n"
            . '<option disabled="disabled" value="hest">hest</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::select('post', 'category', ['abe', '<mus>', 'hest'], ['disabled' => ['hest', 'abe']])
        );
    }

    public function testCollectionSelect() {
        $post = new Post(['author_name' => 'Babe']);
        static::setContextVariables(['post' => $post]);
        $expected = '<select id="post_author_name" name="post[author_name]">'
            . '<option value="&lt;Abe&gt;">&lt;Abe&gt;</option>' . "\n"
            . '<option value="Babe" selected="selected">Babe</option>' . "\n"
            . '<option value="Cabe">Cabe</option>'
            . '</select>';

        $this->assertDomEquals(
            $expected,
            static::collectionSelect('post', 'author_name', $this->dummyPosts(), 'author_name', 'author_name')
        );
    }

    public function testCollectionSelectUnderFieldsFor() {
        $post = new Post(['author_name' => 'Babe']);
        static::setContextVariables(['post' => $post]);

        $rendered = static::fieldsFor('post', $post, [], function ($f) {
            return $f->collectionSelect('author_name', $this->dummyPosts(), 'author_name', 'author_name');
        });
        $expected = '<select id="post_author_name" name="post[author_name]">'
            . '<option value="&lt;Abe&gt;">&lt;Abe&gt;</option>' . "\n"
            . '<option value="Babe" selected="selected">Babe</option>' . "\n"
            . '<option value="Cabe">Cabe</option>'
            . '</select>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testCollectionSelectUnderFieldsForWithIndex() {
        $post = new Post(['author_name' => 'Babe']);
        static::setContextVariables(['post' => $post]);

        $rendered = static::fieldsFor('post', $post, ['index' => 815], function ($f) {
            return $f->collectionSelect('author_name', $this->dummyPosts(), 'author_name', 'author_name');
        });
        $expected = '<select id="post_815_author_name" name="post[815][author_name]">'
            . '<option value="&lt;Abe&gt;">&lt;Abe&gt;</option>' . "\n"
            . '<option value="Babe" selected="selected">Babe</option>' . "\n"
            . '<option value="Cabe">Cabe</option>'
            . '</select>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testCollectionSelectUnderFieldsForWithAutoIndex() {
        $post = new Post(['id' => 815, 'author_name' => 'Babe']);
        static::setContextVariables(['post' => $post]);

        $rendered = static::fieldsFor('post[]', $post, [], function ($f) {
            return $f->collectionSelect('author_name', $this->dummyPosts(), 'author_name', 'author_name');
        });
        $expected = '<select id="post_815_author_name" name="post[815][author_name]">'
            . '<option value="&lt;Abe&gt;">&lt;Abe&gt;</option>' . "\n"
            . '<option value="Babe" selected="selected">Babe</option>' . "\n"
            . '<option value="Cabe">Cabe</option>'
            . '</select>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testCollectionSelectWithBlankAndStyle() {
        $post = new Post(['author_name' => 'Babe']);
        static::setContextVariables(['post' => $post]);

        $rendered = static::collectionSelect(
            'post',
            'author_name',
            $this->dummyPosts(),
            'author_name',
            'author_name',
            ['include_blank' => true],
            ['style' => 'width: 200px']
        );

        $expected = '<select id="post_author_name" name="post[author_name]" style="width: 200px">'
            . '<option value="" label=" "></option>' . "\n"
            . '<option value="&lt;Abe&gt;">&lt;Abe&gt;</option>' . "\n"
            . '<option value="Babe" selected="selected">Babe</option>' . "\n"
            . '<option value="Cabe">Cabe</option>'
            . '</select>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testCollectionSelectWithBlankAsStringAndStyle() {
        $post = new Post(['author_name' => 'Babe']);
        static::setContextVariables(['post' => $post]);

        $rendered = static::collectionSelect(
            'post',
            'author_name',
            $this->dummyPosts(),
            'author_name',
            'author_name',
            ['include_blank' => 'No selection'],
            ['style' => 'width: 200px']
        );

        $expected = '<select id="post_author_name" name="post[author_name]" style="width: 200px">'
            . '<option value="">No selection</option>' . "\n"
            . '<option value="&lt;Abe&gt;">&lt;Abe&gt;</option>' . "\n"
            . '<option value="Babe" selected="selected">Babe</option>' . "\n"
            . '<option value="Cabe">Cabe</option>'
            . '</select>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testCollectionSelectWithMultipleOptionAppendsArrayBracketsAndHiddenInput() {
        $post = new Post(['author_name' => 'Babe']);
        static::setContextVariables(['post' => $post]);

        $rendered = static::collectionSelect(
            'post',
            'author_name',
            $this->dummyPosts(),
            'author_name',
            'author_name',
            ['include_blank' => true],
            ['multiple' => true]
        );

        $expected = '<input type="hidden" name="post[author_name][]" value="" autocomplete="off"/>'
            . '<select id="post_author_name" name="post[author_name][]" multiple="multiple">'
            . '<option value="" label=" "></option>' . "\n"
            . '<option value="&lt;Abe&gt;">&lt;Abe&gt;</option>' . "\n"
            . '<option value="Babe" selected="selected">Babe</option>' . "\n"
            . '<option value="Cabe">Cabe</option>'
            . '</select>';

        $this->assertDomEquals($expected, $rendered);

        $rendered = static::collectionSelect(
            'post',
            'author_name',
            $this->dummyPosts(),
            'author_name',
            'author_name',
            ['include_blank' => true, 'name' => 'post[author_name][]'],
            ['multiple' => true]
        );

    }

    public function testCollectionSelectWithBlankAndSelected() {
        $post = new Post(['author_name' => 'Babe']);
        static::setContextVariables(['post' => $post]);

        $rendered = static::collectionSelect(
            'post',
            'author_name',
            $this->dummyPosts(),
            'author_name',
            'author_name',
            ['include_blank' => true, 'selected' => '<Abe>']
        );

        $expected = '<select id="post_author_name" name="post[author_name]">'
            . '<option value="" label=" "></option>' . "\n"
            . '<option value="&lt;Abe&gt;" selected="selected">&lt;Abe&gt;</option>' . "\n"
            . '<option value="Babe">Babe</option>' . "\n"
            . '<option value="Cabe">Cabe</option>'
            . '</select>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testCollectionSelectWithDisabled() {
        $post = new Post(['author_name' => 'Babe']);
        static::setContextVariables(['post' => $post]);

        $rendered = static::collectionSelect(
            'post',
            'author_name',
            $this->dummyPosts(),
            'author_name',
            'author_name',
            ['disabled' => 'Cabe']
        );

        $expected = '<select id="post_author_name" name="post[author_name]">'
            . '<option value="&lt;Abe&gt;">&lt;Abe&gt;</option>' . "\n"
            . '<option value="Babe" selected="selected">Babe</option>' . "\n"
            . '<option value="Cabe" disabled="disabled">Cabe</option>'
            . '</select>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testCollectionSelectWithProcForValueMethod() {
        $post = new Post();
        static::setContextVariables(['post' => $post]);

        $expected = '<select id="post_author_name" name="post[author_name]">'
            . '<option value="&lt;Abe&gt;">&lt;Abe&gt; went home</option>' . "\n"
            . '<option value="Babe">Babe went home</option>' . "\n"
            . '<option value="Cabe">Cabe went home</option>'
            . '</select>';

        $fn = function ($p) {
            return $p->author_name;
        };

        $this->assertDomEquals(
            $expected,
            static::collectionSelect(
                'post',
                'author_name',
                $this->dummyPosts(),
                $fn,
                'title'
            )
        );
    }

    public function testCollectionSelectWithProcForTextMethod() {
        $post = new Post();
        static::setContextVariables(['post' => $post]);

        $expected = '<select id="post_author_name" name="post[author_name]">'
            . '<option value="&lt;Abe&gt;">&lt;Abe&gt; went home</option>' . "\n"
            . '<option value="Babe">Babe went home</option>' . "\n"
            . '<option value="Cabe">Cabe went home</option>'
            . '</select>';

        $fn = function ($p) {
            return $p->title;
        };

        $this->assertDomEquals(
            $expected,
            static::collectionSelect(
                'post',
                'author_name',
                $this->dummyPosts(),
                'author_name',
                $fn
            )
        );
    }

    public function testTimeZoneSelect() {
        $post = new Post(['time_zone' => 'D']);
        static::setContextVariables(['post' => $post]);

        $html = static::timeZoneSelect('post', 'time_zone', null, ['model' => $this->dummyZones()]);
        $expected = '<select id="post_time_zone" name="post[time_zone]">'
            . '<option value="A">A</option>' . "\n"
            . '<option value="B">B</option>' . "\n"
            . '<option value="C">C</option>' . "\n"
            . '<option value="D" selected="selected">D</option>' . "\n"
            . '<option value="E">E</option>'
            . '</select>';

        $this->assertDomEquals($expected, $html);
    }

    public function testTimeZoneSelectUnderFieldsFor() {
        $post = new Post(['time_zone' => 'D']);
        static::setContextVariables(['post' => $post]);

        $html = static::fieldsFor('post', $post, [], function ($f) {
            return $f->timeZoneSelect('time_zone', null, ['model' => $this->dummyZones()]);
        });
        $expected = '<select id="post_time_zone" name="post[time_zone]">'
            . '<option value="A">A</option>' . "\n"
            . '<option value="B">B</option>' . "\n"
            . '<option value="C">C</option>' . "\n"
            . '<option value="D" selected="selected">D</option>' . "\n"
            . '<option value="E">E</option>'
            . '</select>';

        $this->assertDomEquals($expected, $html);
    }

    public function testTimeZoneSelectUnderFieldsForWithIndex() {
        $post = new Post(['time_zone' => 'D']);
        static::setContextVariables(['post' => $post]);

        $html = static::fieldsFor('post', $post, ['index' => 305], function ($f) {
            return $f->timeZoneSelect('time_zone', null, ['model' => $this->dummyZones()]);
        });
        $expected = '<select id="post_305_time_zone" name="post[305][time_zone]">'
            . '<option value="A">A</option>' . "\n"
            . '<option value="B">B</option>' . "\n"
            . '<option value="C">C</option>' . "\n"
            . '<option value="D" selected="selected">D</option>' . "\n"
            . '<option value="E">E</option>'
            . '</select>';

        $this->assertDomEquals($expected, $html);
    }

    public function testTimeZoneSelectUnderFieldsForWithAutoIndex() {
        $post = new Post(['id' => 305, 'time_zone' => 'D']);
        static::setContextVariables(['post' => $post]);

        $html = static::fieldsFor('post[]', $post, [], function ($f) {
            return $f->timeZoneSelect('time_zone', null, ['model' => $this->dummyZones()]);
        });
        $expected = '<select id="post_305_time_zone" name="post[305][time_zone]">'
            . '<option value="A">A</option>' . "\n"
            . '<option value="B">B</option>' . "\n"
            . '<option value="C">C</option>' . "\n"
            . '<option value="D" selected="selected">D</option>' . "\n"
            . '<option value="E">E</option>'
            . '</select>';

        $this->assertDomEquals($expected, $html);
    }

    public function testTimeZoneSelectWithBlank() {
        $post = new Post(['time_zone' => 'D']);
        static::setContextVariables(['post' => $post]);

        $html = static::timeZoneSelect('post', 'time_zone', null, [
            'include_blank' => true,
            'model' => $this->dummyZones(),
        ]);
        $expected = '<select id="post_time_zone" name="post[time_zone]">'
            . '<option value="" label=" "></option>' . "\n"
            . '<option value="A">A</option>' . "\n"
            . '<option value="B">B</option>' . "\n"
            . '<option value="C">C</option>' . "\n"
            . '<option value="D" selected="selected">D</option>' . "\n"
            . '<option value="E">E</option>'
            . '</select>';

        $this->assertDomEquals($expected, $html);
    }

    public function testTimeZoneSelectWithBlankAsString() {
        $post = new Post(['time_zone' => 'D']);
        static::setContextVariables(['post' => $post]);

        $html = static::timeZoneSelect('post', 'time_zone', null, [
            'include_blank' => 'No zone',
            'model' => $this->dummyZones(),
        ]);
        $expected = '<select id="post_time_zone" name="post[time_zone]">'
            . '<option value="">No zone</option>' . "\n"
            . '<option value="A">A</option>' . "\n"
            . '<option value="B">B</option>' . "\n"
            . '<option value="C">C</option>' . "\n"
            . '<option value="D" selected="selected">D</option>' . "\n"
            . '<option value="E">E</option>'
            . '</select>';

        $this->assertDomEquals($expected, $html);
    }

    public function testTimeZoneSelectWithStyle() {
        $post = new Post(['time_zone' => 'D']);
        static::setContextVariables(['post' => $post]);

        $html = static::timeZoneSelect('post', 'time_zone', null, [
            'model' => $this->dummyZones(),
        ], [
            'style' => 'color: red'
        ]);
        $expected = '<select id="post_time_zone" name="post[time_zone]" style="color: red">'
            . '<option value="A">A</option>' . "\n"
            . '<option value="B">B</option>' . "\n"
            . '<option value="C">C</option>' . "\n"
            . '<option value="D" selected="selected">D</option>' . "\n"
            . '<option value="E">E</option>'
            . '</select>';

        $this->assertDomEquals($expected, $html);
    }

    public function testTimeZoneSelectWithBlankAndStyle() {
        $post = new Post(['time_zone' => 'D']);
        static::setContextVariables(['post' => $post]);

        $html = static::timeZoneSelect('post', 'time_zone', null, [
            'include_blank' => true,
            'model' => $this->dummyZones(),
        ], [
            'style' => 'color: red'
        ]);
        $expected = '<select id="post_time_zone" name="post[time_zone]" style="color: red">'
            . '<option value="" label=" "></option>' . "\n"
            . '<option value="A">A</option>' . "\n"
            . '<option value="B">B</option>' . "\n"
            . '<option value="C">C</option>' . "\n"
            . '<option value="D" selected="selected">D</option>' . "\n"
            . '<option value="E">E</option>'
            . '</select>';

        $this->assertDomEquals($expected, $html);
    }

    public function testTimeZoneSelectWithBlankAsStringAndStyle() {
        $post = new Post(['time_zone' => 'D']);
        static::setContextVariables(['post' => $post]);

        $html = static::timeZoneSelect('post', 'time_zone', null, [
            'include_blank' => 'No zone',
            'model' => $this->dummyZones(),
        ], [
            'style' => 'color: red'
        ]);
        $expected = '<select id="post_time_zone" name="post[time_zone]" style="color: red">'
            . '<option value="">No zone</option>' . "\n"
            . '<option value="A">A</option>' . "\n"
            . '<option value="B">B</option>' . "\n"
            . '<option value="C">C</option>' . "\n"
            . '<option value="D" selected="selected">D</option>' . "\n"
            . '<option value="E">E</option>'
            . '</select>';

        $this->assertDomEquals($expected, $html);
    }

    public function testTimeZoneSelectWithPriorityZones() {
        $post = new Post(['time_zone' => 'D']);
        static::setContextVariables(['post' => $post]);

        $zones = ['A', 'D'];
        $html = static::timeZoneSelect('post', 'time_zone', $zones, ['model' => $this->dummyZones()]);
        $expected = '<select id="post_time_zone" name="post[time_zone]">'
            . '<option value="A">A</option>' . "\n"
            . '<option value="D" selected="selected">D</option>'
            . '<option value="" disabled="disabled">-------------</option>' . "\n"
            . '<option value="B">B</option>' . "\n"
            . '<option value="C">C</option>' . "\n"
            . '<option value="E">E</option>'
            . '</select>';

        $this->assertDomEquals($expected, $html);
    }

    public function testTimeZoneSelectWithPriorityZonesAsRegexp() {
        $post = new Post(['time_zone' => 'D']);
        static::setContextVariables(['post' => $post]);

        $zones = '/A|D/';
        $html = static::timeZoneSelect('post', 'time_zone', $zones, ['model' => $this->dummyZones()]);
        $expected = '<select id="post_time_zone" name="post[time_zone]">'
            . '<option value="A">A</option>' . "\n"
            . '<option value="D" selected="selected">D</option>'
            . '<option value="" disabled="disabled">-------------</option>' . "\n"
            . '<option value="B">B</option>' . "\n"
            . '<option value="C">C</option>' . "\n"
            . '<option value="E">E</option>'
            . '</select>';

        $this->assertDomEquals($expected, $html);
    }

    public function testTimeZoneSelectWithPriorityZonesAndErrors() {
        $this->markTestSkipped('Errors not working yet');
        $post = new Post(['time_zone' => 'D']);
        $post->errors = new MessageBag();
        $post->errors->merge(['time_zone' => 'invalid']);
        static::setContextVariables(['post' => $post]);

        $zones = '/A|D/';
        $html = static::timeZoneSelect('post', 'time_zone', $zones, ['model' => $this->dummyZones()]);
        $expected = '<div class="field_with_errors">'
            . '<select id="post_time_zone" name="post[time_zone]">'
            . '<option value="A">A</option>' . "\n"
            . '<option value="D" selected="selected">D</option>'
            . '<option value="" disabled="disabled">-------------</option>' . "\n"
            . '<option value="B">B</option>' . "\n"
            . '<option value="C">C</option>' . "\n"
            . '<option value="E">E</option>'
            . '</select>'
            . '</div>';

        $this->assertDomEquals($expected, $html);
    }

    public function testTimeZoneSelectWithDefaultTimeZoneAndNullValue() {
        $post = new Post(['time_zone' => null]);
        static::setContextVariables(['post' => $post]);

        $html = static::timeZoneSelect('post', 'time_zone', null, [
            'default' => 'B',
            'model' => $this->dummyZones()
        ]);
        $expected = '<select id="post_time_zone" name="post[time_zone]">'
            . '<option value="A">A</option>' . "\n"
            . '<option value="B" selected="selected">B</option>' . "\n"
            . '<option value="C">C</option>' . "\n"
            . '<option value="D">D</option>' . "\n"
            . '<option value="E">E</option>'
            . '</select>';

        $this->assertDomEquals($expected, $html);
    }

    public function testTimeZoneSelectWithDefaultTimeZoneAndValue() {
        $post = new Post(['time_zone' => 'D']);
        static::setContextVariables(['post' => $post]);

        $html = static::timeZoneSelect('post', 'time_zone', null, [
            'default' => 'B',
            'model' => $this->dummyZones()
        ]);
        $expected = '<select id="post_time_zone" name="post[time_zone]">'
            . '<option value="A">A</option>' . "\n"
            . '<option value="B">B</option>' . "\n"
            . '<option value="C">C</option>' . "\n"
            . '<option value="D" selected="selected">D</option>' . "\n"
            . '<option value="E">E</option>'
            . '</select>';

        $this->assertDomEquals($expected, $html);
    }

    public function testOptionsForSelectWithElementAttributes() {
        $rendered = static::optionsForSelect([
            ['<Denmark>', ['class' => 'bold']],
            ['USA', ['onclick' => "alert('Hello World');"]],
            ['Sweden'],
            ['Germany']
        ]);

        $expected = '<option value="&lt;Denmark&gt;" class="bold">&lt;Denmark&gt;</option>' . "\n"
            . '<option value="USA" onclick="alert(&#39;Hello World&#39;);">USA</option>' . "\n"
            . '<option value="Sweden">Sweden</option>' . "\n"
            . '<option value="Germany">Germany</option>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testOptionsForSelectWithDataElement() {
        $this->assertDomEquals(
            '<option value="&lt;Denmark&gt;" data-test="bold">&lt;Denmark&gt;</option>',
            static::optionsForSelect([['<Denmark>', ['data' => ['test' => 'bold']]]])
        );
    }

    public function testOptionsForSelectWithDataElementWithSpecialChars() {
        $this->assertDomEquals(
            '<option value="&lt;Denmark&gt;" data-test="&lt;bold&gt;">&lt;Denmark&gt;</option>',
            static::optionsForSelect([['<Denmark>', ['data' => ['test' => '<bold>']]]])
        );
    }

    public function testOptionsForSelectWithElementAttributesAndSelection() {
        $rendered = static::optionsForSelect([
            ['<Denmark>'],
            ['USA', ['class' => 'bold']],
            ['Sweden']
        ], 'USA');

        $expected = '<option value="&lt;Denmark&gt;">&lt;Denmark&gt;</option>' . "\n"
            . '<option value="USA" class="bold" selected="selected">USA</option>' . "\n"
            . '<option value="Sweden">Sweden</option>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testOptionsForSelectWithElementAttributesAndSelectionArray() {
        $rendered = static::optionsForSelect([
            ['<Denmark>'],
            ['USA', ['class' => 'bold']],
            ['Sweden']
        ], ['USA', 'Sweden']);

        $expected = '<option value="&lt;Denmark&gt;">&lt;Denmark&gt;</option>' . "\n"
            . '<option value="USA" class="bold" selected="selected">USA</option>' . "\n"
            . '<option value="Sweden" selected="selected">Sweden</option>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testOptionsForSelectWithSpecialChars() {
        $this->assertDomEquals(
            '<option value="&lt;Denmark&gt;" onclick="alert(&quot;&lt;code&gt;&quot;)">&lt;Denmark&gt;</option>',
            static::optionsForSelect([['<Denmark>', ['onclick' => 'alert("<code>")']]])
        );
    }

    public function testOptionHtmlAttributesWithNoArrayElement() {
        $this->assertEquals([], static::optionHtmlAttributes('foo'));
    }

    public function testOptionHtmlAttributesWithoutHash() {
        $this->assertEquals([], static::optionHtmlAttributes(['foo', 'bar']));
    }

    public function testOptionHtmlAttributesWithSingleElementHash() {
        $this->assertEquals(
            ['class' => 'fancy'],
            static::optionHtmlAttributes(['foo', 'bar', ['class' => 'fancy']])
        );
    }

    public function testOptionHtmlAttributesWithMultipleElementHash() {
        $this->assertEquals(
            ['class' => 'fancy', 'onclick' => 'alert("Hello World");'],
            static::optionHtmlAttributes(['foo', 'bar', ['class' => 'fancy', 'onclick' => 'alert("Hello World");']])
        );
    }

    public function testOptionHtmlAttributesWithMultipleHashes() {
        $this->assertEquals(
            ['class' => 'fancy', 'onclick' => 'alert("Hello World");'],
            static::optionHtmlAttributes([
                'foo',
                'bar',
                ['class' => 'fancy'],
                ['onclick' => 'alert("Hello World");']
            ])
        );
    }

    public function testGroupedCollectionSelect() {
        $post = new Post(['origin' => 'dk']);
        static::setContextVariables(['post' => $post]);

        $rendered = static::groupedCollectionSelect(
            'post',
            'origin',
            $this->dummyContinents(),
            'countries',
            'continent_name',
            'country_id',
            'country_name'
        );

        $expected = '<select id="post_origin" name="post[origin]">'
            . '<optgroup label="&lt;Africa&gt;">'
            . '<option value="&lt;sa&gt;">&lt;South Africa&gt;</option>' . "\n"
            . '<option value="so">Somalia</option>'
            . '</optgroup><optgroup label="Europe">'
            . '<option value="dk" selected="selected">Denmark</option>' . "\n"
            . '<option value="ie">Ireland</option>'
            . '</optgroup></select>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testGroupedCollectionSelectWithSelected() {
        $post = new Post();
        static::setContextVariables(['post' => $post]);

        $rendered = static::groupedCollectionSelect(
            'post',
            'origin',
            $this->dummyContinents(),
            'countries',
            'continent_name',
            'country_id',
            'country_name',
            ['selected' => 'dk']
        );

        $expected = '<select id="post_origin" name="post[origin]">'
            . '<optgroup label="&lt;Africa&gt;">'
            . '<option value="&lt;sa&gt;">&lt;South Africa&gt;</option>' . "\n"
            . '<option value="so">Somalia</option>'
            . '</optgroup><optgroup label="Europe">'
            . '<option value="dk" selected="selected">Denmark</option>' . "\n"
            . '<option value="ie">Ireland</option>'
            . '</optgroup></select>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testGroupedCollectionSelectWithDisabledValue() {
        $post = new Post();
        static::setContextVariables(['post' => $post]);

        $rendered = static::groupedCollectionSelect(
            'post',
            'origin',
            $this->dummyContinents(),
            'countries',
            'continent_name',
            'country_id',
            'country_name',
            ['disabled' => 'dk']
        );

        $expected = '<select id="post_origin" name="post[origin]">'
            . '<optgroup label="&lt;Africa&gt;">'
            . '<option value="&lt;sa&gt;">&lt;South Africa&gt;</option>' . "\n"
            . '<option value="so">Somalia</option>'
            . '</optgroup><optgroup label="Europe">'
            . '<option value="dk" disabled="disabled">Denmark</option>' . "\n"
            . '<option value="ie">Ireland</option>'
            . '</optgroup></select>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testGroupedCollectionSelectUnderFieldsFor() {
        $post = new Post(['origin' => 'dk']);
        static::setContextVariables(['post' => $post]);

        $rendered = static::fieldsFor('post', $post, [], function ($f) {
            return $f->groupedCollectionSelect(
                'origin',
                $this->dummyContinents(),
                'countries',
                'continent_name',
                'country_id',
                'country_name',
            );
        });

        $expected = '<select id="post_origin" name="post[origin]">'
            . '<optgroup label="&lt;Africa&gt;">'
            . '<option value="&lt;sa&gt;">&lt;South Africa&gt;</option>' . "\n"
            . '<option value="so">Somalia</option>'
            . '</optgroup><optgroup label="Europe">'
            . '<option value="dk" selected="selected">Denmark</option>' . "\n"
            . '<option value="ie">Ireland</option>'
            . '</optgroup></select>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testWeekdayOptionsForSelectWithNoParams() {
        $expected = '<option value="Monday">Monday</option>' . "\n"
            . '<option value="Tuesday">Tuesday</option>' . "\n"
            . '<option value="Wednesday">Wednesday</option>' . "\n"
            . '<option value="Thursday">Thursday</option>' . "\n"
            . '<option value="Friday">Friday</option>' . "\n"
            . '<option value="Saturday">Saturday</option>' . "\n"
            . '<option value="Sunday">Sunday</option>';

        $this->assertDomEquals($expected, static::weekdayOptionsForSelect());
    }

    public function testWeekdayOptionsForSelectWithIndexAsValue() {
        $expected = '<option value="1">Monday</option>' . "\n"
            . '<option value="2">Tuesday</option>' . "\n"
            . '<option value="3">Wednesday</option>' . "\n"
            . '<option value="4">Thursday</option>' . "\n"
            . '<option value="5">Friday</option>' . "\n"
            . '<option value="6">Saturday</option>' . "\n"
            . '<option value="0">Sunday</option>';

        $this->assertDomEquals($expected, static::weekdayOptionsForSelect(null, true));
    }

    public function testWeekdayOptionsForSelectWithAbbreviatedDayNames() {
        Lang::setLocale('dates');

        $expected = '<option value="Mon">Mon</option>' . "\n"
            . '<option value="Tue">Tue</option>' . "\n"
            . '<option value="Wed">Wed</option>' . "\n"
            . '<option value="Thu">Thu</option>' . "\n"
            . '<option value="Fri">Fri</option>' . "\n"
            . '<option value="Sat">Sat</option>' . "\n"
            . '<option value="Sun">Sun</option>';

        $this->assertDomEquals($expected, static::weekdayOptionsForSelect(null, false, 'abbr_day_names'));
    }

    public function testWeekdayOptionsForSelectWithBeginningOfWeekSetToSunday() {
        $expected = '<option value="Sunday">Sunday</option>' . "\n"
            . '<option value="Monday">Monday</option>' . "\n"
            . '<option value="Tuesday">Tuesday</option>' . "\n"
            . '<option value="Wednesday">Wednesday</option>' . "\n"
            . '<option value="Thursday">Thursday</option>' . "\n"
            . '<option value="Friday">Friday</option>' . "\n"
            . '<option value="Saturday">Saturday</option>';

        $this->assertDomEquals($expected, static::weekdayOptionsForSelect(null, false, 'day_names', 0));
    }

    public function testWeekdayOptionsForSelectWithBeginningOfWeekSetToSaturday() {
        $expected = '<option value="Saturday">Saturday</option>' . "\n"
            . '<option value="Sunday">Sunday</option>' . "\n"
            . '<option value="Monday">Monday</option>' . "\n"
            . '<option value="Tuesday">Tuesday</option>' . "\n"
            . '<option value="Wednesday">Wednesday</option>' . "\n"
            . '<option value="Thursday">Thursday</option>' . "\n"
            . '<option value="Friday">Friday</option>';

        $this->assertDomEquals($expected, static::weekdayOptionsForSelect(null, false, 'day_names', 6));
    }

    public function testWeekdayOptionsForSelectWithSelectedValue() {
        $expected = '<option value="Monday">Monday</option>' . "\n"
            . '<option value="Tuesday">Tuesday</option>' . "\n"
            . '<option value="Wednesday">Wednesday</option>' . "\n"
            . '<option value="Thursday">Thursday</option>' . "\n"
            . '<option value="Friday" selected="selected">Friday</option>' . "\n"
            . '<option value="Saturday">Saturday</option>' . "\n"
            . '<option value="Sunday">Sunday</option>';

        $this->assertDomEquals($expected, static::weekdayOptionsForSelect('Friday'));
    }

    public function testWeekdaySelect() {
        $expected = '<select name="model[weekday]" id="model_weekday">'
            . '<option value="Monday">Monday</option>' . "\n"
            . '<option value="Tuesday">Tuesday</option>' . "\n"
            . '<option value="Wednesday">Wednesday</option>' . "\n"
            . '<option value="Thursday">Thursday</option>' . "\n"
            . '<option value="Friday">Friday</option>' . "\n"
            . '<option value="Saturday">Saturday</option>' . "\n"
            . '<option value="Sunday">Sunday</option>'
            . '</select>';

        $this->assertDomEquals($expected, static::weekdaySelect('model', 'weekday'));
    }

    public function testWeekdaySelectWithSelectedValue() {
        $expected = '<select name="model[weekday]" id="model_weekday">'
            . '<option value="Monday">Monday</option>' . "\n"
            . '<option value="Tuesday">Tuesday</option>' . "\n"
            . '<option value="Wednesday">Wednesday</option>' . "\n"
            . '<option value="Thursday">Thursday</option>' . "\n"
            . '<option value="Friday" selected="selected">Friday</option>' . "\n"
            . '<option value="Saturday">Saturday</option>' . "\n"
            . '<option value="Sunday">Sunday</option>'
            . '</select>';

        $this->assertDomEquals($expected, static::weekdaySelect('model', 'weekday', ['selected' => 'Friday']));
    }

    public function testWeekdaySelectUnderFieldsFor() {
        $post = new Post();
        static::setContextVariables(['post' => $post]);

        $rendered = static::fieldsFor('post', $post, [], function ($f) {
            return $f->weekdaySelect('weekday');
        });

        $expected = '<select name="post[weekday]" id="post_weekday">'
            . '<option value="Monday">Monday</option>' . "\n"
            . '<option value="Tuesday">Tuesday</option>' . "\n"
            . '<option value="Wednesday">Wednesday</option>' . "\n"
            . '<option value="Thursday">Thursday</option>' . "\n"
            . '<option value="Friday">Friday</option>' . "\n"
            . '<option value="Saturday">Saturday</option>' . "\n"
            . '<option value="Sunday">Sunday</option>'
            . '</select>';

        $this->assertDomEquals($expected, $rendered);
    }

    public function testWeekdaySelectUnderFieldsForWithValue() {
        $post = new Post(['weekday' => 'Monday']);
        static::setContextVariables(['post' => $post]);

        $rendered = static::fieldsFor('post', $post, [], function ($f) {
            return $f->weekdaySelect('weekday');
        });

        $expected = '<select name="post[weekday]" id="post_weekday">'
            . '<option value="Monday" selected="selected">Monday</option>' . "\n"
            . '<option value="Tuesday">Tuesday</option>' . "\n"
            . '<option value="Wednesday">Wednesday</option>' . "\n"
            . '<option value="Thursday">Thursday</option>' . "\n"
            . '<option value="Friday">Friday</option>' . "\n"
            . '<option value="Saturday">Saturday</option>' . "\n"
            . '<option value="Sunday">Sunday</option>'
            . '</select>';

        $this->assertDomEquals($expected, $rendered);
    }

    private function dummyPosts() {
        $b = 'To a little house';

        return collect([
            new Post(['title' => '<Abe> went home', 'author_name' => '<Abe>', 'body' => $b, 'written_on' => 'shh!']),
            new Post(['title' => 'Babe went home', 'author_name' => 'Babe', 'body' => $b, 'written_on' => 'shh!']),
            new Post(['title' => 'Cabe went home', 'author_name' => 'Cabe', 'body' => $b, 'written_on' => 'shh!'])
        ]);
    }

    private function dummyContinents() {
        return collect([
            (object)[
                'continent_name' => '<Africa>',
                'countries' => [
                    (object)['country_id' => '<sa>', 'country_name' => '<South Africa>'],
                    (object)['country_id' => 'so', 'country_name' => 'Somalia']
                ]
            ],
            (object)[
                'continent_name' => 'Europe',
                'countries' => [
                    (object)['country_id' => 'dk', 'country_name' => 'Denmark'],
                    (object)['country_id' => 'ie', 'country_name' => 'Ireland'],
                ]
            ]
        ]);
    }

    private function dummyZones() {
        return collect(['A', 'B', 'C', 'D', 'E']);
    }
}

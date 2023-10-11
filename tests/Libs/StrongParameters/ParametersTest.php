<?php

use Illuminate\Support\HtmlString;
use Orchestra\Testbench\TestCase;
use SilvertipSoftware\LaravelSupport\Libs\StrongParameters\AnyStructure;
use SilvertipSoftware\LaravelSupport\Libs\StrongParameters\ParameterMissingException;
use SilvertipSoftware\LaravelSupport\Libs\StrongParameters\Parameters;

class ParametersTest extends TestCase {

    protected $params = null;

    public function setUp(): void {
        $this->params = new Parameters([
            'person' => [
                'age' => 32,
                'name' => [
                    'first' => 'John',
                    'last' => 'Lennon'
                ],
                'addresses' => [
                    ['city' => 'NYC', 'state' => 'New York']
                ]
            ]
        ]);
    }

    public function testAccessors() {
        $params = new Parameters([
            'name' => 'Bort',
            'groups' => [
                ['name' => 'Group1', 'level' => 1],
                ['name' => 'Group2', 'level' => 2]
            ],
            'user' => [
                'login' => 'bort@domain.com'
            ]
        ]);

        $this->assertEquals('Bort', $params['name']);
        $this->assertEquals(1, $params['groups'][0]['level']);
        $this->assertEquals('bort@domain.com', $params['user']['login']);

        $this->assertEquals('Bort', $params->name);
        $this->assertEquals(1, $params->groups[0]->level);
        $this->assertEquals('bort@domain.com', $params->user->login);

        $this->assertNull($params['password']);
        $this->assertNull($params->password);
    }

    public function testFalseShouldWork() {
        $this->assertEquals(false, (new Parameters(['person' => false]))->require('person'));
    }

    public function testRequiredParametersMustNotBeNull() {
        $this->expectException(ParameterMissingException::class);

        $params = (new Parameters(['person' => null]))->require('person');
    }

    public function testRequiredParametersMustNotBeEmpty() {
        $this->expectException(ParameterMissingException::class);

        $params = (new Parameters(['person' => []]))->require('person');
    }

    public function testRequireArrayWhenAllRequiredParamsArePresent() {
        $data = [
            'person' => [
                'first_name' => "Gaurish",
                'title' => "Mjallo",
                'city' => "Barcelona"
            ]
        ];

        $params = (new Parameters($data))
            ->require('person')
            ->require(['first_name', 'title']);

        $this->assertIsArray($params);
        $this->assertEquals(['Gaurish', 'Mjallo'], $params);
    }

    public function testRequireArrayWhenRequiredParamMissing() {
        $data = [
            'person' => [
                'first_name' => "Gaurish",
                'title' => null
            ]
        ];

        $this->expectException(ParameterMissingException::class);

        $params = (new Parameters($data))
            ->require('person')
            ->require(['first_name', 'title']);
    }

    public function testIfNothingIsPermittedTheHashBecomesEmpty() {
        $params = new Parameters(['id' => "1234"]);

        $permitted = $params->permit([]);
        $this->assertTrue($permitted->isPermitted());
        $this->assertEquals([], $permitted->toArray());
    }

    public function testUnknownKeysAreFilteredOut() {
        $params = new Parameters(['id' => "1234", 'injected' => "injected"]);

        $permitted = $params->permit('id');
        $this->assertEquals("1234", $permitted['id']);
        $this->assertFalse($permitted->offsetExists('injected'));
        $this->assertNull($permitted['injected']);
    }

    public function testArraysAreFilteredOut() {
        foreach ([[], [1], ["1"]] as $arr) {
            $params = new Parameters(['id' => $arr]);
            $permitted = $params->permit('id');

            $this->assertFalse($permitted->offsetExists('id'));
            $this->assertNull($permitted['id']);
        }
    }

    public function testHashesAreFilteredOut() {
        foreach ([[], ['foo' => 1], ['foo' => "bar"]] as $hash) {
            $params = new Parameters(['id' => $hash]);
            $permitted = $params->permit('id');
            $this->assertFalse($permitted->offsetExists('id'));
            $this->assertNull($permitted['id']);
        }
    }

    public function testNonPermittedScalarValuesAreFilteredOut() {
        $params = new Parameters(['id' => new stdclass()]);
        $permitted = $params->permit('id');

        $this->assertFalse($permitted->offsetExists('id'));
        $this->assertNull($permitted['id']);
    }

    public function testKeyNotAssignedIfNotPresentInParams() {
        $params = new Parameters(['name' => "Joe"]);
        $permitted = $params->permit(['id' => []]);
        $this->assertFalse($permitted->offsetExists('id'));
    }

    public function testKeyToEmptyArrayPass() {
        $params = new Parameters(['id' => []]);
        $permitted = $params->permit(['id' => []]);
        $this->assertEquals([], $permitted['id']);
    }

    public function testDoNotBreakParamsFilteringOnNullValues() {
        $params = new Parameters(['a' => 1, 'b' => [1, 2, 3], 'c' => null]);

        $permitted = $params->permit('a', ['c' => []], ['b' => []]);
        $this->assertEquals(1, $permitted['a']);
        $this->assertEquals([1, 2, 3], $permitted['b']);
        $this->assertNull($permitted['c']);
    }

    public function testArraysOfPermittedScalarsPass() {
        foreach ([["foo"], [1], ["foo", "bar"], [1, 2, 3]] as $arr) {
            $params = new Parameters(['id' => $arr]);
            $permitted = $params->permit(['id' => []]);
            $this->assertEquals($arr, $permitted['id']);
        }
    }

    public function testPermittedScalarValuesDoNotPass() {
        foreach (["foo", 1] as $permitted_scalar) {
            $params = new Parameters(['id' => $permitted_scalar]);
            $permitted = $params->permit(['id' => []]);

            $this->assertFalse($permitted->offsetExists('id'));
            $this->assertNull($permitted['id']);
        }
    }

    public function testArraysOfNonPermittedScalarDoNotPass() {
        foreach ([[new stdClass()], [[]], [[1]], [['id' => "1"]]] as $non_permitted_scalar) {
            $params = new Parameters(['id' => $non_permitted_scalar]);
            $permitted = $params->permit(['id' => []]);

            $this->assertFalse($permitted->offsetExists('id'));
            $this->assertNull($permitted['id']);
        }
    }

    public function testArbitraryHashesArePermitted() {
        $params = new Parameters([
            'username' => "fxn",
            'preferences' => [
                'scheme' => "Marazul",
                'font' => [
                    'name' => "Source Code Pro",
                    'size' => 12
                ],
                'tabstops' => [4, 8, 12, 16],
                'suspicious' => [true, new stdClass(), false, new HtmlString("yo!")],
                'dubious' => [['a' => 'a', 'b' => new HtmlString("wtf!")], ['c' => 'c']],
                'injected' => new stdClass()
            ],
            'hacked' => 1
        ]);

        $permitted = $params->permit([
            'username',
            'preferences' => new AnyStructure(),
            'hacked' => new AnyStructure()
        ]);

        $this->assertEquals("fxn", $permitted['username']);
        $this->assertEquals("Marazul", $permitted['preferences']['scheme']);
        $this->assertEquals("Source Code Pro", $permitted['preferences']['font']['name']);
        $this->assertEquals(12, $permitted['preferences']['font']['size']);
        $this->assertEquals([4, 8, 12, 16], $permitted['preferences']['tabstops']);
        $this->assertEquals([true, false], $permitted['preferences']['suspicious']);
        $this->assertEquals('a', $permitted['preferences']['dubious'][0]['a']);
        $this->assertEquals('c', $permitted['preferences']['dubious'][1]['c']);

        $this->assertFalse(isset($permitted['preferences']['dubious'][0]['b']));
        $this->assertFalse(isset($permitted['preferences']['injected']));
        $this->assertFalse($permitted->offsetExists('hacked'));
    }

    public function testNestedArraysWithStrings() {
        $params = new Parameters([
            'book' => [
                'genres' => ['Tragedy']
            ]
        ]);
        $permitted = $params->permit(['book' => ['genres' => []]]);

        $this->assertEquals(['Tragedy'], $permitted['book']['genres']);
    }

    public function testNestedArrayWithStringsThatShouldBeHashes() {
        $params = new Parameters([
            'book' => [
                'genres' => ['Tragedy']
            ]
        ]);
        $permitted = $params->permit(['book' => ['genres' => 'type']]);

        $this->assertIsArray($permitted['book']['genres']);
        $this->assertEmpty($permitted['book']['genres']);
    }

    public function testNestedStringThatShouldBeAHash() {
        $params = new Parameters([
            'book' => ['genre' => 'Tragedy']
        ]);
        $permitted = $params->permit(['book' => ['genre' => 'type']]);

        $this->assertNull($permitted['book']['genre']);
    }

    public function testPermitNestedParameters() {
        $params = new Parameters([
            'book' => [
                'title' => "Romeo and Juliet",
                'authors' => [
                    [
                        'name' => "William Shakespeare",
                        'born' => "1564-04-26"
                    ], [
                        'name' => "Christopher Marlowe"
                    ], [
                        'name' => ['malicious', 'injected', 'names']
                    ]
                ],
                'details' => [
                    'pages' => 200,
                    'genre' => "Tragedy"
                ],
                'id' => [
                    'isbn' => "x"
                ]
            ],
            'magazine' => "Mjallo!"
        ]);

        $permitted = $params->permit([
            'book' => [
                'title',
                'authors' => ['name'],
                'details' => ['pages'],
                'id'
            ]
        ]);

        $this->assertTrue($permitted->isPermitted());
        $this->assertEquals('Romeo and Juliet', $permitted['book']['title']);
        $this->assertEquals("William Shakespeare", $permitted['book']['authors'][0]['name']);
        $this->assertEquals("Christopher Marlowe", $permitted['book']['authors'][1]['name']);
        $this->assertEquals(200, $permitted['book']['details']['pages']);

        $this->assertNull($permitted['magazine']);
        $this->assertNull($permitted['book']['id']);
        $this->assertNull($permitted['book']['details']['genre']);
        $this->assertNull($permitted['book']['authors'][0]['born']);
        $this->assertNull($permitted['book']['authors'][2]['name']);
    }

    public function testNestedParamsWithNumericKeys() {
        $params = new Parameters([
            'book' => [
                'authors_attributes' => [
                    '0' => ['name' => 'William Shakespeare', 'age_of_death' => 52],
                    '1' => ['name' => 'Unattributed Assistant'],
                    '2' => ['name' => ['injected', 'names']]
                ]
            ]
        ]);

        $permitted = $params->permit([
            'book' => [
                'authors_attributes' => ['name']
            ]
        ]);

        $this->assertNotNull($permitted['book']['authors_attributes']['0']);
        $this->assertNotNull($permitted['book']['authors_attributes']['1']);
        $this->assertEquals([], $permitted['book']['authors_attributes']['2']->toArray());
        $this->assertEquals('William Shakespeare', $permitted['book']['authors_attributes']['0']['name']);
        $this->assertEquals('Unattributed Assistant', $permitted['book']['authors_attributes']['1']['name']);
    }

    public function testNestedParamsWithNonNumericKeys() {
        $params = new Parameters([
            'book' => [
                'authors_attributes' => [
                    '0' => ['name' => 'William Shakespeare', 'age_of_death' => 52],
                    '1' => ['name' => 'Unattributed Assistant'],
                    '2' => 'Not a hash',
                    'new_record' => ['name' => 'Some name']
                ]
            ]
        ]);

        $permitted = $params->permit([
            'book' => [
                'authors_attributes' => ['name']
            ]
        ]);

        $this->assertNotNull($permitted['book']['authors_attributes']['0']);
        $this->assertNotNull($permitted['book']['authors_attributes']['1']);
        $this->assertNull($permitted['book']['authors_attributes']['2']);
        $this->assertNull($permitted['book']['authors_attributes']['new_record']);

        $this->assertEquals('William Shakespeare', $permitted['book']['authors_attributes']['0']['name']);
        $this->assertEquals('Unattributed Assistant', $permitted['book']['authors_attributes']['1']['name']);

        $this->assertEquals([
            'book' => [
                'authors_attributes' => [
                    '0' => ['name' => 'William Shakespeare'],
                    '1' => ['name' => 'Unattributed Assistant']
                ]
            ]
        ], $permitted->toArray());
    }

    public function testNestedParamsWithNegativeNumericKeys() {
        $params = new Parameters([
            'book' => [
                'authors_attributes' => [
                    '-1' => ['name' => 'William Shakespeare', 'age_of_death' => 52],
                    '-2' => ['name' => 'Unattributed Assistant']
                ]
            ]
        ]);

        $permitted = $params->permit([
            'book' => [
                'authors_attributes' => ['name']
            ]
        ]);

        $this->assertNotNull($permitted['book']['authors_attributes']['-1']);
        $this->assertNotNull($permitted['book']['authors_attributes']['-2']);
        $this->assertEquals('William Shakespeare', $permitted['book']['authors_attributes']['-1']['name']);
        $this->assertEquals('Unattributed Assistant', $permitted['book']['authors_attributes']['-2']['name']);

        $this->assertNull($permitted['book']['authors_attributes']['-1']['age_of_death']);
    }

    public function testNestedParamsWithTargettedNumericKeys() {
        $this->markTestSkipped('PHP cannot really do this since arrays and hashes are the same thing.');
    }

    public function testFetchRaisesException() {
        $this->expectException(ParameterMissingException::class);

        $this->params->fetch('foo');
    }

    public function testFetchWithDefaultValueDoesNotMutateParams() {
        $params = new Parameters([]);
        $params->fetch('foo', []);

        $this->assertNull($params['foo']);
    }

    public function testFetchDoesntRaiseExceptionWithDefault() {
        $this->assertEquals('monkey', $this->params->fetch('foo', 'monkey'));
        $this->assertEquals('monkey', $this->params->fetch('foo', fn() => 'monkey'));
    }

    public function testFetchDoesntRaiseExceptionWithNullAsDefault() {
        $this->assertNull($this->params->fetch('foo', null));
    }

    public function testFetchRaisesExceptionEvenInBlock() {
        $this->expectException(ParameterMissingException::class);

        $this->params->fetch('foo', fn () => $this->params->fetch('also_missing'));
    }

    public function testScalarsAreFilteredWhenArrayIsSpecified() {
        $params = new Parameters(['foo' => 'bar']);

        $this->assertArrayHasKey('foo', $params->permit('foo'));
        $this->assertEquals('bar', $params['foo']);
        $this->assertArrayNotHasKey('foo', $params->permit(['foo' => []]));
        $this->assertArrayNotHasKey('foo', $params->permit(['foo' => ['bar']]));
        $this->assertArrayNotHasKey('foo', $params->permit(['foo' => 'bar']));
    }

    public function testQueries() {
        $params = new Parameters([
            'book' => [
                'title' => "Romeo and Juliet",
                'authors' => [
                    [
                        'name' => "William Shakespeare",
                        'born' => "1564-04-26"
                    ]
                ],
                'details' => [
                    'pages' => 200,
                ]
            ]
        ]);

        $permitted = $params->permit([
            'book' => [
                'title',
                'authors' => ['name'],
                'details' => ['pages']
            ]
        ])['book'];

        $this->assertTrue($permitted->exists('title'));
        $this->assertFalse($permitted->exists('details.pages'));
        $this->assertTrue($permitted->has('title'));
        $this->assertTrue($permitted->has('details.pages'));
        $this->assertTrue($permitted->hasValue('Romeo and Juliet'));
        $this->assertEquals(['title', 'authors', 'details'], $permitted->keys());
        $this->assertEquals('Romeo and Juliet', $permitted->values()[0]);
    }
}

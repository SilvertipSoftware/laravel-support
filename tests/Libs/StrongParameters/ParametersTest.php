<?php

use Illuminate\Support\HtmlString;
use Orchestra\Testbench\TestCase;
use SilvertipSoftware\LaravelSupport\Libs\StrongParameters\AnyStructure;
use SilvertipSoftware\LaravelSupport\Libs\StrongParameters\ParameterMissingException;
use SilvertipSoftware\LaravelSupport\Libs\StrongParameters\Parameters;

class ParametersTest extends TestCase {

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

        $permitted = $params->permit(['a', 'c' => [], 'b' => []]);
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

        $this->assertEquals("fxn",             $permitted['username']);
        $this->assertEquals("Marazul",         $permitted['preferences']['scheme']);
        $this->assertEquals("Source Code Pro", $permitted['preferences']['font']['name']);
        $this->assertEquals(12,                $permitted['preferences']['font']['size']);
        $this->assertEquals([4, 8, 12, 16],    $permitted['preferences']['tabstops']);
        $this->assertEquals([true, false],     $permitted['preferences']['suspicious']);
        $this->assertEquals('a',               $permitted['preferences']['dubious'][0]['a']);
        $this->assertEquals('c',               $permitted['preferences']['dubious'][1]['c']);

        $this->assertFalse(isset($permitted['preferences']['dubious'][0]['b']));
        $this->assertFalse(isset($permitted['preferences']['injected']));
        $this->assertFalse($permitted->offsetExists('hacked'));
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
                'authors' => [['name']],
                'details' => ['pages'],
                'id'
            ]
        ]);

        $this->assertTrue($permitted->isPermitted());
        //var_dump($permitted->toArray());
        $this->assertEquals('Romeo and Juliet', $permitted['book']['title']);
        $this->assertEquals("William Shakespeare", $permitted['book']['authors'][0]['name']);
        $this->assertEquals("Christopher Marlowe", $permitted['book']['authors'][1]['name']);
        $this->assertEquals(200, $permitted['book']['details']['pages']);

        $this->assertNull($permitted['magazine']);
        $this->assertNull($permitted['book']['id']);
        //   assert_filtered_out permitted[:book][:details], :genre
        //   assert_filtered_out permitted[:book][:authors][0], :born
        //   assert_filtered_out permitted[:book][:authors][2], :name
    }
}

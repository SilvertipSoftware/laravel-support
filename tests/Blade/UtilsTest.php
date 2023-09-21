<?php

namespace Tests\Blade;

use PHPUnit\Framework\TestCase;
use SilvertipSoftware\LaravelSupport\Blade\Utils;

class UtilsTest extends TestCase {

    protected $fn;

    public function setUp(): void {
        $this->fn = function () {
            return 1;
        };
    }

    public function testDetermineTagArgsNone() {
        $this->assertEquals([], Utils::determineTagArgs());
    }

    public function testDetermineTagArgsOne() {
        $this->assertEquals([5], Utils::determineTagArgs(5));
        $this->assertEquals([$this->fn], Utils::determineTagArgs($this->fn));

        $this->assertEquals([null], Utils::determineTagArgs(['a']));
    }

    public function testDetermineTagArgsTwo() {
        $this->assertEquals([5, 'a'], Utils::determineTagArgs(5, 'a'));
        $this->assertEquals([5, $this->fn], Utils::determineTagArgs(5, $this->fn));
        $this->assertEquals([null, $this->fn], Utils::determineTagArgs($this->fn, 1));

        $this->assertEquals([null, ['a']], Utils::determineTagArgs(['a'], 1));
    }

    public function testDetermineTagArgsThree() {
        $this->assertEquals([5, 'a', true], Utils::determineTagArgs(5, 'a', true));
        $this->assertEquals([5, 'a', $this->fn], Utils::determineTagArgs(5, 'a', $this->fn));
        $this->assertEquals([5, null, $this->fn], Utils::determineTagArgs(5, $this->fn, 1));
        $this->assertEquals([null, null, $this->fn], Utils::determineTagArgs($this->fn, 1, 2));

        $this->assertEquals([null, ['a'], true], Utils::determineTagArgs(['a'], true, 5));
        $this->assertEquals([null, ['a'], $this->fn], Utils::determineTagArgs(['a'], $this->fn, 1));
        $this->assertEquals([null, ['a'], 5], Utils::determineTagArgs(['a'], 5, $this->fn));
    }

    public function testDetermineTagArgsFour() {
        $this->assertEquals([5, 'a', true, 1], Utils::determineTagArgs(5, 'a', true, 1));
        $this->assertEquals([5, 'a', true, $this->fn], Utils::determineTagArgs(5, 'a', true, $this->fn));
        $this->assertEquals([5, 'a', null, $this->fn], Utils::determineTagArgs(5, 'a', $this->fn, 1));
        $this->assertEquals([5, null, null, $this->fn], Utils::determineTagArgs(5, $this->fn, 1, 2));
        $this->assertEquals([null, null, null, $this->fn], Utils::determineTagArgs($this->fn, 1, 2, 3));

        $this->assertEquals([null, ['a'], true, 5], Utils::determineTagArgs(['a'], true, 5, 1));
        $this->assertEquals([null, ['a'], null, $this->fn], Utils::determineTagArgs(['a'], $this->fn, 1, 2));
        $this->assertEquals([null, ['a'], 5, $this->fn], Utils::determineTagArgs(['a'], 5, $this->fn, 2));
    }
}

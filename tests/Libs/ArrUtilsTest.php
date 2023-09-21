<?php

namespace Tests\Libs;

use Orchestra\Testbench\TestCase;
use SilvertipSoftware\LaravelSupport\Libs\ArrUtils;

class ArrUtilsTest extends TestCase {

    public function testEmptyArray() {
        $this->assertExtract([], [], []);
    }

    public function testOnlyOptions() {
        $this->assertExtract(
            ['option' => 5],
            [],
            ['option' => 5]
        );
    }

    public function testOnlyComponents() {
        $this->assertExtract(
            [],
            ['first', 'second', 3],
            ['first', 'second', 3]
        );
    }

    public function testOptionsAndComponents() {
        $this->assertExtract(
            ['option' => 5, 'another' => 'yes'],
            ['first', 'second', 3],
            ['first', 'second', 'option' => 5, 3, 'another' => 'yes']
        );
    }

    protected function assertExtract($expectedOptions, $expectedComps, $src) {
        $opts = ArrUtils::extractOptions($src);

        $this->assertEquals($expectedOptions, $opts);
        $this->assertEquals($expectedComps, $src);
    }
}

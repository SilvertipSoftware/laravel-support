<?php

namespace Tests\Blade\Directives;

use Orchestra\Testbench\TestCase;
use ReflectionClass;
use ReflectionMethod;
use SilvertipSoftware\LaravelSupport\Blade\FormBuilder;
use SilvertipSoftware\LaravelSupport\Blade\ViewSupport;

class ViewSupportTest extends TestCase {

    public function testCachedRegistrationsAreAccurate() {
        $expected = [
            'helper' => ViewSupport::$registrations['helper'],
            'builder' => ViewSupport::$registrations['builder']
        ];

        $result = ViewSupport::computeRegistrations();

        $this->assertEquals($expected, $result);
    }
}

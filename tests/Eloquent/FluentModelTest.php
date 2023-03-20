<?php

use App\Models\Device;
use Orchestra\Testbench\TestCase;

require_once __DIR__ . '/../models/Device.php';

class FluentModelTest extends TestCase {

    public function setUp(): void {
        $this->device = new Device([
            'model' => 'iPhone',
            'version' => '14'
        ]);
    }

    public function testAccessingProperties() {
        $this->assertTrue(isset($this->device['model']));
        $this->assertEquals('iPhone', $this->device->model);
        $this->assertEquals('iPhone', $this->device['model']);
    }

    public function testSettingProperties() {
        $this->device['model'] = 'Galaxy';
        $this->assertEquals('Galaxy', $this->device->model);

        $this->device->model = 'Pixel';
        $this->assertEquals('Pixel', $this->device->model);
    }

    public function testFluentModelComputedAttributes() {
        $this->assertEquals('Apple', $this->device->vendor);
    }

    public function testFluentModelMutators() {
        $this->device->type = '12 Pro Max';
        $this->assertEquals('12 Pro Max', $this->device->model);

        $this->device['type'] = '14 Pro';
        $this->assertEquals('14 Pro', $this->device->model);
    }


    public function testFluentModelNaming() {
        $this->assertEquals('device', $this->device->model_name->singular);
    }
}

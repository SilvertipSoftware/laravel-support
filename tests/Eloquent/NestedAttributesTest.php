<?php

use App\Models\Customer;
use App\Models\Eye;
use App\Models\InvalidNestedAttrModel;
use App\Models\Iris;
use App\Models\Order;
use SilvertipSoftware\LaravelSupport\Eloquent\Model;

require_once __DIR__ . '/DatabaseTestCase.php';
require_once __DIR__ . '/../models/TestModels.php';

class NestedAttributesTest extends DatabaseTestCase {

    public function testBaseModelDoesNotAcceptNested() {
        $model = new Model();

        $this->assertCount(0, $model->getNestedAttributes());
    }

    public function testRelationNeedsToExist() {
        $this->expectException(RuntimeException::class);
        $model = new InvalidNestedAttrModel();
    }
}

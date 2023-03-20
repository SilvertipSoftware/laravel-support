<?php

use App\Models\Customer;
use App\Models\Eye;
use App\Models\InvalidNestedAttrModel;
use App\Models\Iris;
use App\Models\Order;
use SilvertipSoftware\LaravelSupport\Eloquent\Model;

require_once __DIR__ . '/../DatabaseTestCase.php';
require_once __DIR__ . '/../../models/TestModels.php';

class NestedAttributesHasOneTest extends DatabaseTestCase {

    public function setUp(): void {
        parent::setUp();

        $this->eye = Eye::createOrFail(['side' => 'left'])->fresh();
        Iris::createOrFail(['color' => 'honey', 'eye_id' => $this->eye->id]);

        $this->iris = $this->eye->iris;
    }

    public function testBuildsNewModelIfNoId() {
        $this->iris->delete();
        $this->eye = $this->eye->fresh();

        $this->eye->iris_attributes = ['color' => 'black'];
        $this->assertFalse($this->eye->iris->exists);
        $this->assertEquals('black', $this->eye->iris->color);
    }

    public function testDoesNotBuildNewModelIfNoIdAndDestroying() {
        $this->iris->delete();
        $this->eye = $this->eye->fresh();

        $this->eye->iris_attributes = ['color' => 'black', '_destroy' => true];
        $this->assertNull($this->eye->iris);
    }

    public function testReplacesExistingIfNoId() {
        $this->eye = $this->eye->fresh();

        $this->eye->iris_attributes = ['color' => 'black'];

        $this->assertFalse($this->eye->iris->exists);
        $this->assertEquals('black', $this->eye->iris->color);
        $this->assertEquals('honey', $this->iris->color);
    }

    public function testDoesNotReplaceExistingIfNoIdAndDestroying() {
        $this->eye = $this->eye->fresh();

        $this->eye->iris_attributes = ['color' => 'black', '_destroy' => true];
        $this->assertEquals($this->iris->id, $this->eye->iris->id);
        $this->assertEquals('honey', $this->eye->iris->color);
    }

    public function testUpdatesExistingIfMatchingId() {
        $this->eye->iris_attributes = [
            'id' => $this->iris->id,
            'color' => 'black'
        ];

        $this->assertEquals($this->iris, $this->eye->iris);
        $this->assertEquals('black', $this->eye->iris->color);
    }

    public function testThrowsIfIdGivenButNoRecord() {
        $this->expectException(RuntimeException::class);

        $this->eye->iris_attributes = ['id' => 1234567890];
    }

    public function testUpdatesAttributesOnRelatedModel() {
        $this->eye->iris_attributes = ['id' => $this->iris->id, 'color' => 'black'];

        $this->assertEquals($this->iris->id, $this->eye->iris->id);
        $this->assertEquals('black', $this->eye->iris->color);
    }

    public function testDestroysExistingRecordWithMatchingIdAndDestroyIsTruthy() {
        $this->iris->delete();

        foreach ([1, '1', true] as $ix => $truthy) {
            $iris = $this->eye->iris()->create(['color' => $ix.'black']);
            $this->eye->setRelation('iris', $iris);

            $this->eye->updateOrFail([
                'iris_attributes' => ['id' => $iris->id, '_destroy' => $truthy]
            ]);

            $this->assertNull(Iris::find($iris->id));
            $this->assertNull($this->eye->fresh()->iris);
        }
    }

    public function testDoesNotDestroyRecordWhenDestroyIsFalsey() {
        foreach ([0, '0', false, '', null] as $ix => $falsey) {
            $iris = $this->eye->iris()->create(['color' => $ix.'black']);
            $this->eye->setRelation('iris', $iris);

            $this->eye->updateOrFail([
                'iris_attributes' => ['id' => $iris->id, '_destroy' => $falsey]
            ]);

            $this->assertNotNull(Iris::find($iris->id));
            $this->assertEquals($this->iris, $this->eye->fresh()->iris);
        }
    }

    public function testDoesNotDestroyExistingRecordIfNotAllowedTo() {
        $this->eye = $this->eye->fresh();

        $this->eye->updateOrFail([
            'permanent_iris_attributes' => ['id' => $this->iris->id, '_destroy' => true]
        ]);

        $eye = $this->eye->fresh();
        $this->assertNotNull($eye->iris);
        $this->assertEquals($this->iris->id, $eye->iris->id);
    }

    public function testWorksWithinLargerUpdate() {
        $this->eye->updateOrFail([
            'side' => 'right',
            'iris_attributes' => ['id' => $this->iris->id, 'color' => 'black']
        ]);

        $eye = $this->eye->fresh();

        $this->assertEquals('right', $eye->side);
        $this->assertEquals('black', $eye->iris->color);
    }

    public function testDoesNotDestroyUntilParentIsSaved() {
        $this->eye->iris_attributes = ['id' => $this->iris->id, '_destroy' => true];

        $this->assertTrue($this->eye->iris->exists);
        $this->assertTrue($this->eye->iris->isMarkedForDestruction());

        $this->eye->saveOrFail();

        $this->assertFalse($this->iris->exists);
        $this->assertNull($this->eye->fresh()->iris);
    }

    public function testUpdateOnlyWorks() {
        $this->eye->updateOrFail([
            'update_only_iris_attributes' => ['id' => $this->iris->id, 'color' => 'black']
        ]);

        $this->assertTrue(true);
    }

    public function testCreatesNewModelWhenEmptyAndUpdateOnly() {
        $this->iris->delete();
        $this->eye = $this->eye->fresh();

        $this->eye->updateOrFail([
            'update_only_iris_attributes' => ['color' => 'black']
        ]);

        $this->assertNotNull($this->eye->iris);
    }

    public function testUpdatesExistingWhenUpdateOnlyAndNoIdGiven() {
        $this->iris->delete();
        $eye = $this->eye->fresh();

        $iris = $eye->update_only_iris()->create(['color' => 'black']);
        $eye->updateOrFail([
            'update_only_iris_attributes' => ['color' => 'hazel']
        ]);

        $this->assertEquals('hazel', $iris->fresh()->color);
        $this->assertEquals($iris->id, $eye->fresh()->iris->id);
    }

    public function testUpdatesExistingWhenUpdateOnlyAndIdIsGiven() {
        $this->iris->delete();
        $eye = $this->eye->fresh();

        $iris = $eye->update_only_iris()->create(['color' => 'black']);
        $eye->updateOrFail([
            'update_only_iris_attributes' => ['id' => $iris->id, 'color' => 'hazel']
        ]);

        $this->assertEquals('hazel', $iris->fresh()->color);
        $this->assertEquals($iris->id, $eye->fresh()->iris->id);
    }

    public function testDestroysWhenUpdateOnlyAndIdGiven() {
        $this->iris->delete();
        $eye = $this->eye->fresh();

        $iris = $eye->update_only_iris()->create(['color' => 'black']);
        $eye->updateOrFail([
            'update_and_destroy_iris_attributes' => ['id' => $iris->id, 'color' => 'hazel', '_destroy' => true]
        ]);

        $this->assertNull($eye->fresh()->iris);
        $this->assertNull(Iris::find($iris->id));
    }
}

<?php

use App\Models\Customer;
use App\Models\Eye;
use App\Models\InvalidNestedAttrModel;
use App\Models\Retina;
use App\Models\Order;
use SilvertipSoftware\LaravelSupport\Eloquent\Model;

require_once __DIR__ . '/../DatabaseTestCase.php';
require_once __DIR__ . '/../../models/TestModels.php';

class NestedAttributesBelongsToTest extends DatabaseTestCase {

    public function setUp(): void {
        parent::setUp();

        $this->eye = Eye::createOrFail(['side' => 'left'])->fresh();
        $this->retina = Retina::createOrFail(['status' => 'attached', 'eye_id' => $this->eye->id]);
        $this->eye = $this->retina->eye;
    }

    public function testBuildsNewModelIfNoId() {
        $this->eye->delete();
        $this->retina = $this->retina->fresh();

        $this->retina->eye_attributes = ['side' => 'right'];
        $this->assertFalse($this->retina->eye->exists);
        $this->assertEquals('right', $this->retina->eye->side);
    }

    public function testDoesNotBuildNewModelIfNoIdAndDestroying() {
        $this->eye->delete();
        $this->retina = $this->retina->fresh();

        $this->retina->eye_attributes = ['side' => 'right', '_destroy' => true];
        $this->assertNull($this->retina->eye);
    }

    public function testReplacesExistingIfNoId() {
        $this->retina = $this->retina->fresh();

        $this->retina->eye_attributes = ['side' => 'right'];

        $this->assertFalse($this->retina->eye->exists);
        $this->assertEquals('right', $this->retina->eye->side);
        $this->assertEquals('left', $this->eye->side);
    }

    public function testDoesNotReplaceExistingIfNoIdAndDestroying() {
        $this->retina = $this->retina->fresh();

        $this->retina->eye_attributes = ['side' => 'right', '_destroy' => true];
        $this->assertEquals($this->eye->id, $this->retina->eye->id);
        $this->assertEquals('left', $this->retina->eye->side);
    }

    public function testUpdatesExistingIfMatchingId() {
        $this->retina->eye_attributes = [
            'id' => $this->eye->id,
            'side' => 'right'
        ];

        $this->assertEquals($this->eye, $this->retina->eye);
        $this->assertEquals('right', $this->retina->eye->side);
    }

    public function testThrowsIfIdGivenButNoRecord() {
        $this->expectException(RuntimeException::class);

        $this->retina->eye_attributes = ['id' => 1234567890];
    }

    public function testUpdatesAttributesOnRelatedModel() {
        $this->retina->eye_attributes = ['id' => $this->eye->id, 'side' => 'right'];

        $this->assertEquals($this->eye->id, $this->retina->eye->id);
        $this->assertEquals('right', $this->retina->eye->side);
    }

    public function testDestroysExistingRecordWithMatchingIdAndDestroyIsTruthy() {
        $this->eye->delete();

        foreach ([1, '1', true] as $ix => $truthy) {
            $eye = $this->retina->eye()->create(['side' => 'right']);
            $this->retina->setRelation('eye', $eye);

            $this->retina->updateOrFail([
                'eye_attributes' => ['id' => $eye->id, '_destroy' => $truthy]
            ]);

            $this->assertNull(Eye::find($eye->id));
            $this->assertNull($this->retina->fresh()->eye);
        }
    }

    public function testUnsetsRelationWhenExistingRecordDestroyed() {
        $originalEyeId = $this->eye->id;

        $this->retina->updateOrFail([
            'eye_attributes' => ['id' => $this->eye->id, '_destroy' => true]
        ]);

        $this->assertNull(Eye::find($originalEyeId));
        $this->assertNull($this->retina->eye_id);
        $this->assertNull($this->retina->eye);
    }

    public function testDoesNotDestroyRecordWhenDestroyIsFalsey() {
        foreach ([0, '0', false, '', null] as $ix => $falsey) {
            $eye = $this->retina->eye()->create(['side' => $ix.'right']);
            $this->retina->setRelation('eye', $eye);

            $this->retina->updateOrFail([
                'eye_attributes' => ['id' => $eye->id, '_destroy' => $falsey]
            ]);

            $this->assertNotNull(Eye::find($eye->id));
            $this->assertNotNull($this->retina->fresh()->eye);
        }
    }

    public function testDoesNotDestroyExistingRecordIfNotAllowedTo() {
        $this->retina = $this->retina->fresh();

        $this->retina->updateOrFail([
            'permanent_eye_attributes' => ['id' => $this->eye->id, '_destroy' => true]
        ]);

        $retina = $this->retina->fresh();
        $this->assertNotNull($retina->eye);
        $this->assertEquals($this->eye->id, $retina->eye->id);
    }

    public function testWorksWithinLargerUpdate() {
        $this->retina->updateOrFail([
            'status' => 'detached',
            'eye_attributes' => ['id' => $this->eye->id, 'side' => 'right']
        ]);

        $retina = $this->retina->fresh();

        $this->assertEquals('detached', $retina->status);
        $this->assertEquals('right', $retina->eye->side);
    }

    public function testDoesNotDestroyUntilParentIsSaved() {
        $this->retina->eye_attributes = ['id' => $this->eye->id, '_destroy' => true];

        $this->assertTrue($this->retina->eye->exists);
        $this->assertTrue($this->retina->eye->isMarkedForDestruction());

        $this->retina->saveOrFail();

        $this->assertFalse($this->eye->exists);
        $this->assertNull($this->retina->fresh()->eye);
    }

    public function testCreatesNewModelWhenEmptyAndUpdateOnly() {
        $this->eye->delete();
        $this->retina = $this->retina->fresh();

        $this->retina->updateOrFail([
            'update_only_eye_attributes' => ['side' => 'right']
        ]);

        $this->assertNotNull($this->retina->eye);
    }

    public function testUpdatesExistingWhenUpdateOnlyAndNoIdGiven() {
        $this->eye->delete();
        $retina = $this->retina->fresh();

        $eye = $retina->update_only_eye()->create(['side' => 'right']);
        $retina->setRelation('update_only_eye', $eye);
        $retina->updateOrFail([
            'update_only_eye_attributes' => ['side' => 'middle']
        ]);

        $this->assertEquals('middle', $eye->fresh()->side);
        $this->assertEquals($eye->id, $retina->fresh()->eye->id);
    }

    public function testUpdatesExistingWhenUpdateOnlyAndIdIsGiven() {
        $this->eye->delete();
        $retina = $this->retina->fresh();

        $eye = $retina->update_only_eye()->create(['side' => 'right']);
        $retina->setRelation('update_only_eye', $eye);
        $retina->updateOrFail([
            'update_only_eye_attributes' => ['id' => $eye->id, 'side' => 'middle']
        ]);

        $this->assertEquals('middle', $eye->fresh()->side);
        $this->assertEquals($eye->id, $retina->fresh()->eye->id);
    }

    public function testDestroysWhenUpdateOnlyAndIdGiven() {
        $this->eye->delete();
        $retina = $this->retina->fresh();

        $eye = $retina->update_and_destroy_eye()->create(['side' => 'right']);
        $retina->setRelation('update_and_destroy_eye', $eye);
        $retina->updateOrFail([
            'update_and_destroy_eye_attributes' => ['id' => $eye->id, 'side' => 'middle', '_destroy' => true]
        ]);

        $this->assertNull($retina->fresh()->eye);
        $this->assertNull(Eye::find($eye->id));
    }
}

<?php

use App\Models\Eye;

require_once __DIR__ . '/../../models/TestModels.php';

trait TestsNestedAttributesOnACollection {

    public function testSavesOnlyOneRelatedModelOnCreate() {
        $eye = new Eye([
            'side' => 'right',
            $this->attributesName() => [
                ['color' => 'green']
            ]
        ]);

        $eye->saveOrFail();

        $this->assertEquals(1, $eye->fresh()->{$this->relationName}()->count());
    }

    public function testAssignsAttributesToRelatedModels() {
        $this->eye->updateOrFail($this->alternateParams);

        $this->assertEquals('green', $this->child1->fresh()->color);
        $this->assertEquals('rainbow', $this->child2->fresh()->color);
    }

    public function testDoesNotLoadFullCollectionWhenUpdating() {
        $eye = $this->eye->fresh();

        $eye->{$this->attributesName()} = [
            ['id' => $this->child1->id, 'color' => 'rainbow']
        ];

        $this->assertEquals(1, $eye->{$this->relationName}->count());

        $eye->saveOrFail();

        $this->assertEquals(1, $eye->{$this->relationName}->count());
        $this->assertEquals('rainbow', $this->child1->fresh()->color);
    }

    public function testThrowsWhenGivenIdOfUnknownModel() {
        $this->expectException(RuntimeException::class);

        $this->eye->{$this->attributesName()} = [
            ['id' => 12345678]
        ];
    }

    public function testThrowsWhenGivenIdOfUnownedModel() {
        $otherEye = Eye::createOrFail(['side' => 'right']);
        $otherChild = $otherEye->{$this->relationName}()->create(['color' => 'pinkish']);

        $this->expectException(RuntimeException::class);

        $this->eye->{$this->attributesName()} = [
            ['id' => $otherChild->id]
        ];
    }

    public function testItBuildsModelsWhenIdNotGiven() {
        $this->eye->{$this->relationName}()->delete();
        $eye = $this->eye->fresh();

        $eye->{$this->attributesName()} = [
            ['color' => 'rainbow'],
            ['color' => 'pinkish']
        ];

        $this->assertFalse($eye->{$this->relationName}[0]->exists);
        $this->assertEquals('rainbow', $eye->{$this->relationName}[0]->color);
        $this->assertFalse($eye->{$this->relationName}[1]->exists);
        $this->assertEquals('pinkish', $eye->{$this->relationName}[1]->color);
    }

    public function testItFiltersDestroyFlag() {
        $this->eye->{$this->attributesName()} = [
            ['_destroy' => false]
        ];

        $child = $this->eye->{$this->relationName}->last();
        $this->assertArrayNotHasKey('_destroy', $child->getAttributes());
    }

    public function testIgnoresNewModelsWithDestroyFlag() {
        $this->eye->{$this->relationName}()->delete();
        $eye = $this->eye->fresh();

        $eye->{$this->attributesName()} = [
            ['color' => 'rainbow'],
            ['color' => 'pinkish', '_destroy' => 1]
        ];

        $this->assertEquals(1, $eye->{$this->relationName}->count());
        $this->assertEquals('rainbow', $eye->{$this->relationName}[0]->color);
    }

    public function testUdatesExistingAndAddsNew() {
        $this->alternateParams[$this->attributesName()][] = ['color' => 'pinkish'];
        $numChildren = $this->eye->{$this->relationName}()->count();

        $this->eye->updateOrFail($this->alternateParams);

        $this->assertEquals(
            ['green', 'rainbow', 'pinkish'],
            $this->eye->fresh()->{$this->relationName}->pluck('color')->all()
        );
    }

    public function testDestroysExistingRecordWithMatchingIdAndDestroyIsTruthy() {
        foreach ([1, '1', true] as $ix => $truthy) {
            $this->resetQueries();
            $child = $this->eye->{$this->relationName}()->create(['color' => 'rainbow']);
            $eye = $this->eye->fresh();
            $numChildren = $eye->{$this->relationName}()->count();

            $eye->updateOrFail([
                $this->attributesName() => [
                    ['id' => $child->id, '_destroy' => $truthy]
                ]
            ]);

            $this->assertEquals($numChildren - 1, $eye->{$this->relationName}()->count());
        }
    }

    public function testDoesNotDestroyRecordWhenDestroyIsFalsey() {
        $numChildren = $this->eye->{$this->relationName}()->count();

        foreach ([0, '0', false, '', null] as $ix => $falsey) {
            $this->alternateParams[$this->attributesName()][0]['_destroy'] = $falsey;
            $this->eye->updateOrFail($this->alternateParams);

            $this->assertEquals($numChildren, $this->eye->{$this->relationName}()->count());
        }
    }

    public function testDoesNotDestroyModelsUntilCommit() {
        $this->alternateParams[$this->attributesName()][0]['_destroy'] = true;
        $numChildren = $this->eye->{$this->relationName}->count();

        $this->eye->updateOrFail($this->alternateParams);

        $this->assertEquals($numChildren - 1, $this->eye->{$this->relationName}->count());
    }

    public function testAutomaticallySetsAsAutosaved() {
        $this->assertTrue($this->eye->isAutosaveRelation($this->relationName));
    }

    private function attributesName() {
        return $this->relationName . '_attributes';
    }
}

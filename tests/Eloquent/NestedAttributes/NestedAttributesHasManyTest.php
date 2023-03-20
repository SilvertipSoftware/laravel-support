<?php

use App\Models\Eye;
use SilvertipSoftware\LaravelSupport\Eloquent\Model;

require_once __DIR__ . '/../DatabaseTestCase.php';
require_once __DIR__ . '/../../models/TestModels.php';
require_once __DIR__ . '/TestsNestedAttributesOnACollection.php';

class NestedAttributesHasManyTest extends DatabaseTestCase {
    use TestsNestedAttributesOnACollection;

    public function setUp(): void {
        parent::setUp();

        $this->type = 'HasMany';
        $this->relationName = 'cones';

        $this->eye = Eye::createOrFail(['side' => 'left']);
        $this->eye->cones()->create(['color' => 'red']);
        $this->eye->cones()->create(['color' => 'blue']);

        $this->eye = $this->eye->fresh([$this->relationName]);
        $this->child1 = $this->eye->cones[0];
        $this->child2 = $this->eye->cones[1];

        $this->alternateParams = [
            'cones_attributes' => [
                ['id' => $this->child2->id, 'color' => 'rainbow'],
                ['id' => $this->child1->id, 'color' => 'green']
            ]
        ];
    }
}

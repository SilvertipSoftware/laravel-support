<?php

namespace Tests\Eloquent;

require_once __DIR__ . '/../models/TestModels.php';

use App\Models\Review;
use Orchestra\Testbench\TestCase;
use SilvertipSoftware\LaravelSupport\Libs\StrongParameters\Parameters;

class ModelParametersTest extends TestCase {

    public function testConstruction() {
        $data = [
            'user_id' => 5,
            'content' => 'This is a review'
        ];

        $params = (new Parameters($data))
            ->permit(['user_id', 'content']);

        $review = new Review($params);

        $this->assertEquals(5, $review->user_id);
        $this->assertEquals('This is a review', $review->content);
    }
}

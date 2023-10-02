<?php

require_once __DIR__ . '/DatabaseTestCase.php';
require_once __DIR__ . '/../models/TestModels.php';

use App\Models\Review;
use SilvertipSoftware\LaravelSupport\Libs\StrongParameters\Parameters;

class ModelParametersTest extends DatabaseTestCase {

    public function testConstructTakesParameters() {
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

    public function testUpdateAcceptsParameters() {
        $data = [
            'user_id' => 5,
            'content' => 'This is a review'
        ];

        $params = (new Parameters($data))
            ->permit(['user_id', 'content']);

        $review = Review::create(['content' => 'Pre-content', 'user_id' => 1])->fresh();

        $this->assertTrue($review->update($params));
        $this->assertEquals(5, $review->user_id);
        $this->assertEquals('This is a review', $review->content);
    }

    public function testUpdateOrFailAcceptsParameters() {
        $data = [
            'user_id' => 5,
            'content' => 'This is a review'
        ];

        $params = (new Parameters($data))
            ->permit(['user_id', 'content']);

        $review = Review::create(['content' => 'Pre-content', 'user_id' => 1])->fresh();

        $this->assertTrue($review->updateOrFail($params));
        $this->assertEquals(5, $review->user_id);
        $this->assertEquals('This is a review', $review->content);
    }
}

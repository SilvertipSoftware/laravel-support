<?php

use App\Models\Comment;
use Orchestra\Testbench\TestCase;
use SilvertipSoftware\LaravelSupport\Libs\StrongParameters\Parameters;

require_once __DIR__ . '/../models/Comment.php';

class ModelParametersTest extends TestCase {

    public function testConstruction() {
        $data = [
            'user_id' => 5,
            'content' => 'This is a comment'
        ];

        $params = (new Parameters($data))
            ->permit(['user_id', 'content']);

        $comment = new Comment($params);

        $this->assertEquals(5, $comment->user_id);
        $this->assertEquals('This is a comment', $comment->content);
    }
}

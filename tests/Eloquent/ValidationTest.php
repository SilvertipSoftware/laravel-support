<?php

use App\Models\Comment;
use App\Models\Reply;
use Illuminate\Support\Facades\Lang;
use Orchestra\Testbench\TestCase;

require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Reply.php';

class ValidationTest extends TestCase {

    public function testSingleAttributeValidation() {
        $reply = new Reply();
        $reply->title = 'New Reply';

        $this->assertFalse($reply->isValid());

        $reply->content = 'Some content';
        $this->assertTrue($reply->isValid());
    }

    public function testSingleAttributeValidationErrors() {
        $reply = new Reply();
        $reply->title = 'New Reply';

        $this->assertFalse($reply->isValid());
        $this->assertCount(1, $reply->errors);
        $this->assertTrue($reply->errors->has('content'));
        $this->assertStringContainsString('is required', $reply->errors->first('content'));
    }

    public function assertDoubleAttributeValidation() {
        $reply = new Reply();

        $this->assertFalse($reply->isValid());
        $this->assertCount(2, $reply->errors);
        $this->assertTrue($reply->errors->has('title'));
        $this->assertStringContainsString('is required', $reply->errors->first('title'));
        $this->assertTrue($reply->errors->has('content'));
        $this->assertStringContainsString('is required', $reply->errors->first('content'));
    }

    public function testErrorsClearAfterRecheck() {
        $reply = new Reply();
        $reply->title = 'New Reply';

        $this->assertFalse($reply->isValid());
        $this->assertCount(1, $reply->errors);
        $this->assertTrue($reply->errors->has('content'));

        $reply->content = 'Some text';
        $this->assertTrue($reply->isValid());
        $this->assertTrue($reply->errors->isEmpty());
    }

    public function testCustomAttributeNames() {
        Lang::addLines([
            'eloquent.attributes.comment.content' => 'comment body'
        ], 'en');

        $comment = new Comment();
        $this->assertFalse($comment->isValid());
        $this->assertCount(1, $comment->errors);
        $this->assertTrue($comment->errors->has('content'));
        $this->assertStringContainsString('comment body', $comment->errors->first('content'));
    }

    public function testMethodCallingRule() {
        $reply = new Reply();
        $reply->addMethodRule();

        $reply->title = 'Title';
        $reply->content = 'content';
        $this->assertFalse($reply->isValid());
        $this->assertCount(1, $reply->errors);
        $this->assertTrue($reply->errors->has('title'));
        $this->assertEquals('validation.reply.title_start', $reply->errors->first('title'));

        $reply->title = 'ABCTitle';
        $this->assertTrue($reply->isValid());
        $this->assertTrue($reply->errors->isEmpty());
    }
}

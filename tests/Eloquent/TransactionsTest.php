<?php

use App\Models\Comment;
use App\Models\Movie;
use App\Models\User;

require_once __DIR__ . '/DatabaseTestCase.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Movie.php';
require_once __DIR__ . '/../models/User.php';

class TransactionsTest extends DatabaseTestCase {

    public function setUp(): void {
        parent::setUp();
        $this->comment = Comment::create(['content' => 'Existing']);
    }

    public function testAfterCommitCallbackCalled() {
        $called = false;

        Comment::registerModelEvent('afterCommit', function ($model) use (&$called) {
            $called = true;
        });

        $comment = new Comment(['content' => 'Initial']);
        $comment->saveOrFail();

        $this->assertTrue($called);
    }

    public function testAfterRollbackCallbackCalled() {
        $called = false;

        Comment::registerModelEvent('afterRollback', function ($model) use (&$called) {
            $called = true;
        });

        $comment = new Comment(['content' => 'Initial']);

        try {
            $this->getConnection()->transaction(function () use ($comment) {
                $comment->save();
                throw new Exception('nope');
            });
        } catch (Exception $ex) {
        }

        $this->assertTrue($called);
    }

    public function testSyncOriginalOnSave() {
        $comment = Comment::find($this->comment->id);
        $comment->content = 'new content';
        $comment->saveOrFail();

        $this->assertCount(0, $comment->getDirty());
    }

    public function testRollbackForValidation() {
        $comment = Comment::find($this->comment->id);

        try {
            $this->getConnection()->transaction(function () use ($comment) {
                $comment->update(['content' => null]);
            });
        } catch (Exception $ex) {
        }

        $this->assertEquals(null, $comment->content);
        $this->assertEquals('Existing', $comment->fresh()->content);
    }


    public function testRollbackOfDirtyAttributeFlags() {
        $comment = Comment::find($this->comment->id);

        try {
            $this->getConnection()->transaction(function () use ($comment) {
                $comment->update(['content' => 'New content']);
                throw new Exception('nope');
            });
        } catch (Exception $ex) {
        }

        $this->assertCount(1, $comment->getDirty());
    }

    public function testRollbackOfMultipleChanges() {
        $comment = Comment::find($this->comment->id);

        try {
            $this->getConnection()->transaction(function () use ($comment) {
                $comment->update(['content' => 'New content']);
                $comment->update(['content' => 'More new content']);
                throw new Exception('nope');
            });
        } catch (Exception $ex) {
        }

        $this->assertCount(1, $comment->getDirty());
        $this->assertEquals('Existing', $comment->getOriginal('content'));
        $this->assertEquals('More new content', $comment->content);
    }

    public function testRollbackThenSave() {
        $comment = Comment::find($this->comment->id);

        try {
            $this->getConnection()->transaction(function () use ($comment) {
                $comment->update(['content' => 'New content']);
                throw new Exception('nope');
            });
        } catch (Exception $ex) {
        }

        $comment->update(['content' => 'Retried content']);

        $this->assertCount(0, $comment->getDirty());
        $this->assertEquals($comment->content, $comment->fresh()->content);
    }

    public function testRollbackRevertsKeyExistsAndCreated() {
        $comment = new Comment(['content' => 'Initial']);

        try {
            $this->getConnection()->transaction(function () use ($comment) {
                $comment->save();
                throw new Exception('nope');
            });
        } catch (Exception $ex) {
        }

        $this->assertNull($comment->id);
        $this->assertFalse($comment->exists);
        $this->assertFalse($comment->wasRecentlyCreated);
    }

    public function testRollbackAfterDeleteRevertsExists() {
        $comment = Comment::find($this->comment->id);

        try {
            $this->getConnection()->transaction(function () use ($comment) {
                $comment->delete();
                throw new Exception('nope');
            });
        } catch (Exception $ex) {
        }

        $this->assertNotNull($comment->id);
        $this->assertTrue($comment->exists);
    }

    public function testSuccess() {
        $comment = Comment::find($this->comment->id);

        try {
            $this->getConnection()->transaction(function () use ($comment) {
                $comment->update(['content' => 'New content']);
            });
        } catch (Exception $ex) {
        }

        $this->assertCount(0, $comment->getDirty());
        $this->assertEquals($comment->content, $comment->fresh()->content);
    }

    public function testExceptionInSavingCallbackRollsback() {
        Comment::saving(function ($model) {
            if ($model->content === 'RAISE') {
                throw new Exception('Callback exception');
            }
        });

        $comment = new Comment(['content' => 'RAISE']);

        try {
            $this->getConnection()->transaction(function () use ($comment) {
                $comment->save();
            });
        } catch (Exception $ex) {
        }

        $this->assertNull($comment->id);
        $this->assertFalse($comment->exists);
        $this->assertFalse($comment->wasRecentlyCreated);
    }

    public function testExceptionInSavedCallbackRollsback() {
        Comment::saved(function ($model) {
            if ($model->content === 'RAISE') {
                throw new Exception('Callback exception');
            }
        });

        $comment = new Comment(['content' => 'RAISE']);

        try {
            $this->getConnection()->transaction(function () use ($comment) {
                // does this need to be saveOrFail()?
                $comment->saveOrFail();
            });
        } catch (Exception $ex) {
        }

        $this->assertNull($comment->id);
        $this->assertFalse($comment->exists);
        $this->assertFalse($comment->wasRecentlyCreated);
    }

    public function testStateWhenFailedAfterAlreadyExists() {
        $comment = Comment::find($this->comment->id);

        $comment->content = null;
        $ret = $comment->save();

        $this->assertFalse($ret);
        $this->assertTrue($comment->exists);
    }

    public function testAutosaveRollsbackOnFailure() {
        $user = User::create(['name' => 'Bob']);
        $comment = Comment::create(['content' => 'Comment', 'user_id' => $user->id]);

        $this->assertTrue($user->exists);
        $this->assertTrue($comment->exists);

        $user->name = null;
        $user->setRelation('comments', collect());

        $ret = $user->save();

        $this->assertFalse($ret);
        $this->assertEquals(1, $user->fresh()->comments()->count());
    }

    public function testAutosaveRollsbackOnFailureWithThrow() {
        $user = User::create(['name' => 'Bob']);
        $comment = Comment::create(['content' => 'Comment', 'user_id' => $user->id]);

        $this->assertTrue($user->exists);
        $this->assertTrue($comment->exists);

        $user->name = null;
        $user->setRelation('comments', collect());

        try {
            $ret = $user->saveOrFail();
        } catch (Exception $ex) {
        }

        $this->assertEquals(1, $user->fresh()->comments()->count());
    }

    public function testCancelInDeletingCallbackRollsback() {
        $comment = Comment::first();
        $comment->update(['content' => 'CANCELDELETE']);

        Comment::deleting(function ($model) {
            return $model->content != 'CANCELDELETE';
        });

        $comment = $comment->fresh();

        $numComments = Comment::count();
        $this->assertGreaterThan(0, $numComments);

        $comment->delete();

        $this->assertEquals($numComments, Comment::count());
    }

    public function testFailInCreatingCallback() {
        Comment::creating(function ($model) {
            return $model->content != 'CANCELCREATING';
        });

        $comment = Comment::create(['content' => 'CANCELCREATING']);

        $this->assertFalse($comment->exists);
        $this->assertNull($comment->id);
        $this->assertFalse($comment->wasRecentlyCreated);
    }

    public function testFailInCreatedCallback() {
        Comment::created(function ($model) {
            if ($model->content == 'CANCELCREATED') {
                throw new Exception("fail");
            }
        });

        $numComments = Comment::count();

        $comment = Comment::create(['content' => 'CANCELCREATED']);

        $this->assertEquals($numComments, Comment::count());
        $this->assertFalse($comment->exists);
        $this->assertNull($comment->id);
        $this->assertFalse($comment->wasRecentlyCreated);
    }

    public function testRollbackOfFreshlyCreatedRecords() {
        $comment = Comment::create(['content' => 'Fresh']);

        try {
            $this->getConnection()->transaction(function () use ($comment) {
                $comment->delete();
                throw new Exception("fail");
            });
        } catch (Exception $ex) {
        }

        $this->assertTrue($comment->wasRecentlyCreated);
        $this->assertTrue($comment->exists);
    }

    public function testRestoreStateForAllRecordsInTxn() {
        $comment1 = new Comment(['content' => 'One']);
        $comment2 = new Comment(['content' => 'Two']);

        try {
            $this->getConnection()->transaction(function () use ($comment1, $comment2) {
                $comment1->saveOrFail();
                $comment2->saveOrFail();
                $this->comment->delete();

                $this->assertTrue($comment1->exists);
                $this->assertNotNull($comment1->id);
                $this->assertTrue($comment2->exists);
                $this->assertNotNull($comment1->id);
                $this->assertFalse($this->comment->exists);

                throw new Exception('fail');
            });
        } catch (Exception $ex) {
        }

        $this->assertFalse($comment1->exists);
        $this->assertNull($comment1->id);
        $this->assertFalse($comment2->exists);
        $this->assertNull($comment1->id);
        $this->assertTrue($this->comment->exists);
    }

    public function testRestoreExistsAfterDoubleSave() {
        $comment = new Comment(['content' => 'Fresh']);

        try {
            $this->getConnection()->transaction(function () use ($comment) {
                $comment->saveOrFail();
                $comment->saveOrFail();
                throw new Exception('fail');
            });
        } catch (Exception $ex) {
        }

        $this->assertFalse($comment->exists);
    }

    public function testDontRestoreRecentlyCreatedInNewTransaction() {
        $comment = new Comment(['content' => 'Fresh']);

        $this->getConnection()->transaction(function () use ($comment) {
            $comment->saveOrFail();
        });

        try {
            $this->getConnection()->transaction(function () use ($comment) {
                $comment->saveOrFail();
                throw new Exception("fail");
            });
        } catch (Exception $ex) {
        }

        $this->assertTrue($comment->wasRecentlyCreated);
        $this->assertTrue($comment->exists);
        $this->assertNotNull($comment->id);
    }

    public function testRollbackOfPrimaryKey() {
        $comment = new Comment(['content' => 'Movie']);

        try {
            $this->getConnection()->transaction(function () use ($comment) {
                $comment->saveOrFail();
                throw new Exception("fail");
            });
        } catch (Exception $ex) {
        }

        $this->assertNull($comment->id);
    }

    public function testRollbackOfCustomPrimaryKey() {
        $movie = new Movie(['name' => 'Movie']);

        try {
            $this->getConnection()->transaction(function () use ($movie) {
                $movie->saveOrFail();
                throw new Exception("fail");
            });
        } catch (Exception $ex) {
        }

        $this->assertNull($movie->movieid);
    }
}

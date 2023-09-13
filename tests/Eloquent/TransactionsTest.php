<?php

use App\Models\Review;
use App\Models\Movie;
use App\Models\User;

require_once __DIR__ . '/DatabaseTestCase.php';
require_once __DIR__ . '/../models/TestModels.php';
require_once __DIR__ . '/../models/User.php';

class TransactionsTest extends DatabaseTestCase {

    public function setUp(): void {
        parent::setUp();
        $this->review = Review::create(['content' => 'Existing']);
    }

    public function testAfterCommitCallbackCalled() {
        $called = false;

        Review::registerModelEvent('afterCommit', function ($model) use (&$called) {
            $called = true;
        });

        $review = new Review(['content' => 'Initial']);
        $review->saveOrFail();

        $this->assertTrue($called);
    }

    public function testAfterRollbackCallbackCalled() {
        $called = false;

        Review::registerModelEvent('afterRollback', function ($model) use (&$called) {
            $called = true;
        });

        $review = new Review(['content' => 'Initial']);

        try {
            $this->getConnection()->transaction(function () use ($review) {
                $review->save();
                throw new Exception('nope');
            });
        } catch (Exception $ex) {
        }

        $this->assertTrue($called);
    }

    public function testSyncOriginalOnSave() {
        $review = Review::find($this->review->id);
        $review->content = 'new content';
        $review->saveOrFail();

        $this->assertCount(0, $review->getDirty());
    }

    public function testRollbackForValidation() {
        $review = Review::find($this->review->id);

        try {
            $this->getConnection()->transaction(function () use ($review) {
                $review->update(['content' => null]);
            });
        } catch (Exception $ex) {
        }

        $this->assertEquals(null, $review->content);
        $this->assertEquals('Existing', $review->fresh()->content);
    }


    public function testRollbackOfDirtyAttributeFlags() {
        $review = Review::find($this->review->id);

        try {
            $this->getConnection()->transaction(function () use ($review) {
                $review->update(['content' => 'New content']);
                throw new Exception('nope');
            });
        } catch (Exception $ex) {
        }

        $this->assertCount(1, $review->getDirty());
    }

    public function testRollbackOfMultipleChanges() {
        $review = Review::find($this->review->id);

        try {
            $this->getConnection()->transaction(function () use ($review) {
                $review->update(['content' => 'New content']);
                $review->update(['content' => 'More new content']);
                throw new Exception('nope');
            });
        } catch (Exception $ex) {
        }

        $this->assertCount(1, $review->getDirty());
        $this->assertEquals('Existing', $review->getOriginal('content'));
        $this->assertEquals('More new content', $review->content);
    }

    public function testRollbackThenSave() {
        $review = Review::find($this->review->id);

        try {
            $this->getConnection()->transaction(function () use ($review) {
                $review->update(['content' => 'New content']);
                throw new Exception('nope');
            });
        } catch (Exception $ex) {
        }

        $review->update(['content' => 'Retried content']);

        $this->assertCount(0, $review->getDirty());
        $this->assertEquals($review->content, $review->fresh()->content);
    }

    public function testRollbackRevertsKeyExistsAndCreated() {
        $review = new Review(['content' => 'Initial']);

        try {
            $this->getConnection()->transaction(function () use ($review) {
                $review->save();
                throw new Exception('nope');
            });
        } catch (Exception $ex) {
        }

        $this->assertNull($review->id);
        $this->assertFalse($review->exists);
        $this->assertFalse($review->wasRecentlyCreated);
    }

    public function testRollbackAfterDeleteRevertsExists() {
        $review = Review::find($this->review->id);

        try {
            $this->getConnection()->transaction(function () use ($review) {
                $review->delete();
                throw new Exception('nope');
            });
        } catch (Exception $ex) {
        }

        $this->assertNotNull($review->id);
        $this->assertTrue($review->exists);
    }

    public function testSuccess() {
        $review = Review::find($this->review->id);

        try {
            $this->getConnection()->transaction(function () use ($review) {
                $review->update(['content' => 'New content']);
            });
        } catch (Exception $ex) {
        }

        $this->assertCount(0, $review->getDirty());
        $this->assertEquals($review->content, $review->fresh()->content);
    }

    public function testExceptionInSavingCallbackRollsback() {
        Review::saving(function ($model) {
            if ($model->content === 'RAISE') {
                throw new Exception('Callback exception');
            }
        });

        $review = new Review(['content' => 'RAISE']);

        try {
            $this->getConnection()->transaction(function () use ($review) {
                $review->save();
            });
        } catch (Exception $ex) {
        }

        $this->assertNull($review->id);
        $this->assertFalse($review->exists);
        $this->assertFalse($review->wasRecentlyCreated);
    }

    public function testExceptionInSavedCallbackRollsback() {
        Review::saved(function ($model) {
            if ($model->content === 'RAISE') {
                throw new Exception('Callback exception');
            }
        });

        $review = new Review(['content' => 'RAISE']);

        try {
            $this->getConnection()->transaction(function () use ($review) {
                // does this need to be saveOrFail()?
                $review->saveOrFail();
            });
        } catch (Exception $ex) {
        }

        $this->assertNull($review->id);
        $this->assertFalse($review->exists);
        $this->assertFalse($review->wasRecentlyCreated);
    }

    public function testStateWhenFailedAfterAlreadyExists() {
        $review = Review::find($this->review->id);

        $review->content = null;
        $ret = $review->save();

        $this->assertFalse($ret);
        $this->assertTrue($review->exists);
    }

    public function testAutosaveRollsbackOnFailure() {
        $user = User::create(['name' => 'Bob']);
        $review = Review::create(['content' => 'Review', 'user_id' => $user->id]);

        $this->assertTrue($user->exists);
        $this->assertTrue($review->exists);

        $user->name = null;
        $user->setRelation('reviews', collect());

        $ret = $user->save();

        $this->assertFalse($ret);
        $this->assertEquals(1, $user->fresh()->reviews()->count());
    }

    public function testAutosaveRollsbackOnFailureWithThrow() {
        $user = User::create(['name' => 'Bob']);
        $review = Review::create(['content' => 'Review', 'user_id' => $user->id]);

        $this->assertTrue($user->exists);
        $this->assertTrue($review->exists);

        $user->name = null;
        $user->setRelation('reviews', collect());

        try {
            $ret = $user->saveOrFail();
        } catch (Exception $ex) {
        }

        $this->assertEquals(1, $user->fresh()->reviews()->count());
    }

    public function testCancelInDeletingCallbackRollsback() {
        $review = Review::first();
        $review->update(['content' => 'CANCELDELETE']);

        Review::deleting(function ($model) {
            return $model->content != 'CANCELDELETE';
        });

        $review = $review->fresh();

        $numReviews = Review::count();
        $this->assertGreaterThan(0, $numReviews);

        $review->delete();

        $this->assertEquals($numReviews, Review::count());
    }

    public function testFailInCreatingCallback() {
        Review::creating(function ($model) {
            return $model->content != 'CANCELCREATING';
        });

        $review = Review::create(['content' => 'CANCELCREATING']);

        $this->assertFalse($review->exists);
        $this->assertNull($review->id);
        $this->assertFalse($review->wasRecentlyCreated);
    }

    public function testFailInCreatedCallback() {
        Review::created(function ($model) {
            if ($model->content == 'CANCELCREATED') {
                throw new Exception("fail");
            }
        });

        $numReviews = Review::count();

        $review = Review::create(['content' => 'CANCELCREATED']);

        $this->assertEquals($numReviews, Review::count());
        $this->assertFalse($review->exists);
        $this->assertNull($review->id);
        $this->assertFalse($review->wasRecentlyCreated);
    }

    public function testRollbackOfFreshlyCreatedRecords() {
        $review = Review::create(['content' => 'Fresh']);

        try {
            $this->getConnection()->transaction(function () use ($review) {
                $review->delete();
                throw new Exception("fail");
            });
        } catch (Exception $ex) {
        }

        $this->assertTrue($review->wasRecentlyCreated);
        $this->assertTrue($review->exists);
    }

    public function testRestoreStateForAllRecordsInTxn() {
        $review1 = new Review(['content' => 'One']);
        $review2 = new Review(['content' => 'Two']);

        try {
            $this->getConnection()->transaction(function () use ($review1, $review2) {
                $review1->saveOrFail();
                $review2->saveOrFail();
                $this->review->delete();

                $this->assertTrue($review1->exists);
                $this->assertNotNull($review1->id);
                $this->assertTrue($review2->exists);
                $this->assertNotNull($review1->id);
                $this->assertFalse($this->review->exists);

                throw new Exception('fail');
            });
        } catch (Exception $ex) {
        }

        $this->assertFalse($review1->exists);
        $this->assertNull($review1->id);
        $this->assertFalse($review2->exists);
        $this->assertNull($review1->id);
        $this->assertTrue($this->review->exists);
    }

    public function testRestoreExistsAfterDoubleSave() {
        $review = new Review(['content' => 'Fresh']);

        try {
            $this->getConnection()->transaction(function () use ($review) {
                $review->saveOrFail();
                $review->saveOrFail();
                throw new Exception('fail');
            });
        } catch (Exception $ex) {
        }

        $this->assertFalse($review->exists);
    }

    public function testDontRestoreRecentlyCreatedInNewTransaction() {
        $review = new Review(['content' => 'Fresh']);

        $this->getConnection()->transaction(function () use ($review) {
            $review->saveOrFail();
        });

        try {
            $this->getConnection()->transaction(function () use ($review) {
                $review->saveOrFail();
                throw new Exception("fail");
            });
        } catch (Exception $ex) {
        }

        $this->assertTrue($review->wasRecentlyCreated);
        $this->assertTrue($review->exists);
        $this->assertNotNull($review->id);
    }

    public function testRollbackOfPrimaryKey() {
        $review = new Review(['content' => 'Movie']);

        try {
            $this->getConnection()->transaction(function () use ($review) {
                $review->saveOrFail();
                throw new Exception("fail");
            });
        } catch (Exception $ex) {
        }

        $this->assertNull($review->id);
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

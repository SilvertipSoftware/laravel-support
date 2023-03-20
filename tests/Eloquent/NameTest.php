<?php

use Orchestra\Testbench\TestCase;
use SilvertipSoftware\LaravelSupport\Eloquent\Naming\Name;

require_once __DIR__ . '/../models/Post/TrackBack.php';

class NameTest extends TestCase {

    public function setUp(): void {
        parent::setUp();
        $this->name = new Name(App\Models\Post\TrackBack::class);
    }

    public function testSingular() {
        $this->assertEquals('post_track_back', $this->name->singular);
    }

    public function testPlural() {
        $this->assertEquals('post_track_backs', $this->name->plural);
    }

    public function testElement() {
        $this->assertEquals('track_back', $this->name->element);
    }

    public function testCollection() {
        $this->assertEquals('post/track_backs', $this->name->collection);
    }

    public function testHuman() {
        $this->assertEquals('Track back', $this->name->human);
    }

    public function testRouteKey() {
        $this->assertEquals('post.track-backs', $this->name->route_key);
    }

    public function testParamKey() {
        $this->assertEquals('post_track_back', $this->name->param_key);
    }

    public function testI18nKey() {
        $this->assertEquals('post.track_back', $this->name->i18n_key);
    }

    public function testIsUncountable() {
        $this->assertFalse($this->name->is_uncountable);
    }
}

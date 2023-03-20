<?php

use Orchestra\Testbench\TestCase;
use SilvertipSoftware\LaravelSupport\Eloquent\Naming\Name;

require_once __DIR__ . '/../models/Blog/Post.php';

class NameWithRelativeNamespaceTest extends TestCase {

    public function setUp(): void {
        parent::setUp();
        $this->name = App\Models\Blog\Post::modelName();
    }

    public function testSingular() {
        $this->assertEquals('blog_post', $this->name->singular);
    }

    public function testPlural() {
        $this->assertEquals('blog_posts', $this->name->plural);
    }

    public function testElement() {
        $this->assertEquals('post', $this->name->element);
    }

    public function testCollection() {
        $this->assertEquals('blog/posts', $this->name->collection);
    }

    public function testHuman() {
        $this->assertEquals('Post', $this->name->human);
    }

    public function testRouteKey() {
        $this->assertEquals('posts', $this->name->route_key);
    }

    public function testParamKey() {
        $this->assertEquals('post', $this->name->param_key);
    }

    public function testI18nKey() {
        $this->assertEquals('blog.post', $this->name->i18n_key);
    }

    public function testIsUncountable() {
        $this->assertFalse($this->name->is_uncountable);
    }
}

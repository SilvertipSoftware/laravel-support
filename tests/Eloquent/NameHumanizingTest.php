<?php

use App\Models\Blog\Post;
use Orchestra\Testbench\TestCase;
use SilvertipSoftware\LaravelSupport\Eloquent\Naming\Name;
use SomeVendor\Package\Warehouse;

require_once __DIR__ . '/../models/Blog/Post.php';
require_once __DIR__ . '/../models/package/Warehouse.php';

class NameHumanizingTest extends TestCase {

    public function setUp(): void {
        parent::setUp();
        $this->name = new Name(Post::class);
    }

    public function testHumanAttribute() {
        $this->assertEquals('Post', $this->name->human);
    }

    public function testHumanFunction() {
        $this->assertEquals('Post', $this->name->human());
    }

    public function testHumanWithTranslation() {
        Lang::addLines([
            'eloquent.models.blog.post' => 'Entry'
        ], 'en');

        $this->assertEquals('Entry', $this->name->human());
    }

    public function testHumanWithChoiceTranslation() {
        Lang::addLines([
            'eloquent.models.blog.post' => '{0} Entry|[1,9] Entries|[10,*]Blog'
        ], 'en');

        $this->assertEquals('Entries', $this->name->human(['count' => 5]));
        $this->assertEquals('Blog', $this->name->human(['count' => 15]));
    }

    public function testPackageScopedModels() {
        Lang::addLines([
            'models.some_vendor.package.warehouse' => 'Supply Location'
        ], 'en', 'package');

        $name = new Name(Warehouse::class);
        $this->assertEquals('Supply Location', $name->human());
    }
}

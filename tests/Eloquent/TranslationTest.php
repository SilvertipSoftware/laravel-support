<?php

use App\Models\Blog\Post;
use App\Models\Person;
use App\Models\User;
use Orchestra\Testbench\TestCase;
use SilvertipSoftware\LaravelSupport\Eloquent\Naming\Name;
use SomeVendor\Package\Warehouse;

require_once __DIR__ . '/../models/Blog/Post.php';
require_once __DIR__ . '/../models/Person.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/package/Warehouse.php';

class TranslationTest extends TestCase {

    public function testAttributeName() {
        Lang::addLines([
            'eloquent.attributes.person.name' => 'person name attribute'
        ], 'en');

        $this->assertEquals('person name attribute', Person::humanAttributeName('name'));
    }

    public function testAttributeNameChoice() {
        Lang::addLines([
            'eloquent.attributes.person.name' => 'person name|person names'
        ], 'en');

        $opts = [
            'count' => 5
        ];

        $this->assertEquals('person names', Person::humanAttributeName('name', $opts));
    }

    public function testAttributeNameReplacements() {
        Lang::addLines([
            'eloquent.attributes.person.name' => ':type person name'
        ], 'en');

        $opts = [
            'type' => 'admin'
        ];

        $this->assertEquals('admin person name', Person::humanAttributeName('name', $opts));
    }

    public function testAttributeNameWithGlobal() {
        Lang::addLines([
            'attributes.name' => 'name default attribute'
        ], 'en');

        $this->assertEquals('name default attribute', Person::humanAttributeName('name'));
    }

    public function testAttributeNameWithDefault() {
        $opts = [
            'default' => 'name attribute'
        ];

        $this->assertEquals('name attribute', Person::humanAttributeName('name', $opts));
    }

    public function testAttributeNameFallback() {
        $this->assertEquals('Name', Person::humanAttributeName('name'));
    }

    public function testAttributeNameWithNamespacedModels() {
        Lang::addLines([
            'eloquent.attributes.post.name' => 'post name',
            'eloquent.attributes.blog.post.name' => 'blog post name'
        ], 'en');

        $this->assertEquals('blog post name', Post::humanAttributeName('name'));
    }

    public function testScopedAttributeName() {
        Lang::addLines([
            'security.attributes.user.name' => 'security user name'
        ], 'en');

        $this->assertEquals('security user name', User::humanAttributeName('name'));
    }

    public function testPackageScopedModel() {
        Lang::addLines([
            'attributes.some_vendor.package.warehouse.name' => 'package warehouse name'
        ], 'en', 'package');

        $this->assertEquals('package warehouse name', Warehouse::humanAttributeName('name'));
    }
}

<?php

namespace Tests\Blade\Directives;

require_once __DIR__ . '/../../models/TestFormModels.php';

use App\Models\Post;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use SilvertipSoftware\LaravelSupport\Blade\FormHelper;
use SilvertipSoftware\LaravelSupport\Blade\ViewSupport;
use SilvertipSoftware\LaravelSupport\Eloquent\FluentModel;
use Tests\TestSupport\HtmlAssertions;

class CompendiumTest extends TestCase {
    use DatabaseMigrations,
        DirectiveTestSupport,
        HtmlAssertions;

    public function setUp(): void {
        parent::setUp();
        $this->createFixtures();
        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
    }

    public function testCompendiumCompilesAllDirectives() {
        $result = $this->rendered('compendium', ['posts' => $this->posts]);
        //echo($result);
        $this->assertStringNotContainsString('@', $result);
    }

    public function testMultipleFieldsFor() {
        $this->expectNotToPerformAssertions();
        $blade = '';
        foreach (range(1, 100) as $ix) {
            $blade = "@fieldsFor(\$newPost as \$f)<b>Fields $ix</b>@endFieldsFor\n";
            $this->blade($blade);
        }
    }

    public function testMultipleForms() {
        $this->expectNotToPerformAssertions();
        $blade = '';
        foreach (range(1, 100) as $ix) {
            $blade .= "@formWith(model: \$newPost as \$f)<b>Form $ix</b>@endFormWith\n";
        }

        $this->blade($blade);
    }
}

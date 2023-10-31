<?php

namespace SilvertipSoftware\LaravelSupport\View;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\FileViewFinder;

final class FormatAwareViewFinder extends FileViewFinder {

    /** @var string[] */
    protected array $knownFormats = [
        'turbo_stream',
        'js'
    ];

    public static function register(Application $app): void {
        $app->bind('view.finder', function ($app) {
            return new static($app['files'], $app['config']['view.paths']);
        });
    }

    public function addExtension($extension): void {
        $knownFormatExtensions = array_map(fn ($format) => $format . '.php', $this->knownFormats);

        if (!in_array($extension, $knownFormatExtensions)) {
            parent::addExtension($extension);
        }
    }

    /**
     * @return string[]
     */
    protected function getPossibleViewFiles(mixed $name): array {
        $format = Arr::first($this->knownFormats, fn ($f) => Str::endsWith($name, '.' . $f));
        if (!empty($format)) {
            $name = str_replace('.' . $format, '', $name);
        }

        $extensions = !empty($format)
            ? [$format . '.php']
            : $this->extensions;

        return array_map(fn ($extension) => str_replace('.', '/', $name).'.'.$extension, $extensions);
    }
}

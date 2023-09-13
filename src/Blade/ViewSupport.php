<?php

namespace SilvertipSoftware\LaravelSupport\Blade;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

class ViewSupport {
    use FormHelper,
        FormOptionsHelper,
        FormTagHelper,
        TagHelper,
        UrlHelper;

    public static function registerDirectives() {
        $excluded = [
            'registerDirectives'
        ];
        $contentTags = [
            'contentTag' => 0,
            'cdataSection' => 0,
            'labelTag' => 0,
            'buttonTag' => 0,
            'select' => 0,
            'fieldsFor' => 1
        ];

        $reflection = new ReflectionClass(static::class);
        $methods = array_filter(
            $reflection->getMethods(ReflectionMethod::IS_STATIC && ReflectionMethod::IS_PUBLIC),
            function ($m) use ($excluded) {
                return !in_array($m->name, $excluded);
            }
        );

        foreach ($methods as $method) {
            $name = $method->name;

            if (array_key_exists($name, $contentTags)) {
                static::makeCapturingDirective($name, $contentTags[$name]);
            } else {
                static::makeBareDirective($name);
            }
        }
    }

    private static function makeBareDirective($name) {
        Blade::directive($name, function ($expression) use ($name) {
            return "<?php echo " . static::class . "::"
                . $name . "(" . $expression . "); ?>";
        });
    }

    public static $capturingSections = [];

    private static function makeCapturingDirective($name, $numArgs) {
        if ($numArgs > 0) {
            static::makeNewCapturingDirective($name, $numArgs);
            return;
        }

        Blade::directive($name, function ($expression) use ($name) {
            $id = '__' . $name . '_' . Str::uuid();
            $expr = trim(Blade::stripParentheses($expression));

            $func = "function (\$contentFn) {"
                . "echo " . static::class . "::" . $name . "("
                . $expr . (!empty($expr) ? ", " : "")
                . "\$contentFn);"
                . "}";
            $recordCapture = static::class . "::\$capturingSections[] = ["
                . "'name' => '" . $name . "',"
                . "'id' => '" . $id . "',"
                . "'fn' => " . $func
                . "];";

            $startSection = "\$__env->startSection('" . $id . "');";

            return "<?php " . $recordCapture
                . $startSection
                . "?>";

        });

        Blade::directive($name . 'Inline', function ($expression) use ($name) {
            return "<?php echo " . static::class . "::" . $name . "(" . $expression . "); ?>";
        });

        Blade::directive('end' . ucfirst($name), function ($expression) use ($name) {
            $endSection = "\$__env->stopSection();";
            $data = "\$__lastCaptureData = array_pop(" . static::class . "::\$capturingSections);";
            $captureFn = "function () use (\$__env, \$__lastCaptureData)"
                . " { return new \\Illuminate\\Support\\HtmlString(\$__env->yieldContent(\$__lastCaptureData['id'])); }";

            return "<?php "
                . $endSection
                . $data
                . "\$__lastCaptureData['fn'](" . $captureFn . ");"
                . "?>";
        });
    }

    private static function makeNewCapturingDirective($name, $numArgs) {
        Blade::directive($name, function ($expression) use ($name) {
            $matches = null;
            if (!preg_match('/(.*) do \|(.*)\|/', Blade::stripParentheses($expression), $matches)) {
                throw new RuntimeException('bad block syntax');
            }

            return "<?php " . static::class . "::" . $name . "("
                . $matches[1] . ", function (" . $matches[2] . ") use (\$__env) { ?>";
        });

        Blade::directive('end' . ucfirst($name), function ($expression) {
            return "<?php }); ?>";
        });
    }
}

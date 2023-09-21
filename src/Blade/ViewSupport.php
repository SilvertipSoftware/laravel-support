<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

class ViewSupport {
    use FormHelper,
        FormOptionsHelper,
        FormTagHelper,
        TagHelper;
    use UrlHelper {
        UrlHelper::urlFor as baseUrlFor;
    }

    public static $capturingSections = [];

    public static function registerDirectives($builderPrefix = 'bld') {
        $excluded = [
            'registerDirectives'
        ];
        $contentTags = [
            'contentTag' => -1,
            'cdataSection' => 0,
            'labelTag' => 0,
            'buttonTag' => -1,
            'select' => -1,
            'formFor' => -1,
            'formWith' => -1,
            'fieldsFor' => -1,
            'label' => -1,
            'collectionCheckBoxes' => 1,
            'collectionRadioButtons' => 1,
            'onBuilder' => -1
        ];
        $builderBasedTags = [
            'button' => ['captures' => true],
            'checkBox',
            'collectionCheckBoxes' => ['captures' => true],
            'collectionRadioButtons' => ['captures' => true],
            'collectionSelect',
            'colorField',
            'dateField',
            'datetimeField',
            'datetimeLocalField',
            'emailField',
            'fields' => ['captures' => true],
            'fieldsFor' => ['captures' => true],
            'fieldId',
            'fieldName',
            'fileField',
            'hiddenField',
            'id',
            'label' => ['captures' => true],
            'monthField',
            'numberField',
            'passwordField',
            'phoneField',
            'radioButton',
            'rangeField',
            'searchField',
            'select',
            'submit',
            'telephoneField',
            'textArea',
            'textField',
            'timeField',
            'urlField',
            'weekField',
        ];

        $reflection = new ReflectionClass(static::class);
        $methods = array_filter(
            $reflection->getMethods(ReflectionMethod::IS_STATIC | ReflectionMethod::IS_PUBLIC),
            function ($m) use ($excluded) {
                return !in_array($m->name, $excluded);
            }
        );

        foreach ($methods as $method) {
            $name = $method->name;
            if (preg_match('/^yielding.*$/', $name)) {
                continue;
            }

            if (array_key_exists($name, $contentTags)) {
                static::registerDirective(
                    static::directiveName($name),
                    $name,
                    $contentTags[$name] == 0 ? [] : ['captures' => true]
                );
            } else {
                static::registerDirective(static::directiveName($name), $name);
            }
        }

        foreach ($builderBasedTags as $tag => $options) {
            if (is_int($tag)) {
                $tag = $options;
                $options = [];
            }
            $directiveName = $builderPrefix . ucfirst($tag);
            static::registerDirective(
                static::directiveName([$builderPrefix, $tag]),
                'onBuilder',
                $options,
                $tag
            );
        }
    }

    public static function onBuilder() {
        $args = func_get_args();
        $method = array_shift($args);
        $builder = array_shift($args);

        return call_user_func_array([$builder, $method], $args);
    }

    public static function yieldingOnBuilder() {
        $args = func_get_args();
        $method = array_shift($args);
        $builder = array_shift($args);

        $yieldingMethod = 'yielding' . ucfirst($method);
        $generator = call_user_func_array([$builder, $yieldingMethod], $args);
        yield from $generator;
        return $generator->getReturn();
    }

    protected static function directiveName(string|array $parts): string {
        $parts = (array)$parts;

        foreach ($parts as $ix => $part) {
            if ($ix > 0) {
                $parts[$ix] = ucfirst($part);
            }
        }

        return implode('', $parts);
    }

    protected static function nonCapturingDirectiveCompiler($name, $baseMethodName, $insertArg) {
        return function ($expression) use ($baseMethodName, $insertArg) {
            $expr = trim(Blade::stripParentheses($expression));
            $code = <<<'INLINECODE'
                echo HELPERCLASS::BASEMETHODNAME(METHODNAME EXPRESSION);
            INLINECODE;

            $searchReplace = [
                'HELPERCLASS' => static::class,
                'BASEMETHODNAME' => $baseMethodName,
                'METHODNAME' => ($insertArg ? ("'" . $insertArg . "',") : ''),
                'EXPRESSION' => $expr
            ];

            return "<?php "
                . trim(str_replace(array_keys($searchReplace), array_values($searchReplace), $code))
                . " ?>";
        };
    }

    protected static function capturingStartDirectiveCompiler($name, $baseMethodName, $insertArg) {
        return function ($expression) use ($name, $baseMethodName, $insertArg) {
            $matches = null;
            $pattern = '/(.*) as (\$.*)/';

            if (!preg_match($pattern, Blade::stripParentheses($expression), $matches)) {
                $fn = static::nonCapturingDirectiveCompiler($name, $baseMethodName, $insertArg);
                return $fn($expression);
            }

            $id = '__' . $name . '_' . str_replace('-', '_', '' . Str::uuid());
            $expr = trim(Blade::stripParentheses($matches[1]));

            $code = <<<'STARTCODE'
                $__genSECTIONID = HELPERCLASS::yieldingBASEMETHODNAME(METHODNAME EXPRESSION);
                HELPERCLASS::$capturingSections[] = [
                    'name' => 'BLOCKNAME',
                    'obj' => '__objSECTIONID',
                    'gen' => '__genSECTIONID',
                    'id' => 'SECTIONID'
                ];
                foreach ($__genSECTIONID as $__objSECTIONID):
                    VARDEFINE
                    $__env->startSection('SECTIONID');
                    echo PHP_EOL;
            STARTCODE;

            $searchReplace = [
                'HELPERCLASS' => static::class,
                'BASEMETHODNAME' => ucfirst($baseMethodName),
                'METHODNAME' => ($insertArg ? ("'" . $insertArg . "',") : ''),
                'BLOCKNAME' => $name,
                'SECTIONID' => $id,
                'EXPRESSION' => $expr,
                'VARDEFINE' => (count($matches) > 2 ? ($matches[2] . " = \$__obj" . $id . "->builder;") : '')
            ];

            return "<?php "
                . trim(str_replace(array_keys($searchReplace), array_values($searchReplace), $code))
                . " ?>";
        };
    }

    protected static function capturingEndDirectiveCompiler($name) {
        return function ($expression) use ($name) {
            $code = <<<'ENDCODE'
                    $__env->stopSection(true);
                    $__lastCaptureData = end(HELPERCLASS::$capturingSections);
                    if ($__lastCaptureData['name'] !== 'BLOCKNAME') {
                        throw new \RuntimeException('mismatched end tags (now BLOCKNAME)');
                    }
                    ${$__lastCaptureData['obj']}->content = $__env->yieldContent($__lastCaptureData['id']);
                endforeach;
                $__lastCaptureData = array_pop(HELPERCLASS::$capturingSections);
                echo ${$__lastCaptureData['gen']}->getReturn() . PHP_EOL;
            ENDCODE;

            return "<?php "
                . trim(str_replace(['HELPERCLASS', 'BLOCKNAME'], [static::class, $name], $code))
                . " ?>";
        };
    }

    protected static function registerDirective($name, $baseMethodName, $opts = [], $insertArg = null) {
        $canCapture = Arr::get($opts, 'captures', false);

        static::ensureDirectiveIsNew($name);
        if (!$canCapture) {
            Blade::directive($name, static::nonCapturingDirectiveCompiler($name, $baseMethodName, $insertArg));
        } else {
            Blade::directive($name, static::capturingStartDirectiveCompiler($name, $baseMethodName, $insertArg));

            $endDirectiveName = static::directiveName(['end', $name]);
            static::ensureDirectiveIsNew($endDirectiveName);
            Blade::directive($endDirectiveName, static::capturingEndDirectiveCompiler($name));
        }
    }

    protected static function ensureDirectiveIsNew($name) {
        if (array_key_exists($name, Blade::getCustomDirectives())) {
            throw new RuntimeException('LaravelSupport directive ' . $name . ' is already registered');
        }
    }
}

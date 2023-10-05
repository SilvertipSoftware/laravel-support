<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade;

use Closure;
use Generator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
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

    /** @var array<array{name:string, obj:string, gen:string, id:string}> */
    public static array $capturingSections = [];

    /**
     * These are pre-computed by computeRegistrations() (and tested in UT) to avoid reflection at runtime.
     *
     * @var array{helper: array<string|int,mixed>, builder: array<string|int,mixed>}
     */
    public static array $registrations = [
        'helper' => [
            "buttonTag" => ['captures' => true],
            "collectionCheckBoxes" => ['captures' => true],
            "collectionRadioButtons" => ['captures' => true],
            "contentTag" => ['captures' => true],
            "fields" => ['captures' => true],
            "fieldsFor" => ['captures' => true],
            "formWith" => ['captures' => true],
            "label" => ['captures' => true],
            "labelTag" => ['captures' => true],
            "onBuilder" => ['captures' => true],
            "select" => ['captures' => true],
            "cdataSection",
            "checkBox",
            "checkBoxTag",
            "classNames",
            "closeTag",
            "collectionSelect",
            "colorField",
            "colorFieldTag",
            "dateField",
            "dateFieldTag",
            "datetimeField",
            "datetimeFieldTag",
            "datetimeLocalField",
            "domClass",
            "domId",
            "emailField",
            "emailFieldTag",
            "escapeOnce",
            "fieldId",
            "fieldName",
            "fileField",
            "fileFieldTag",
            "formTagHtml",
            "formTagWithBody",
            "groupedCollectionSelect",
            "groupedOptionsForSelect",
            "hiddenField",
            "hiddenFieldTag",
            "monthField",
            "monthFieldTag",
            "numberField",
            "numberFieldTag",
            "objectForFormBuilder",
            "optionGroupsFromCollectionForSelect",
            "optionsForSelect",
            "optionsFromCollectionForSelect",
            "passwordField",
            "passwordFieldTag",
            "phoneField",
            "radioButton",
            "radioButtonTag",
            "rangeField",
            "rangeFieldTag",
            "searchField",
            "searchFieldTag",
            "submitTag",
            "tag",
            "telephoneField",
            "textArea",
            "textAreaTag",
            "textField",
            "textFieldTag",
            "timeField",
            "timeFieldTag",
            "timeZoneOptionsForSelect",
            "timeZoneSelect",
            "tokenList",
            "urlField",
            "urlFieldTag",
            "urlFor",
            "weekField",
            "weekFieldTag",
            "weekdayOptionsForSelect",
            "weekdaySelect"
        ],
        'builder' => [
            'button' => ['captures' => true],
            'collectionCheckBoxes' => ['captures' => true],
            'collectionRadioButtons' => ['captures' => true],
            'fields' => ['captures' => true],
            'fieldsFor' => ['captures' => true],
            'label' => ['captures' => true],
            'select' => ['captures' => true],
            'checkBox',
            'collectionSelect',
            'colorField',
            'dateField',
            'datetimeField',
            'datetimeLocalField',
            'emailField',
            'fieldId',
            'fieldName',
            'fileField',
            'groupedCollectionSelect',
            'hiddenField',
            'id',
            'monthField',
            'numberField',
            'passwordField',
            'phoneField',
            'radioButton',
            'rangeField',
            'searchField',
            'submit',
            'telephoneField',
            'textArea',
            'textField',
            'timeField',
            'timeZoneSelect',
            'urlField',
            'weekField',
            'weekdaySelect'
        ]
    ];

    public static function registerDirectives(?string $helperPrefix = 'fh', ?string $builderPrefix = null): void {
        foreach (static::$registrations['helper'] as $tag => $options) {
            if (is_int($tag)) {
                $tag = $options;
                $options = [];
            }

            static::registerHelperDirective($tag, $options, $helperPrefix);
        }

        foreach (static::$registrations['builder'] as $tag => $options) {
            if (is_int($tag)) {
                $tag = $options;
                $options = [];
            }

            static::registerBuilderDirective($tag, $options, $builderPrefix);
        }

        Blade::directive(static::directiveName('endBlock'), static::capturingEndDirectiveCompiler());
    }

    /**
     * @return array{helper: array<string,array<string,bool>>, builder: array<string,array<string,bool>>}
     */
    public static function computeRegistrations() {
        $excludedHelperMethods = [
            'computeRegistrations',
            'registerDirectives',
            'registerBuilderDirective',
            'getBuilderMethods',
            'isManyRelation',
            'buildTagValues'
        ];
        $excludedBuilderMethods = [
            '__construct',
            'setIsMultipart',
            '__call',
            'domClass',
            'domId',
            'isManyRelation'
        ];

        $fn = function ($clz, $excluded, $extraMethods = []) {
            $registrations = [];

            $reflection = new ReflectionClass($clz);
            $methods = array_filter(
                $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
                function ($m) use ($excluded) {
                    return !in_array($m->name, $excluded) && strpos($m->name, 'yielding') !== 0;
                }
            );

            foreach ($methods as $method) {
                $name = $method->name;

                $options = [];

                $parameters = $method->getParameters();
                $lastParameterName = count($parameters) > 0
                    ? ($name != 'onBuilder' ? end($parameters)->name : 'block')
                    : '';

                if ($lastParameterName === 'block') {
                    $options['captures'] = true;
                }

                $registrations[$name] = $options;
            }

            $extraMethods = array_diff($extraMethods, array_keys($registrations));
            foreach ($extraMethods as $extra) {
                $registrations[$extra] = [];
            }

            ksort($registrations);

            $keys = array_keys($registrations);
            foreach ($keys as $key) {
                if ($registrations[$key] === []) {
                    unset($registrations[$key]);
                    $registrations[] = $key;
                }
            }


            return $registrations;
        };

        return [
            'helper' => $fn(static::class, $excludedHelperMethods),
            'builder' => $fn(FormBuilder::class, $excludedBuilderMethods, FormBuilder::$fieldHelpers)
        ];
    }

    /**
     * @param array{capture?: bool} $options
     */
    public static function registerBuilderDirective(string $name, array $options, ?string $prefix): void {
        static::registerDirective(
            static::directiveName([$prefix, $name]),
            'onBuilder',
            $options,
            $name
        );
    }

    public static function onBuilder(mixed ...$args): mixed {
        $method = array_shift($args);
        $builder = array_shift($args);

        return call_user_func_array([$builder, $method], $args);
    }

    /**
     * @return Generator<int,\stdClass,null,HtmlString>
     */
    public static function yieldingOnBuilder(mixed ...$args): Generator {
        $method = array_shift($args);
        $builder = array_shift($args);

        $yieldingMethod = 'yielding' . ucfirst($method);
        $generator = call_user_func_array([$builder, $yieldingMethod], $args);
        yield from $generator;

        return $generator->getReturn();
    }

    /**
     * @param string|string[] $parts
     */
    protected static function directiveName(string|array $parts): string {
        $parts = (array)$parts;
        $parts = array_values(array_filter($parts));

        foreach ($parts as $ix => $part) {
            if ($ix > 0) {
                $parts[$ix] = ucfirst($part);
            }
        }

        return implode('', $parts);
    }

    protected static function ensureDirectiveIsNew(string $name): void {
        if (array_key_exists($name, Blade::getCustomDirectives())) {
            throw new RuntimeException('LaravelSupport directive ' . $name . ' is already registered');
        }
    }

    protected static function capturingStartDirectiveCompiler(
        string $name,
        string $baseMethodName,
        ?string $insertArg
    ): Closure {
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

    protected static function capturingEndDirectiveCompiler(): Closure {
        return function ($expression) {
            $code = <<<'ENDCODE'
                    $__env->stopSection(true);
                    $__lastCaptureData = end(HELPERCLASS::$capturingSections);
                    if (!is_array($__lastCaptureData)) {
                        throw new \RuntimeException('mismatched capture end directives');
                    }
                    ${$__lastCaptureData['obj']}->content = $__env->yieldContent($__lastCaptureData['id']);
                endforeach;
                $__lastCaptureData = array_pop(HELPERCLASS::$capturingSections);
                echo ${$__lastCaptureData['gen']}->getReturn() . PHP_EOL;
            ENDCODE;

            return "<?php "
                . trim(str_replace(['HELPERCLASS'], [static::class], $code))
                . " ?>";
        };
    }

    protected static function isPreferredBuilderHelper(string $name): bool {
        return array_key_exists($name, static::$registrations['builder'])
            || in_array($name, array_values(static::$registrations['builder']));
    }

    protected static function nonCapturingDirectiveCompiler(
        string $name,
        string $baseMethodName,
        ?string $insertArg
    ): Closure {
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

    /**
     * @param array{captures?: bool} $opts
     */
    protected static function registerDirective(
        string $name,
        string $baseMethodName,
        array $opts = [],
        ?string $insertArg = null
    ): void {
        $canCapture = Arr::get($opts, 'captures', false);

        static::ensureDirectiveIsNew($name);
        if (!$canCapture) {
            Blade::directive($name, static::nonCapturingDirectiveCompiler($name, $baseMethodName, $insertArg));
        } else {
            Blade::directive($name, static::capturingStartDirectiveCompiler($name, $baseMethodName, $insertArg));
        }
    }

    /**
     * @param array{captures?: bool} $options
     */
    protected static function registerHelperDirective(string $name, array $options, ?string $prefix): void {
        $prefix = static::isPreferredBuilderHelper($name) ? $prefix : null;

        static::registerDirective(
            static::directiveName([$prefix, $name]),
            $name,
            $options
        );
    }
}

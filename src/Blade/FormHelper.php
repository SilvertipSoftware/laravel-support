<?php

namespace SilvertipSoftware\LaravelSupport\Blade;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Traits\ForwardsCalls;
use RuntimeException;
use SilvertipSoftware\LaravelSupport\Routing\RestRouter;

/*
 * Form helpers are designed to make working with resources much easier
 * compared to using vanilla HTML.
 *
 * Typically, a form designed to create or update a resource reflects the
 * identity of the resource in several ways: (i) the URL that the form is
 * sent to (the form element's `action` attribute) should result in a request
 * being routed to the appropriate controller action (with the appropriate `:id`
 * parameter in the case of an existing resource), (ii) input fields should
 * be named in such a way that in the controller their values appear in the
 * appropriate places within the `request()` input array, and (iii) for an existing record,
 * when the form is initially displayed, input fields corresponding to attributes
 * of the resource should show the current values of those attributes.
 *
 * This is usually achieved by creating the form using `formFor` and
 * a number of related helper methods. `formFor` generates an appropriate `form`
 * tag and yields a form builder object that knows the model the form is about.
 * Input fields are created by calling methods defined on the form builder, which
 * means they are able to generate the appropriate names and default values
 * corresponding to the model attributes, as well as convenient IDs, etc.
 * Conventions in the generated field names allow controllers to receive form data
 * nicely structured in `params` with no effort on your side.
 *
 * For example, to create a new person you typically set up a new instance of
 * `Person` in the `PeopleController@new` action, `$person`, and
 * in the code or Blade template pass that object to `formFor`:
 *
 * ```
 *   Helper::formFor($person, [], function ($f) {
 *     return $f->label('first_name') . ':'
 *       . $f->textField('first_name')
 *       . '<br />'
 *       . $f->label('last_name') . ':'
 *       . $f->textField('last_name')
 *       . '<br />'
 *       . $f->submit();
 *   });
 * ```
 *
 * The HTML generated for this would be (modulus formatting):
 *
 * ```
 *   <form action="/people" class="new_person" id="new_person" method="post">
 *     <input name="csrf_token" type="hidden" value="NrOp5bsjoLRuK8IW5+dQEYjKGUJDe7TQoZVvq95Wteg=" />
 *     <label for="person_first_name">First name</label>:
 *     <input id="person_first_name" name="person[first_name]" type="text" /><br />
 *
 *     <label for="person_last_name">Last name</label>:
 *     <input id="person_last_name" name="person[last_name]" type="text" /><br />
 *
 *     <input name="commit" type="submit" value="Create Person" />
 *   </form>
 * ```
 *
 * As you see, the HTML reflects knowledge about the resource in several spots,
 * like the path the form should be submitted to, or the names of the input fields.
 *
 * In particular, thanks to the conventions followed in the generated field names, the
 * controller gets a nested hash `request('person')` with the person attributes
 * set in the form. That hash is ready to be passed to `new Person()`:
 *
 * ```
 *   $person = new Person(request('person'));
 *   if ($person->save()) {
 *     success
 *   } else {
 *     error handling
 *   }
 * ```
 *
 * Interestingly, the exact same view code in the previous example can be used to edit
 * a person. If `$person` is an existing record with name "John Smith" and ID 256,
 * the code above as is would yield instead:
 *
 * ```
 *   <form action="/people/256" class="edit_person" id="edit_person_256" method="post">
 *     <input name="_method" type="hidden" value="patch" />
 *     <input name="csrf_token" type="hidden" value="NrOp5bsjoLRuK8IW5+dQEYjKGUJDe7TQoZVvq95Wteg=" />
 *     <label for="person_first_name">First name</label>:
 *     <input id="person_first_name" name="person[first_name]" type="text" value="John" /><br />
 *
 *     <label for="person_last_name">Last name</label>:
 *     <input id="person_last_name" name="person[last_name]" type="text" value="Smith" /><br />
 *
 *     <input name="commit" type="submit" value="Update Person" />
 *   </form>
 * ```
 *
 * Note that the endpoint, default values, and submit button label are tailored for `$person`.
 * That works that way because the involved helpers know whether the resource is a new record or not,
 * and generate HTML accordingly.
 *
 * The controller would receive the form data again in `request('person')`, ready to be
 * passed to `Person::update`:
 *
 * ```
 *   if ($person->update(request('person'))) {
 *     success
 *   } else {
 *     error handling
 *   }
 * ```
 *
 * That's how you typically work with resources.
 */
trait FormHelper {
    use FormTagHelper,
        ModelUtils,
        TagHelper;

    public static $defaultFormBuilderClass = FormBuilder::class;
    public static $formWithGeneratesIds = true;
    public static $formWithGeneratesRemoteForms = true;
    public static $multipleFileFieldIncludeHidden = false;

    protected static $builders = [];
    //TODO: get vars from view environment somehow... if possible?
    protected static $attributes = [];

    public static function fields($scope = null, $model = null, $options = [], $block = null) {
        if (!is_callable($block)) {
            throw new RuntimeException('fieldsFor requires a callback');
        }

        $defaultOpts = [
            'allow_method_names_outside_object' => true,
            'skip_default_ids' => !static::$formWithGeneratesIds
        ];
        $options = array_merge($defaultOpts, $options);

        if ($model) {
            $model = static::objectForFormBuilder($model);
            $scope = $scope ?? static::modelNameFrom($model)->param_key;
        }

        $builder = static::instantiateBuilder($scope, $model, $options);
        static::pushBuilder($builder);
        $content = $block($builder);
        static::popBuilder();
        return $content;
    }

    public static function fieldsFor($recordName, $recordObject = null, $options = [], $block = null) {
        if (!is_callable($block)) {
            throw new RuntimeException('fieldsFor requires a callback');
        }

        $defaultOpts = [
            'allow_method_names_outside_object' => false,
            'skip_default_ids' => false
        ];
        $options = array_merge($defaultOpts, $options);

        return static::fields($recordName, $recordObject, $options, $block);
    }

    public static function formFor($record, $options = [], $block = null) {
        if (is_string($record)) {
            $model = null;
            $objectName = $record;
        } else {
            $model = static::convertToModel($record);
            $object = static::objectForFormBuilder($record);
            if (!$object) {
                throw new RuntimeException('First argument in form cannot be null or empty');
            }
            $objectName = Arr::get($options, 'as') ?? static::modelNameFrom($object)->param_key;
            static::applyFormForOptions($object, $options);
        }

        $remote = Arr::pull($options, 'remote');

        if ($remote && !static::$embedCsrfInRemoteForms && !Arr::get($options, 'csrf_token')) {
            $options['csrf_token'] = false;
        }

        $options['model'] = $model;
        $options['scope'] = $objectName;
        $options['local'] = !$remote;
        $options['skip_default_ids'] = false;
        $options['allow_method_names_outside_object'] = Arr::get($options, 'allow_method_names_outside_object', false);

        return static::formWith(
            $model,
            $objectName,
            Arr::get($options, 'url'),
            Arr::get($options, 'format'),
            $options,
            $block
        );
    }

    public static function formWith(
        $model = null,
        $scope = null,
        $url = null,
        $format = null,
        $options = [],
        $block = null
    ) {
        $defaultOpts = [
            'allow_method_names_outside_object' => true,
            'skip_default_ids' => !static::$formWithGeneratesIds
        ];
        $options = array_merge($defaultOpts, $options);

        if ($model) {
            if ($url !== false) {
                $url = $url ?? RestRouter::path($model, ['format' => $format]);
            }

            $model = static::objectForFormBuilder($model);
            $scope = $scope ?? static::modelNameFrom($model)->param_key;
        }

        $builder = static::instantiateBuilder($scope, $model, $options);
        static::pushBuilder($builder);

        if (is_callable($block)) {
            $content = $block($builder);
            $options['multipart'] = Arr::get($options, 'multipart', $builder->isMultipart);
            static::popBuilder();

            $htmlOptions = static::htmlOptionsForFormWith($url, $model, $options);
            return static::formTagWithBody($htmlOptions, $content);
        } else {
            $htmlOptions = static::htmlOptionsForFormWith($url, $model, $options);
            return static::formTagHtml($htmlOptions);
        }
    }

    public static function checkBox($objectName, $method, $options = [], $checkedValue = "1", $uncheckedValue = "0") {
        return (new Tags\CheckBox($objectName, $method, static::class, $checkedValue, $uncheckedValue, $options))
            ->render();
    }

    public static function colorField($objectName, $method, $options = []) {
        return (new Tags\ColorField($objectName, $method, static::class, $options))->render();
    }

    public static function dateField($objectName, $method, $options = []) {
        return (new Tags\DateField($objectName, $method, static::class, $options))->render();
    }

    public static function datetimeField($objectName, $method, $options = []) {
        return (new Tags\DatetimeLocalField($objectName, $method, static::class, $options))->render();
    }

    public static function datetimeLocalField($objectName, $method, $options = []) {
        return static::datetimeField($objectName, $method, $options);
    }

    public static function emailField($objectName, $method, $options = []) {
        return (new Tags\EmailField($objectName, $method, static::class, $options))->render();
    }

    public static function fileField($objectName, $method, $options = []) {
        $options = array_merge(
            ['include_hidden' => static::$multipleFileFieldIncludeHidden],
            $options
        );

        $options = static::convertDirectUploadOptionToUrl($options);

        return (new Tags\FileField($objectName, $method, static::class, $options))->render();
    }

    public static function hiddenField($objectName, $method, $options = []) {
        return (new Tags\HiddenField($objectName, $method, static::class, $options))->render();
    }

    public static function label($objectName, $method, $contentOrOptions = null, $options = [], $block = null) {
        return (new Tags\Label($objectName, $method, static::class, $contentOrOptions, $options))->render($block);
    }

    public static function monthField($objectName, $method, $options = []) {
        return (new Tags\MonthField($objectName, $method, static::class, $options))->render();
    }

    public static function numberField($objectName, $method, $options = []) {
        return (new Tags\NumberField($objectName, $method, static::class, $options))->render();
    }

    public static function passwordField($objectName, $method, $options = []) {
        return (new Tags\PasswordField($objectName, $method, static::class, $options))->render();
    }

    public static function radioButton($objectName, $method, $tagValue, $options = []) {
        return (new Tags\RadioButton($objectName, $method, static::class, $tagValue, $options))->render();
    }

    public static function rangeField($objectName, $method, $options = []) {
        return (new Tags\RangeField($objectName, $method, static::class, $options))->render();
    }

    public static function searchField($objectName, $method, $options = []) {
        return (new Tags\SearchField($objectName, $method, static::class, $options))->render();
    }

    public static function telField($objectName, $method, $options = []) {
        return (new Tags\TelField($objectName, $method, static::class, $options))->render();
    }

    public static function textArea($objectName, $method, $options = []) {
        return (new Tags\TextArea($objectName, $method, static::class, $options))->render();
    }

    public static function textField($objectName, $method, $options = []) {
        return (new Tags\TextField($objectName, $method, static::class, $options))->render();
    }

    public static function timeField($objectName, $method, $options = []) {
        return (new Tags\TimeField($objectName, $method, static::class, $options))->render();
    }

    public static function urlField($objectName, $method, $options = []) {
        return (new Tags\UrlField($objectName, $method, static::class, $options))->render();
    }

    public static function weekField($objectName, $method, $options = []) {
        return (new Tags\WeekField($objectName, $method, static::class, $options))->render();
    }

    public static function hasContextVariable($name) {
        return Arr::has(static::$attributes, $name);
    }

    public static function getContextVariable($name) {
        return Arr::get(static::$attributes, $name);
    }

    public static function objectForFormBuilder($object) {
        return is_array($object)
            ? $object[count($object) - 1]
            : $object;
    }

    public static function setContextVariables($vars) {
        static::$attributes = array_merge(static::$attributes, $vars);
    }

    private static function applyFormForOptions($object, &$options) {
        $object = static::convertToModel($object);
        $as = Arr::get($options, 'as');
        $namespace = Arr::get($options, 'namespace');
        $action = static::objectExists($object) ? 'edit' : 'new';

        $htmlOptions = Arr::get($options, 'html', []);
        $class = ($as !== null) ? ($action . '_' . $as) : static::domClass($object, $action);
        $idParts = $as !== null
            ? [$namespace, $action, $as]
            : [$namespace, static::domId($object, $action)];

        $opts = [
            'class' => $class,
            'id' => implode('_', array_filter($idParts, function ($v) { return $v !== null; })) ?: null
        ];

        $options['html'] = array_merge($opts, $htmlOptions);
    }

    private static function htmlOptionsForFormWith($urlForOptions = null, $model = null, $options = []) {
        $html = Arr::pull($options, 'html', []);
        $local = Arr::pull($options, 'local', !static::$formWithGeneratesRemoteForms);
        $skipEnforcingUtf8 = Arr::pull($options, 'skip_enforcing_utf8');

        $htmlOptions = array_merge(
            Arr::only($options, ['id', 'class', 'multipart', 'method', 'data', 'csrf_token']),
            $html
        );
        $htmlOptions['remote'] = Arr::pull($html, 'remote') ?? !$local;

        if (static::objectExists($model)) {
            $htmlOptions['method'] = $htmlOptions['method'] ?? 'patch';
        }

        if ($skipEnforcingUtf8 === null) {
            if (Arr::exists($options, 'enforce_utf8')) {
                $htmlOptions['enforce_utf8'] = Arr::get($options, 'enforce_utf8');
            }
        } else {
            $htmlOptions['enforce_utf8'] = !$skipEnforcingUtf8;
        }

        return static::htmlOptionsForForm($urlForOptions ?? [], $htmlOptions);
    }

    private static function instantiateBuilder($modelName, $modelObject, $options) {
        if (is_string($modelName)) {
            $object = $modelObject;
            $objectName = $modelName;
        } else {
            $object = $modelName;
            $objectName = $object ? static::modelNameFrom($object)->param_key : null;
        }

        $builderClass = Arr::get($options, 'builder') ?? static::$defaultFormBuilderClass;
        return new $builderClass($objectName, $object, static::class, $options);
    }

    private static function objectExists($object) {
        return property_exists($object, 'exists') || method_exists($object, '__get')
            ? $object->exists
            : false;
    }

    private static function pushBuilder($builder) {
        static::$builders[] = $builder;
    }

    private static function popBuilder() {
        return array_pop(static::$builders);
    }
}

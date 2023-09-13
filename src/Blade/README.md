# Forms

## Form Helper

Form helpers are designed to make working with resources much easier
compared to using vanilla HTML.

Typically, a form designed to create or update a resource reflects the
identity of the resource in several ways: (i) the URL that the form is
sent to (the form element's `action` attribute) should result in a request
being routed to the appropriate controller action (with the appropriate `:id`
parameter in the case of an existing resource), (ii) input fields should
be named in such a way that in the controller their values appear in the
appropriate places within the `request()` input array, and (iii) for an existing record,
when the form is initially displayed, input fields corresponding to attributes
of the resource should show the current values of those attributes.

This is usually achieved by creating the form using `formFor` and
a number of related helper methods. `formFor` generates an appropriate `form`
tag and yields a form builder object that knows the model the form is about.
Input fields are created by calling methods defined on the form builder, which
means they are able to generate the appropriate names and default values
corresponding to the model attributes, as well as convenient IDs, etc.
Conventions in the generated field names allow controllers to receive form data
nicely structured in `params` with no effort on your side.

For example, to create a new person you typically set up a new instance of
`Person` in the `PeopleController@new` action, `$person`, and
in the code or Blade template pass that object to `formFor`:

```
  Helper::formFor($person, [], function ($f) {
    return $f->label('first_name') . ':'
      . $f->textField('first_name')
      . '<br />'
      . $f->label('last_name') . ':'
      . $f->textField('last_name')
      . '<br />'
      . $f->submit();
  });
```

The HTML generated for this would be (modulus formatting):

```
  <form action="/people" class="new_person" id="new_person" method="post">
    <input name="csrf_token" type="hidden" value="NrOp5bsjoLRuK8IW5+dQEYjKGUJDe7TQoZVvq95Wteg=" />
    <label for="person_first_name">First name</label>:
    <input id="person_first_name" name="person[first_name]" type="text" /><br />

    <label for="person_last_name">Last name</label>:
    <input id="person_last_name" name="person[last_name]" type="text" /><br />

    <input name="commit" type="submit" value="Create Person" />
  </form>
```

As you see, the HTML reflects knowledge about the resource in several spots,
like the path the form should be submitted to, or the names of the input fields.

In particular, thanks to the conventions followed in the generated field names, the
controller gets a nested hash `request('person')` with the person attributes
set in the form. That hash is ready to be passed to `new Person()`:

```
  $person = new Person(request('person'));
  if ($person->save()) {
    success
  } else {
    error handling
  }
```

Interestingly, the exact same view code in the previous example can be used to edit
a person. If `$person` is an existing record with name "John Smith" and ID 256,
the code above as is would yield instead:

```
  <form action="/people/256" class="edit_person" id="edit_person_256" method="post">
    <input name="_method" type="hidden" value="patch" />
    <input name="csrf_token" type="hidden" value="NrOp5bsjoLRuK8IW5+dQEYjKGUJDe7TQoZVvq95Wteg=" />
    <label for="person_first_name">First name</label>:
    <input id="person_first_name" name="person[first_name]" type="text" value="John" /><br />

    <label for="person_last_name">Last name</label>:
    <input id="person_last_name" name="person[last_name]" type="text" value="Smith" /><br />

    <input name="commit" type="submit" value="Update Person" />
  </form>
```

Note that the endpoint, default values, and submit button label are tailored for `$person`.
That works that way because the involved helpers know whether the resource is a new record or not,
and generate HTML accordingly.

The controller would receive the form data again in `request('person')`, ready to be
passed to `Person::update`:

```
  if ($person->update(request('person'))) {
    success
  } else {
    error handling
  }
```

That's how you typically work with resources.

### formFor

Creates a form that allows the user to create or update the attributes
of a specific model object.

The method can be used in several slightly different ways, depending on
how much you wish to rely on the library to infer automatically from the model
how the form should be constructed. For a generic model object, a form
can be created by passing `formFor` a string representing
the object we are concerned with:

```
  Helper::formFor('person', [], function ($f) {
    return 'First name: ' . $f->textField('first_name') . '<br />'
      . 'Last name : ' . $f->textField('last_name') . '<br />'
      . 'Biography : ' . $f->textArea('biography') . '<br />'
      . 'Admin?    : ' . $f->checkBox('admin') . '<br />'
      . $f->submit();
  });
```

The variable `$f` yielded to the block is a FormBuilder object that
incorporates the knowledge about the model object represented by
`'person'` passed to `formFor`. Methods defined on the FormBuilder
are used to generate fields bound to this model. Thus, for example,
```
  $f->textField('first_name')
```
will get expanded to
```
  Helper::textField('person', 'first_name')
```
which results in an HTML `<input>` tag whose `name` attribute is
`person[first_name]`. This means that when the form is submitted,
the value entered by the user will be available in the controller as
`request('person')['first_name']`.

For fields generated in this way using the FormBuilder,
if `'person'` also happens to be the name of an instance variable
`$person`, the default value of the field shown when the form is
initially displayed (e.g. in the situation where you are editing an
existing record) will be the value of the corresponding attribute of
`$person`.

The rightmost argument to `formFor` is an
optional hash of options -

* `url` - The URL the form is to be submitted to. This may be
  represented in the same way as values passed to `urlFor` or `linkTo`.
  So for example you may use a named route directly. When the model is
  represented by a string or symbol, as in the example above, if the
  `url` option is not specified, by default the form will be
  sent back to the current URL (We will describe below an alternative
  resource-oriented usage of `formFor` in which the URL does not need
  to be specified explicitly).
* `namespace` - A namespace for your form to ensure uniqueness of
  id attributes on form elements. The namespace attribute will be prefixed
  with underscore on the generated HTML id.
* `method` - The method to use when submitting the form, usually
  either "get" or "post". If "patch", "put", "delete", or another verb
  is used, a hidden input with name `_method` is added to
  simulate the verb over post.
* `csrf_token` - Authenticity token to use in the form.
  Use only if you need to pass custom authenticity token string, or to
  not add authenticity_token field at all (by passing `false`).
  Remote forms may omit the embedded authenticity token by setting
  `Helper::$embedAuthenticityTokenInRemoteForms = false`.
  This is helpful when you're fragment-caching the form. Remote forms
  get the authenticity token from the `meta` tag, so embedding is
  unnecessary unless you support browsers without JavaScript.
* `remote` - If set to true, will allow the Unobtrusive
  JavaScript drivers to control the submit behavior.
* `enforce_utf8` - If set to false, a hidden input with name
  utf8 is not output.
* `html` - Optional HTML attributes for the form tag.

Also note that `formFor` doesn't create an exclusive scope. It's still
possible to use both the stand-alone FormHelper methods and methods
from FormTagHelper. For example:
```
  Helper::formFor('person', [], function ($f) {
    return 'First name:' . $f->textField('first_name')
      . 'Last name :' . $f->textField('last_name')
      . 'Biography :' . Helper::textArea('person', 'biography')
      . 'Admin?    :' . Helper::checkBoxTag("person[admin]", "1", $person->company->isAdmin)
      . $f->submit();
  });
```

#### formFor with a model object

In the examples above, the object to be created or edited was
represented by a string passed to `formFor`. It is also possible, however,
to pass a model object itself to `formFor`. For example, if `$post`
is an existing record you wish to edit, you can create the form using
```
  Helper::formFor($post, [], function ($f) {
    ...
  });
```
This behaves in almost the same way as outlined previously, with a
couple of small exceptions. First, the prefix used to name the input
elements within the form (hence the key that denotes them in the `request()`
hash) is actually derived from the object's _class_, e.g. `request('post')`
if the object's class is `Post`. However, this can be overwritten using
the `as` option, e.g. -
```
  Helper::formFor($person, ['as' => 'client'], function ($f) {
    ...
  });
```
would result in `request('client')`.

Secondly, the field values shown when the form is initially displayed
are taken from the attributes of the object passed to `formFor`. So,
for example, if we had a variable `$post`
representing an existing record,
```
  Helper::formFor($post, [], function ($f) {
    ...
  });
```
would produce a form with fields whose initial state reflect the current
values of the attributes of `$post`.

#### Resource-oriented style

In the examples just shown, although not indicated explicitly, we still
need to use the `url` option in order to specify where the
form is going to be sent. However, further simplification is possible
if the record passed to `formFor` is a _resource_, i.e. it corresponds
to a set of RESTful routes, e.g. defined using the `resources` method
in routes. In this case the library will simply infer the
appropriate URL from the record itself. For example,

```
  Helper::formFor($post, [], function ($f) {
    ...
  });
```

is then equivalent to something like:
```
  Helper::formFor(
    $post,
    [
      'as' => 'post',
      'url' => RestRouter::path($post),
      'method' => 'patch',
      'html' => [
        'class' => "edit_post", 'id' => "edit_post_45"
      ]
    ],
    function ($f) {
      ...
    }
  );
```
And for a new record
```
  Helper::formFor(new Post(), [], function ($f) {
    ...
  });
```
is equivalent to something like:
```
  Helper::formFor(
    $post,
    [
      'as' => 'post',
      'url' => RestRouter::path(Post::class),
      'html' => [
        'class' => "new_post",
        'id' => "new_post"
      ]
    ],
    function ($f) {
      ...
    }
  );
```

However you can still overwrite individual conventions, such as:
```
  Helper::formFor($post, ['url' => '/super_post_path'], function ($f) { ... });
```

You can omit the `action` attribute by passing `'url' => false`:
```
  Helper::formFor($post, ['url' => false], function ($f) { ... });
```

You can also set the answer format, like this:
```
  Helper::formFor($post, ['format' => 'json'], function ($f) { ... });
```

For namespaced routes, like '/admin/posts':
```
  Helper::formFor(['admin', $post], [], function ($f) { ... });
```

If your resource has relationships defined, for example, you want to add comments
to the document given that the routes are set correctly:
```
  Helper::formFor([$document, $comment], [] function ($f) { ... });
```
where `$document = Document::find(request('id'))` and
`$comment = new Comment()`.

#### Setting the method

You can force the form to use the full array of HTTP verbs by setting
```
   'method' => ('get'|'post'|'patch'|'put'|'delete')
```
in the options. If the verb is not GET or POST, which are natively
supported by HTML forms, the form will be set to POST and a hidden input
called `_method` will carry the intended verb for the server to interpret.

#### Unobtrusive JavaScript

Specifying:
```
   'remote' => true
```
in the options array creates a form that will allow the unobtrusive JavaScript
drivers to modify its behavior. The form submission will work just like a regular
submission as viewed by the receiving side (all elements available in `request()` input).

Example:
```
  Helper::formFor($post, ['remote' => true], function ($f) { ... });
```

The HTML generated for this would be:
```
  <form action='http://www.example.com' method='post' data-remote='true'>
    <input name='_method' type='hidden' value='patch' />
    ...
  </form>
```

#### Setting HTML options

You can set data attributes directly by passing in a data hash, but all other
HTML options must be wrapped in the HTML key. Example:
```
  Helper::formFor(
    $post,
    [
      'data' => ['behavior' => "autosave"],
      'html' => ['name' => "go"]
    ],
    function ($f) {
      ...
    }
  );
```
The HTML generated for this would be:
```
  <form action='http://www.example.com' method='post' data-behavior='autosave' name='go'>
    <input name='_method' type='hidden' value='patch' />
    ...
  </form>
```

#### Removing hidden model id's

The `formFor` method automatically includes the model id as a hidden field in the form.
This is used to maintain the correlation between the form data and its associated model.
Some ORM systems do not use IDs on nested models so in this case you want to be able
to disable the hidden id.

In the following example the Post model has many Comments stored within it in a NoSQL database,
thus there is no primary key for comments.

Example:
```
  Helper::formFor($post, [], function ($f) {
    return $f->fieldsFor('comments', ['include_id' => false], function ($cf) {
      ...
    });
  });
```

#### Customized form builders

You can also build forms using a customized `FormBuilder` class. Subclass
`FormBuilder` and override or define some more helpers, then use your
custom builder. For example, let's say you made a helper to
automatically add labels to form inputs.
```
  Helper::formFor($person, ['builder' => LabellingFormBuilder::class], function ($f) {
    return $f->textField('first_name')
      . $f->textField('last_name')
      . $f->textArea('biography')
      . $f->checkBox('admin')
      . $f->submit();
  });
```

The custom `FormBuilder` class is automatically merged with the options
of a nested `fieldsFor` call, unless it's explicitly set.

If you don't need to attach a form to a model instance, then check out
`FormTagHelper::formTag()`.

#### Form to external resources

When you build forms to external resources sometimes you need to set an
authenticity token or just render a form without it, for example when you
submit data to a payment gateway number and types of fields could be limited.

To set an authenticity token you need to pass an `csrf_token` parameter
```
  Helper::formFor(
    $invoice,
    ['url' => $externalUrl, 'csrf_token' => 'external_token'],
    function ($f) {
      ...
    }
  );
```

If you don't want to an authenticity token field be rendered at all just pass `false`:
```
  Helper::formFor(
    $invoice,
    ['url' => $externalUrl, 'csrf_token' => false],
    function ($f) {
      ...
    }
  );
```

### fieldsFor

Creates a scope around a specific model object like `formFor`, but
doesn't create the form tags themselves. This makes `fieldsFor` suitable
for specifying additional model objects in the same form.

Although the usage and purpose of `fieldsFor` is similar to `formFor`'s,
its method signature is slightly different. Like `formFor`, it yields
a `FormBuilder` object associated with a particular model object to a block,
and within the block allows methods to be called on the builder to
generate fields associated with the model object. Fields may reflect
a model object in two ways - how they are named (hence how submitted
values appear within the `request()` input array in the controller) and what
default values are shown when the form the fields appear in is first
displayed. In order for both of these features to be specified independently,
both an object name and the object itself can be passed to the method separately -
```
  Helper::formFor($person, [], function ($personForm) {
    return 'First name: ' . $personForm->textField('first_name')
      . 'Last name : ' . $personForm->textField('last_name')
      . Helper::fieldsFor('permission', $person->permission, [], function ($permissionFields) {
        return 'Admin?  : ' . $permissionFields->checkBox('admin');
      })
      . $personForm->submit()
  });
```

In this case, the checkbox field will be represented by an HTML `input`
tag with the `name` attribute `permission[admin]`, and the submitted
value will appear in the controller as `request('permission')['admin']`.
If `$person->permission` is an existing record with an attribute
`admin`, the initial state of the checkbox when first displayed will
reflect the value of `$person->permission->admin`.

Often this can be simplified by passing just the name of the model
object to `fieldsFor` -
```
  Helper::fieldsFor('permission', null, [],function ($permissionFields) {
    return 'Admin?: ' . $permissionFields->checkBox('admin');
  });
```
...in which case, if `'permission'` also happens to be the name of an
instance variable `$permission`, the initial state of the input
field will reflect the value of that variable's attribute `$permission->admin`.

Alternatively, you can pass just the model object itself (if the first
argument isn't a string `fieldsFor` will realize that the
name has been omitted) -
```
  Helper::fieldsFor($person->permission, null, [], function ($permissionFields) {
    return 'Admin?: ' . $permissionFields->checkBox('admin');
  });
```
and `fieldsFor` will derive the required name of the field from the
_class_ of the model object, e.g. if `$person->permission`, is
of class `Permission`, the field will still be named `permission[admin]`.

#### Nested Attributes Examples

When the object belonging to the current scope has a nested attribute
setter for a certain attribute, `fieldsFor` will yield a new scope
for that attribute. This allows you to create forms that set or change
the attributes of a parent object and its associations in one go.

Nested attribute setters are normal setter methods named after an
relationship. The most common way of defining these setters is either
with `acceptsNestedAttributesFor` in a model definition or by
defining a method with the proper name. For example: the attribute
setter for the association `address` is called
`setAddressAttributes`.

Whether a one-to-one or one-to-many style form builder will be yielded
depends on whether the normal getter method returns a _single_ object
or an _array_ of objects.

#### One-to-one

Consider a Person class which returns a _single_ Address from the
`address` getter method and responds to the
`setAddressAttributes` setter method:
```
  class Person
    public $address;

    public function setAddressAttributes($attributes) {
      // Process the attributes
    }
  }
```

This model can now be used with a nested `fieldsFor`, like so:
```
  Helper::formFor($person, [], function ($personForm) {
    ...
    . $personForm->fieldsFor('address', null, [], function ($addressFields) {
      return 'Street  : ' . $addressFields->textField('street')
        . 'Zip code: ' . $addressFields->textField('zip_code');
    })
    ...
  });
```

When address is already an relationship on a Person you can use
`acceptsNestedAttributesFor` to define the setter method for you:
```
  class Person extends Model {
    protected static function bootTraits() {
        parent::bootTraits();
        static::addNestedAttribute('address');
    }

    public function address() {
        return $this->hasOne(Address::class);
    }
  }
```

If you want to destroy the associated model through the form, you have
to enable it first using the `allow_destroy` option for
`acceptsNestedAttributesFor`:
```
  class Person extends Model {
    protected static function bootTraits() {
        parent::bootTraits();
        static::addNestedAttribute('address', ['allow_destroy' => true]);
    }

    public function address() {
        return $this->hasOne(Address::class);
    }
  }
```

Now, when you use a form element with the `_destroy` parameter,
with a value that evaluates to `true`, you will destroy the associated
model (e.g. 1, '1', true, or 'true'):
```
  Helper::formFor($person, [], function ($personForm) {
    ...
    $personForm->fieldsFor('address', null, [], function ($addressFields) {
      ...
      . 'Delete: ' . $addressFields->checkBox('_destroy');
    })
    ...
  });
```

#### One-to-many

Consider a `Person` class which returns an _array_ of `Project` instances
from the `projects` property or `getProjectsAttribute` getter method and responds to the
`setProjectsAttributes()` setter method:
```
  class Person extends FluentModel {
    public function getProjectsAttribute() {
      return [$this->project1, $this->project2];
    }

    public function setProjectsAttributes($attributes)
      // Process the attributes
    }
  }
```

Note that the `setProjectsAttributes` setter method is in fact
required for `fieldsFor` to correctly identify `projects` as a
collection, and the correct indices to be set in the form markup.

When projects is already an association on `Person` you can use
`acceptsNestedAttributesFor` to define the setter method for you:
```
  class Person extends Model {
    protected static function bootTraits() {
        parent::bootTraits();
        static::addNestedAttribute('projects');
    }

    public function projects() {
        return $this->hasMany(Project::class);
    }
  }
```

This model can now be used with a nested `fieldsFor`. The block given to
the nested `fieldsFor` call will be repeated for each instance in the
collection:
```
  Helper::formFor($person, [], function ($personForm) {
    ...
    $personForm->fieldsFor('projects', null, [], function($projectFields) {
      if ($projectFields->object->is_active) {
        return 'Name: ' . $projectFields->textField('name');
      }
    });
    ...
  });
```

It's also possible to specify the instance to be used:
```
  Helper::formFor($person, [], function ($personForm) {
    ...
    foreach ($person->projects as $project) {
      if ($project->is_active) {
        $out .= $personForm->fieldsFor('projects', $project, [], function ($projectFields) {
          return 'Name: ' . $projectFields->textField('name');
        });
      }
    }
    ...
    return $out;
  });
```

Or a collection to be used:
```
  Helper::formFor($person, [], function ($personForm) {
    ...
    $out .= $personForm->fieldsFor('projects', $activeProjects, [], function ($projectFields) {
      return 'Name: ' . $projectFields->textField('name');
    });
    ...
    return $out;
  });
```

If you want to destroy any of the associated models through the
form, you have to enable it first using the `allow_destroy`
option for `acceptsNestedAttributesFor`:
```
  class Person extends Model {
    protected static function bootTraits() {
        parent::bootTraits();
        static::addNestedAttribute('projects', ['allow_destroy' => true]);
    }

    public function projects() {
        return $this->hasMany(Project::class);
    }
  }
```

This will allow you to specify which models to destroy in the
attributes hash by adding a form element for the `_destroy`
parameter with a value that evaluates to `true`
(e.g. 1, '1', true, or 'true'):
```
  Helper::formFor($person, [], function ($personForm) {
    ...
    $out .= $personForm->fieldsFor('projects', null, [], function ($projectFields) {
      return 'Delete: ' . $projectFields->checkBox('_delete');
    });
    ...
    return $out;
  });
```

When a collection is used you might want to know the index of each
object into the array. For this purpose, the `index` method
is available in the `FormBuilder` object.
```
  Helper::formFor($person, [], function ($personForm) {
    ...
    $out .= $personForm->fieldsFor('projects', null, [], function ($projectFields) {
      return 'Project #' . $projectFields->index;
    });
    ...
    return $out;
  });
```

Note that `fieldsFor` will automatically generate a hidden field
to store the ID of the record if it has an `exists` property.
There are circumstances where this hidden field is not needed and you
can pass `'include_id' => false` to prevent `fieldsFor` from
rendering it automatically.

### label

Returns a label tag tailored for labelling an input field for a specified
attribute (identified by +method+) on an object assigned to the template
(identified by +object+). The text of label will default to the attribute name
unless a translation is found in the current I18n locale
(through <tt>helpers.label.<modelname>.<attribute></tt>) or you specify it
explicitly. Additional options on the label tag can be passed as a hash
with +options+. These options will be tagged onto the HTML as an HTML element
attribute as in the example shown, except for the <tt>:value</tt> option, which
is designed to target labels for radio_button tags (where the value is used in
the ID of the input tag).

#### Examples

```
  => Helper::label('post', 'title');
  >>> '<label for="post_title">Title</label>'
```

You can localize your labels based on model and attribute names.
For example you can define the following in your `helpers.php` translation file.

```
  return [
    'label' => [
      'post' => [
        'body' => "Write your entire text here"
      ]
    ]
  ];
```

Which then will result in
```
  => Helper::label('post', 'body');
  >>> '<label for="post_body">Write your entire text here</label>'
```

Localization can also be based purely on the translation of the attribute-name. If you have
the translation file `eloquent.php` with:
```
return [
  'attributes' => [
    'post' => [
      'cost' => "Total cost"
    ]
  ]
];
```

```
  => Helper::label('post', 'cost')
  >>> '<label for="post_cost">Total cost</label>'
```

```
  => Helper::label('post', 'title', "A short title")
  >>> '<label for="post_title">A short title</label>'

  => Helper::label('post', 'title', "A short title", ['class' => "title_label"])
  >>> '<label for="post_title" class="title_label">A short title</label>'

  => Helper::label('post', 'privacy', "Public Post", ['value' => "public"])
  >>> '<label for="post_privacy_public">Public Post</label>'

  => Helper::label('post', 'cost', null, [], function ($b) {
       return Helper::contentTag('span', $b->translation(), ['class' => "cost_label"]);
     });
  >>> '<label for="post_cost"><span class="cost_label">Total cost</span></label>'

  => Helper::label('post', 'cost', null, [], function ($t) {
       return Helper::contentTag('span', $t, ['class' => "cost_label"]);
     });
  >>> '<label for="post_cost"><span class="cost_label">Total cost</span></label>'

  => Helper::label('post', 'terms', null, [], function () {
       return new HtmlString('Accept <a href="/terms">Terms</a>.');
     });
  >>> '<label for="post_terms">Accept <a href="/terms">Terms</a>.</label>'
```

## Form Builder

A `FormBuilder` object is associated with a particular model object and
allows you to generate fields associated with the model object. The
`FormBuilder` object is provided when using `formFor` or `fieldsFor`.
For example:

```
  Helper::formFor($person, function ($personForm) {
    return 'Name: ' . $personForm->textField('name')
        . 'Admin: ' . $personForm->checkBox('admin');
  });
```

In the above block, a `FormBuilder` object is passed as the
`$personForm` variable. This allows you to generate the `textField`
and `checkBox` fields by specifying their eponymous methods, which
modify the underlying template and associates the `$person` model object
with the form.

The `FormBuilder` object can be thought of as serving as a proxy for the
methods in the `FormHelper` class. This class, however, allows you to
call methods with the model object you are building the form for.

You can create your own custom FormBuilder templates by subclassing this
class. For example:

```
  class MyFormBuilder extends FormBuilder {
    public function divRadioButton($method, $tagValue, $options = []) {
      return ($this->template)::contentTag(
        'div',
        ($this->template)::radioButton($this->objectName, $method, $tagValue, $this->objectifyOptions($options))
      );
    }
  }
```

The above code creates a new method `divRadioButton` which wraps a div
around the new radio button. Note that when options are passed in, you
must call `objectifyOptions` in order for the model object to get
correctly passed to the method. If `objectifyOptions` is not called,
then the newly created helper will not be linked back to the model.

The `divRadioButton` code from above can now be used as follows:

```
  Helper::formFor($person, ['builder' => MyFormBuilder], function ($f) {
    return 'I am a child: ' . $f->divRadioButton('admin', 'child')
      . 'I am an adult: ' . $f->divRadioButton('admin', 'adult');
  });
```

The standard set of helper methods for form building are located in the
`$fieldHelpers` class attribute.

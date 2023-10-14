# Form Helpers

Forms in web applications are an essential interface for user input. However, form markup can quickly become tedious to
write and maintain because of the need to handle form control naming and its numerous attributes. Laravel Support does
away with this complexity by providing Blade directives for generating form markup. However, since these helpers have
different use cases, developers need to know the differences between the helper methods before putting them to use.

After reading this guide, you will know:

* How to create search forms and similar kind of generic forms not representing any specific model in your application.
* How to make model-centric forms for creating and editing specific database records.
* How to generate select boxes from multiple types of data.
* What date and time helpers Laravel Support provides.
* What makes a file upload form different.
* How to post forms to external resources and specify setting a CSRF `_token`.
* How to build complex forms.

NOTE: This guide is not intended to be a complete documentation of available form helpers and their arguments.

## Dealing with Basic Forms

The main form helper is `formWith`.

```php
@formWith(['action' => '/'] as $form)
  Form contents
@endBlock
```

When called without arguments like this, it creates a form tag which, when submitted, will POST to the current page. For
instance, assuming the current page is a home page, the generated HTML will (with some bits removed for clarity) look
like this:

```html
<form action="/" accept-charset="UTF-8" method="post">
  <input type="hidden" name="_token" value="..." />
  Form contents
</form>
```

You'll notice that the HTML contains an `input` element with type `hidden`. This `input` is important, because non-GET
forms cannot be successfully submitted without it. The hidden input element with the name `_token` is a
security feature of Laravel called **cross-site request forgery protection**, and form helpers generate it for every
non-GET form (provided that this security feature is enabled).

### A Generic Search Form

One of the most basic forms you see on the web is a search form. This form contains:

* a form element with "GET" method,
* a label for the input,
* a text input element, and
* a submit element.

To create this form you will use `formWith` and the form builder object it yields. Like so:

```php
@formWith(url: '/search', options: ['method' => 'get'] as $form)
  @label($form, 'query', 'Search for:')
  @textField($form, 'query')
  @submit($form, "Search")
@endBlock
```

This will generate the following HTML:

```html
<form action="/search" method="get" accept-charset="UTF-8" >
  <label for="query">Search for:</label>
  <input id="query" name="query" type="text" />
  <input name="commit" type="submit" value="Search" data-disable-with="Search" />
</form>
```

TIP: Passing `url: my_specified_path` to `formWith` tells the form where to make the request. However, as explained
below, you can also pass Model objects to the form.

TIP: For every form input, an ID attribute is generated from its name (`"query"` in above example). These IDs can be
very useful for CSS styling or manipulation of form controls with JavaScript.

IMPORTANT: Use "GET" as the method for search forms. This allows users to bookmark a specific search and get back to it.
More generally Laravel Support encourages you to use the right HTTP verb for an action.

### Helpers for Generating Form Elements

The form builder object yielded by `formWith` provides numerous helper methods for generating form elements such as text
fields, checkboxes, and radio buttons. The first two parameters to these directives is always the form builder, and the
name of the input. When the form is submitted, the name will be passed along with the form data, and will make its way
to the `request()` input in the controller with the value entered by the user for that field. For example, if the form
contains `@textField($form, 'query')`, then you would be able to get the value of this field in the controller with
`request('query')`.

When naming inputs, Laravel Support uses certain conventions that make it possible to submit parameters with non-scalar
values such as arrays, which will also be accessible in `request()` input. You can read more about them in the
[Understanding Parameter Naming Conventions](#understanding-parameter-naming-conventions) section of this guide.

#### Checkboxes

Checkboxes are form controls that give the user a set of options they can enable or disable:

```php
@checkBox($form, 'pet_dog')
@label($form, 'pet_dog', 'I own a dog')
@checkBox($form, 'pet_cat')
@label($form, 'pet_cat', 'I own a cat')
```

This generates the following:

```html
<input type="checkbox" id="pet_dog" name="pet_dog" value="1" />
<label for="pet_dog">I own a dog</label>
<input type="checkbox" id="pet_cat" name="pet_cat" value="1" />
<label for="pet_cat">I own a cat</label>
```

The checkbox's values (the values that will appear in `request()`) can optionally be specified using the fourth and
fifth parameters. See the code for details.

#### Radio Buttons

Radio buttons, while similar to checkboxes, are controls that specify a set of options in which they are mutually
exclusive (i.e., the user can only pick one):

```php
@radioButton($form, 'age', 'child')
@label($form, 'age_child', 'I am younger than 21')
@radioButton($form, 'age', 'adult')
@label($form, 'age_adult', 'I am over 21')
```

Output:

```html
<input type="radio" id="age_child" name="age" value="child" />
<label for="age_child">I am younger than 21</label>
<input type="radio" id="age_adult" name="age" value="adult" />
<label for="age_adult">I am over 21</label>
```

The third parameter to `radioButton` is the value of the input. Because these two radio buttons share the same name
(`age`), the user will only be able to select one of them, and `request('age')` will contain either `"child"` or
`"adult"`.

NOTE: Always use labels for checkbox and radio buttons. They associate text with a specific option and, by expanding the
clickable region, make it easier for users to click the inputs.

### Other Helpers of Interest

Other form controls worth mentioning are text areas, hidden fields, password fields, number fields, date and time
fields, and many more:

```php
@textArea($form, 'message', ['size' => "70x5"])
@hiddenField($form, 'parent_id', ['value' => "foo"])
@passwordField($form, 'password')
@numberField($form, 'price', ['in' => [1.0, 20.0], 'step' => 0.5])
@rangeField($form, 'discount', ['in' => [1, 100]])
@dateField($form, 'born_on')
@timeField($form, 'started_at')
@datetimeLocalField($form, 'graduation_day')
@monthField($form, 'birthday_month')
@weekField($form, 'birthday_week')
@searchField($form, 'name')
@emailField($form, 'address')
@telephoneField($form, 'phone')
@urlField($form, 'homepage')
@colorField($form, 'favorite_color')
```

Output:

```html
<textarea name="message" id="message" cols="70" rows="5"></textarea>
<input type="hidden" name="parent_id" id="parent_id" value="foo" />
<input type="password" name="password" id="password" />
<input type="number" name="price" id="price" step="0.5" min="1" max="20" />
<input type="range" name="discount" id="discount" min="1" max="100" />
<input type="date" name="born_on" id="born_on" />
<input type="time" name="started_at" id="started_at" />
<input type="datetime-local" name="graduation_day" id="graduation_day" />
<input type="month" name="birthday_month" id="birthday_month" />
<input type="week" name="birthday_week" id="birthday_week" />
<input type="search" name="name" id="name" />
<input type="email" name="address" id="address" />
<input type="tel" name="phone" id="phone" />
<input type="url" name="homepage" id="homepage" />
<input type="color" name="favorite_color" id="favorite_color" value="#000000" />
```

Hidden inputs are not shown to the user but instead hold data like any textual input. Values inside them can be changed
with JavaScript.

IMPORTANT: The search, telephone, date, time, color, datetime, datetime-local, month, week, URL, email, number, and
range inputs are HTML5 controls. If you require your app to have a consistent experience in older browsers, you will
need an HTML5 polyfill (provided by CSS and/or JavaScript). There is definitely [no shortage of solutions for this]
(https://github.com/Modernizr/Modernizr/wiki/HTML5-Cross-Browser-Polyfills), although a popular tool at the moment is
[Modernizr](https://modernizr.com/), which provides a simple way to add functionality based on the presence of detected
HTML5 features.


## Dealing with Model Objects

### Binding a Form to an Object

The `model` argument of `formWith` allows us to bind the form builder object to a model object. This means that the form
will be scoped to that model object, and the form's fields will be populated with values from that model object.

For example, if we have an `$article` model object like:

```php
$article = Article::find(42)
# => Article {id: 42, title: "My Title", body: "My Body"}
```

The following form:

```php
@formWith(model: $article as $form)
  @textField($form, 'title')
  @textArea($form, 'body', ['size' => "60x10"])
  @submit($form)
@endBlock
```

Outputs:

```html
<form action="/articles/42" method="post" accept-charset="UTF-8" >
  <input name="_token" type="hidden" value="..." />
  <input type="text" name="article[title]" id="article_title" value="My Title" />
  <textarea name="article[body]" id="article_body" cols="60" rows="10">
    My Body
  </textarea>
  <input type="submit" name="commit" value="Update Article" data-disable-with="Update Article">
</form>
```

There are several things to notice here:

* The form `action` is automatically filled with an appropriate value for `$article`.
* The form fields are automatically filled with the corresponding values from `$article`.
* The form field names are scoped with `article[...]`. This means that `request('article')` will be a hash containing
  all these field's values. You can read more about the significance of input names in chapter [Understanding Parameter
  Naming Conventions](#understanding-parameter-naming-conventions) of this guide.
* The submit button is automatically given an appropriate text value.

TIP: Conventionally your inputs will mirror model attributes. However, they don't have to! If there is other information
you need you can include it in your form just as with attributes and access it via
`request('article')['my_nifty_non_attribute_input']`.

#### The `fhFieldsFor`/`fieldsFor` Helper

The `fhFieldsFor` helper creates a similar binding but without rendering a `<form>` tag. This can be used to render fields
for additional model objects within the same form. For example, if you had a `Person` model with an associated
`ContactDetail` model, you could create a single form for both like so:

```php
@formWith(model: $person as $personForm)
  @textField($personForm, 'name')
  @fhFieldsFor('contact_detail', $person->contact_detail as $contactDetailForm)
    @textField($contactDetailForm, 'phone_number')
  @endBlock
@endBlock
```

Which produces the following output:

```html
<form action="/people" accept-charset="UTF-8" method="post">
  <input type="hidden" name="_token" value="..." />
  <input type="text" name="person[name]" id="person_name" />
  <input type="text" name="contact_detail[phone_number]" id="contact_detail_phone_number" />
</form>
```

The object yielded by `fhFieldsFor` is a form builder like the one yielded by `form_with`.

### Relying on Model Identification

The Article model is directly available to users of the application, so you should declare it **a resource**:

```php
Route::resource('articles', ArticlesController::class);
```

When dealing with RESTful resources, calls to `formWith` can get significantly easier if you rely on **model
identification**. In short, you can just pass the model instance and have Laravel Support figure out model name and the
rest. In both of these examples, the long and short style have the same outcome:

```php
//// Creating a new article
// long-style:
@formWith(model: $article, url: route('articles.store'), [], false))
// short-style:
@formWith(model: $article)

//// Editing an existing article
// long-style:
@formWith(model: $article, url: route('articles.update', [], false), options: ['method' => 'patch'])
// short-style:
@formWith(model: $article)
```

Notice how the short-style `formWith` invocation is conveniently the same, regardless of the model being new or
existing. Model identification is smart enough to figure out if the model is new by using `model->exists`. It also
selects the correct path to submit to, and the name based on the class of the object.

**TBD: singular resources?**

WARNING: When you're using STI (single-table inheritance) with your models, you can't rely on model identification on a
subclass if only their parent class is declared a resource. You will have to specify `url`, and `scope` (the model
name) explicitly.

#### Dealing with Namespaces

If you have created namespaced routes (ie. with `name()` prefixes), `formWith` has a nifty shorthand for that too. If
your application has an admin namespace then

```php
@formWith(model: ['admin', $article])
```

will create a form that submits to the `/admin/articles` (or to `/admin/articles/42` in the case of an update). If you
have several levels of namespacing then the syntax is similar:

```php
@formWith(model: ['admin', 'management', $article] as $form)
```

### How do Forms with PATCH, PUT, or DELETE Methods Work?

The Laravel Support framework encourages RESTful design of your applications, which means you'll be making a lot
of "PATCH", "PUT", and "DELETE" requests (besides "GET" and "POST"). However, most browsers _don't support_ methods
other than "GET" and "POST" when it comes to submitting forms.

Laravel works around this issue by emulating other methods over POST with a hidden input named `"_method"`, which is set
to reflect the desired method:

```php
@formWith(url: $searchPath, options: ['method' => "patch"])
```

Output:

```html
<form accept-charset="UTF-8" action="/search" method="post">
  <input name="_method" type="hidden" value="patch" />
  <input name="_token" type="hidden" value="..." />
  <!-- ... -->
</form>
```

When parsing POSTed data, Laravel will take into account the special `_method` parameter and act as if the HTTP method was
the one specified inside it ("PATCH" in this example).

When rendering a form, submission buttons can override the declared `method` attribute through the `formmethod:`
keyword:

```php
@formWith(url: "/posts/1", options: ['method' => 'patch'] as $form)
  @button($form, "Delete", ['formmethod' => 'delete', 'data' => ['confirm' => 'Are you sure?']])
  @button($form, "Update")
@endBlock
```

Similar to `<form>` elements, most browsers _don't support_ overriding form methods declared through [formmethod]
[] other than "GET" and "POST".

Laravel Support works around this issue by emulating other methods over POST through a combination of [formmethod][],
[value][button-value], and [name][button-name] attributes:

```html
<form accept-charset="UTF-8" action="/posts/1" method="post">
  <input name="_method" type="hidden" value="patch" />
  <input name="_token" type="hidden" value="..." />
  <!-- ... -->

  <button type="submit" formmethod="post" name="_method" value="delete" data-confirm="Are you sure?">Delete</button>
  <button type="submit" name="button">Update</button>
</form>
```

IMPORTANT: In Laravel Support, all forms using `formWith` implement `remote => true` by default. These forms will
submit data using an XHR (Ajax) request. To disable this include `local => true`.

[formmethod]: https://developer.mozilla.org/en-US/docs/Web/HTML/Element/button#attr-formmethod
[button-name]: https://developer.mozilla.org/en-US/docs/Web/HTML/Element/button#attr-name
[button-value]: https://developer.mozilla.org/en-US/docs/Web/HTML/Element/button#attr-value

## Making Select Boxes with Ease

Select boxes in HTML require a significant amount of markup - one `<option>` element for each option to choose from. So
Laravel Support provides helper methods to reduce this burden.

For example, let's say we have a list of cities for the user to choose from. We can use the `select` helper like so:

```php
@select($form, 'city', ["Berlin", "Chicago", "Madrid"])
```

Output:

```html
<select name="city" id="city">
  <option value="Berlin">Berlin</option>
  <option value="Chicago">Chicago</option>
  <option value="Madrid">Madrid</option>
</select>
```

We can also designate `<option>` values that differ from their labels:

```php
@select($form, 'city', [["Berlin", "BE"], ["Chicago", "CHI"], ["Madrid", "MD"]])
```

Output:

```html
<select name="city" id="city">
  <option value="BE">Berlin</option>
  <option value="CHI">Chicago</option>
  <option value="MD">Madrid</option>
</select>
```

This way, the user will see the full city name, but `request('city')` will be one of `"BE"`, `"CHI"`, or `"MD"`.

Lastly, we can specify a default choice for the select box with the `selected` option:

```php
@select($form, 'city', [["Berlin", "BE"], ["Chicago", "CHI"], ["Madrid", "MD"]], ['selected' => "CHI"])
```

Output:

```html
<select name="city" id="city">
  <option value="BE">Berlin</option>
  <option value="CHI" selected="selected">Chicago</option>
  <option value="MD">Madrid</option>
</select>
```

### Option Groups

In some cases we may want to improve the user experience by grouping related options together. We can do so by passing a
`array` to `select`:

```php
@select($form, 'city', [
    "Europe" => [ ["Berlin", "BE"], ["Madrid", "MD"] ],
    "North America" => [ ["Chicago", "CHI"] ],
], ['selected' => "CHI"])
```

Output:

```html
<select name="city" id="city">
  <optgroup label="Europe">
    <option value="BE">Berlin</option>
    <option value="MD">Madrid</option>
  </optgroup>
  <optgroup label="North America">
    <option value="CHI" selected="selected">Chicago</option>
  </optgroup>
</select>
```

### Select Boxes and Model Objects

Like other form controls, a select box can be bound to a model attribute. For example, if we have a `$person` model
object like:

```php
$person = new Person(['city' => "MD"])
```

The following form:

```php
@formWith(model: $person as $form)
  @select($form, 'city', [["Berlin", "BE"], ["Chicago", "CHI"], ["Madrid", "MD"]])
@endBlock
```

Outputs a select box like:

```html
<select name="person[city]" id="person_city">
  <option value="BE">Berlin</option>
  <option value="CHI">Chicago</option>
  <option value="MD" selected="selected">Madrid</option>
</select>
```

Notice that the appropriate option was automatically marked `selected="selected"`. Since this select box was bound to a
model, we didn't need to specify a `selected` argument!

### Time Zone Select

To leverage time zone support in Laravel, you have to ask your users what time zone they are in. Doing so would require
generating select options from a list of pre-defined timezones, but you can simply use the `timeZoneSelect` helper
that already wraps this:

```php
@timeZoneSelect($form, 'time_zone')
```

## Choices from a Collection of Arbitrary Objects

Sometimes, we want to generate a set of choices from a collection of arbitrary objects. For example, if we have a `City`
model and corresponding `belongsTo(City::class)` relationship:

```php
class City extends Model {}

class Person extends Model {
    public function city() {
        return $this->belongsTo(City::class);
    }
}
```

```php
City::orderBy('name')->get()->map(function ($c) { return [$c->name, $c->id]; });
# => [["Berlin", 3], ["Chicago", 1], ["Madrid", 2]]
```

Then we can allow the user to choose a city from the database with the following form:

```php
@formWith(model: $person as $form)
  @select($form, 'city_id', City::orderBy('name')->get()->map(function ($c) { return [$c->name, $c->id]; }))
@endBlock
```

NOTE: When rendering a field for a `belongsTo` association, you must specify the name of the foreign key (`city_id` in
the above example), rather than the name of the association itself.

However, Rails provides helpers that generate choices from a collection without having to explicitly iterate over it.
These helpers determine the value and text label of each choice by calling specified methods on each object in the
collection.

### The `collectionSelect` Helper

To generate a select box, we can use `collectionSelect`:

```php
@collectionSelect($form, 'city_id', City::orderBy('name'), 'id', 'name')
```

Output:

```html
<select name="person[city_id]" id="person_city_id">
  <option value="3">Berlin</option>
  <option value="1">Chicago</option>
  <option value="2">Madrid</option>
</select>
```

NOTE: With `collectionSelect` we specify the value method first (`id` in the example above), and the text label
method second (`name` in the example above).  This is opposite of the order used when specifying choices for the
`select` helper, where the text label comes first and the value second.

The collection passed in can be a `Collection`, a query `Builder`, or an array.

### The `collectionRadioButtons` Helper

To generate a set of radio buttons, we can use `collectionRadioButtons`:

```php
@collectionRadioButtons($form, 'city_id', City::orderBy('name'), 'id', 'name')
```

Output:

```html
<input type="radio" name="person[city_id]" value="3" id="person_city_id_3">
<label for="person_city_id_3">Berlin</label>

<input type="radio" name="person[city_id]" value="1" id="person_city_id_1">
<label for="person_city_id_1">Chicago</label>

<input type="radio" name="person[city_id]" value="2" id="person_city_id_2">
<label for="person_city_id_2">Madrid</label>
```

### The `collectionCheckBoxes` Helper

To generate a set of check boxes — for example, to support a `HasMany` association — we can use
`collectionCheckBoxes`:


```php
@collectionCheckBoxes($form, 'interest_ids', Interest::orderBy('name'), 'id', 'name')
```

Output:

```html
<input type="checkbox" name="person[interest_id][]" value="3" id="person_interest_id_3">
<label for="person_interest_id_3">Engineering</label>

<input type="checkbox" name="person[interest_id][]" value="4" id="person_interest_id_4">
<label for="person_interest_id_4">Math</label>

<input type="checkbox" name="person[interest_id][]" value="1" id="person_interest_id_1">
<label for="person_interest_id_1">Science</label>

<input type="checkbox" name="person[interest_id][]" value="2" id="person_interest_id_2">
<label for="person_interest_id_2">Technology</label>
```

## Uploading Files

A common task is uploading some sort of file, whether it's a picture of a person or a CSV file containing data to
process. File upload fields can be rendered with the `fileField`.

```php
@formWith(model: $person as $form)
  @fileField(form, 'picture')
@endBlock
```

The most important thing to remember with file uploads is that the rendered form's `enctype` attribute **must** be set
to "multipart/form-data". This is done automatically if you use a `fileField` inside a `formWith`. You can also set the
attribute manually:

```php
@formWith(url: '/uploads', options: ['multipart' => true] as $form)
  @fileField($form, 'picture')
@endBlock
```

Note that, in accordance with `formWith` conventions, the field names in the two forms above will also differ.  That
is, the field name in the first form will be `person[picture]` (accessible via `request('person')['picture']`), and the
field name in the second form will be just `picture` (accessible via `request('person')`).

### What Gets Uploaded

The object in the `request()` input is an instance of `Illuminate\Http\UploadedFile`. The following snippet saves the
uploaded file in `storage/app/public/uploads` (by default) under the same name as the original file, which is generally
not a good idea.

```php
public function upload() {
  $uploadedFile = request('picture');
  $uploadedFile->storeAs('uploads', $uploadedFile->getClientOriginalName(), 'public');
}
```

Once a file has been uploaded, there are a multitude of potential tasks, ranging from where to store the files (on Disk,
Amazon S3, etc), associating them with models, resizing image files, and generating thumbnails, etc.

## Customizing Form Builders

The object yielded by `formWith` and `fieldsFor` is an instance of `SilvertipSoftware\LaravelSupport\BladeFormBuilder`.
Form builders encapsulate the notion of displaying form elements for a single object. While you can write helpers for
your forms in the usual way, you can also create a subclass of `FormBuilder`, and add the helpers there. For example,

```php
@formWith(model: $person as $form)
    @label($form, 'first_name')
    @textField($form, 'first_name')
@endBlock
```

can be replaced with

```php
@formWith(model: $person, options: ['builder' => LabellingFormBuilder::class] as $form)
  @textField($form, 'first_name')
@endBlock
```

by defining a `LabellingFormBuilder` class similar to the following:

```php
class LabellingFormBuilder extends FormBuilder
    public function textField($attribute, $options = []) {
        return new HtmlString($this->label($attribute) . parent::textField($attribute, $options));
    }
}
```

If you reuse this frequently you could define a `labeledFormWith` directive that automatically applies the
`builder => LabellingFormBuilder` option.


## Understanding Parameter Naming Conventions

Values from forms can be at the top level of the `request()` input array or nested in another array. For example, in a
standard `create` action for a Person model, `request('person')` would usually be an array of all the attributes for
the person to create. The `request()` array can also contain arrays, arrays of arrays, and so on.

Fundamentally HTML forms don't know about any sort of structured data, all they generate is name-value pairs, where
pairs are just plain strings. The arrays and hashes you see in your application are the result of some parameter naming
conventions that Laravel and Laravel Support uses.

### Basic Structures

The two basic structures are arrays and hashes. Hashes mirror the syntax used for accessing the value in `params`. For
example, if a form contains:

```html
<input id="person_name" name="person[name]" type="text" value="Henry"/>
```

the `request()` input will contain

```php
['person' => ['name' => 'Henry']]
```

and `request('person')['name']` will retrieve the submitted value in the controller.

Hashes can be nested as many levels as required, for example:

```html
<input id="person_address_city" name="person[address][city]" type="text" value="New York"/>
```

will result in the `request()` hash being

```php
['person' => ['address' => ['city' => 'New York']]]
```

Normally Laravel ignores duplicate parameter names. If the parameter name ends with an empty set of square brackets `
[]` then they will be accumulated in an array. If you wanted users to be able to input multiple phone numbers, you
could place this in the form:

```html
<input name="person[phone_number][]" type="text"/>
<input name="person[phone_number][]" type="text"/>
<input name="person[phone_number][]" type="text"/>
```

This would result in `request('person')['phone_number']` being an array containing the inputted phone numbers.

### Combining Them

We can mix and match these two concepts. One element of a hash might be an array as in the previous example, or you can
have an array of hashes. For example, a form might let you create any number of addresses by repeating the following
form fragment

```html
<input name="person[addresses][][line1]" type="text"/>
<input name="person[addresses][][line2]" type="text"/>
<input name="person[addresses][][city]" type="text"/>
<input name="person[addresses][][line1]" type="text"/>
<input name="person[addresses][][line2]" type="text"/>
<input name="person[addresses][][city]" type="text"/>
```

This would result in `request('person')['addresses']` being an array of hashes with keys `line1`, `line2`, and `city`.

There's a restriction, however: while hashes can be nested arbitrarily, only one level of "arrayness" is allowed. Arrays
can usually be replaced by hashes; for example, instead of having an array of model objects, one can have a hash of
model objects keyed by their id, an array index, or some other parameter.

WARNING: Array parameters do not play well with the `checkBox` helper. According to the HTML specification unchecked
checkboxes submit no value. However it is often convenient for a checkbox to always submit a value. The `checkBox`
helper fakes this by creating an auxiliary hidden input with the same name. If the checkbox is unchecked only the
hidden input is submitted and if it is checked then both are submitted but the value submitted by the checkbox takes
precedence.

### The `fieldsFor` Helper `index` Option

Let's say we want to render a form with a set of fields for each of a person's addresses. The [`fieldsFor`][] helper
with its `index` option can assist:

```php
@formWith(model: $person as $personForm)
  @textField($personForm, 'name')
  @foreach ($person->addresses as $address)
    @fieldsFor($personForm, 'address', ['index' => $address->id] as $addressForm)
      @textField($addressForm, 'city')
    @endBlock
  @endBlock
@endBlock
```

Assuming the person has two addresses with IDs 23 and 45, the above form would
render output similar to:

```html
<form accept-charset="UTF-8" action="/people/1" method="post">
  <input name="_method" type="hidden" value="patch" />
  <input id="person_name" name="person[name]" type="text" />
  <input id="person_address_23_city" name="person[address][23][city]" type="text" />
  <input id="person_address_45_city" name="person[address][45][city]" type="text" />
</form>
```

Which will result in a `request()` hash that looks like:

```php
[
  "person" => [
    "name" => "Bob",
    "address" => [
      "23" => [
        "city" => "Paris"
      ],
      "45" => [
        "city" => "London"
      ]
    ]
  ]
]
```

All of the form inputs map to the `"person"` hash because we called `fieldsFor`
on the `$personForm` form builder. By specifying an `index` option, we mapped
the address inputs to `person[address][$address->id][city]` instead of
`person[address][city]`. Thus we are able to determine which Address records
should be modified when processing the `request()` hash.

You can pass other numbers or strings of significance via the `index` option.
You can even pass `null`, which will produce an array parameter.

To create more intricate nestings, you can specify the leading portion of the
input name explicitly. For example:

```php
@fieldsFor('person[address][primary]', $address, ['index' => $address->id] as $addressForm)
  @textField($addressForm, 'city')
@endBlock
```

will create inputs like:

```html
<input id="person_address_primary_23_city" name="person[address][primary][23][city]" type="text" value="Paris" />
```

You can also pass an `index` option directly to helpers such as `textField`, but it is usually less repetitive to
specify this at the form builder level than on individual input fields.

Speaking generally, the final input name will be a concatenation of the name given to `fieldsFor` / `formWith`, the
`index` option value, and the name of the attribute.

Lastly, as a shortcut, instead of specifying an ID for `index` (e.g. `index => $address->id`), you can append `"[]"` to
the given name. For example:

```php
@fieldsFor('person[address][primary][]', $address as $addressForm)
  @textField($addressForm, 'city')
@endBlock
```

produces exactly the same output as our original example. This requires `$address` to implement a `toParam()` method,
which returns an appropriate index. `Model` does implement this, and returns the model's key attribute
(typically `id`), so this pattern should also work out of the box.

## Forms to External Resources

Laravel Support's form helpers can also be used to build a form for posting data to an external resource. However, at
times it can be necessary to set an `_token` for the resource; this can be done by passing an
`csrf_token: 'your_external_token'` parameter to the `formWith` options:

```php
@formWith(url: 'http://farfar.away/form', options: ['csrf_token' => 'external_token'] as $form)
  Form contents
@endBlock
```

Sometimes when submitting data to an external resource, like a payment gateway, the fields that can be used in the form
are limited by an external API and it may be undesirable to generate an `_token`. To not send a token, simply pass
`false` to the `csrf_token` option:

```php
@formWith(url: 'http://farfar.away/form', options: ['csrf_token' => false] as $form)
  Form contents
@endBlock
```

## Building Complex Forms

Many apps grow beyond simple forms editing a single object. For example, when creating a `Person` you might want to
allow the user to (on the same form) create multiple address records (home, work, etc.). When later editing that person
the user should be able to add, remove, or amend addresses as necessary.

### Configuring the Model

Laravel Support's `Model` provides model level support via the `acceptsNestedAttributesFor` functionality:

```php
class Person extends Model {
  protected static function bootTraits() {
    parent::bootTraits();
    static::addNestedAttribute('addresses');
  }

  public function addresses() {
    return $this->hasMany(Address::class);
  }
}

class Address extends Model
  public function person() {
    return $this->belongsTo(Person::class);
  }
}
```

This creates an `setAddressesAttributes()` magic method on `Person` that allows you to create, update, and
(optionally) destroy addresses.

### Nested Forms

The following form allows a user to create a `Person` and its associated addresses.

```php
@formWith(model: $person as $form)
  Addresses:
  <ul>
    @fieldsFor($form, 'addresses' as $addressesForm)
      <li>
        @label($addressesForm, 'kind')
        @textField($addressesForm, 'kind')

        @label($addressesForm, 'street')
        @textField($addressesForm, 'street')
        ...
      </li>
    @endBlock
  </ul>
@endBlock
```

When an association accepts nested attributes `fieldsFor` renders its block once for every element of the association.
In particular, if a person has no addresses it renders nothing. A common pattern is for the controller to build one or
more empty children so that at least one set of fields is shown to the user. The example below would result in 2 sets
of address fields being rendered on the new person form.

```php
public function create() {
  $this->person = new Person();
  $this->person->setRelation('addresses', collect([
    new Address(),
    new Address(),
  ]));
}
```

The `fieldsFor` yields a form builder. The parameters' name will be what
`acceptsNestedAttributesFor` expects. For example, when creating a user with
2 addresses, the submitted parameters would look like:

```php
[
  'person' => [
    'name' => 'John Doe',
    'addresses_attributes' => [
      '0' => [
        'kind' => 'Home',
        'street' => '221b Baker Street'
      ],
      '1' => [
        'kind' => 'Office',
        'street' => '31 Spooner Street'
      ]
    ]
  ]
]
```

The keys of the `addresses_attributes` hash are unimportant, they need merely be different for each address.

If the associated object is already saved, `fieldsFor` autogenerates a hidden input with the `id` of the saved record.
You can disable this by passing `include_id => false` to `fieldsFor`.

### The Controller

You need to declare the permitted parameters in the controller before you pass them to the model:

```php
public function store() {
  $this->person = new Person($this->personParams());
  // ...
}

private function personParams() {
  $this->params()
    ->require('person')
    ->permit(['name', 'addresses_attributes' => ['id', 'kind', 'street']]);
}
```

### Removing Objects

You can allow users to delete associated objects by passing `allow_destroy => true` to `addNestedAttribute`

```php
class Person extends Model {
  protected static function bootTraits() {
    parent::bootTraits();
    static::addNestedAttribute('addresses', ['allow_destroy' => true]);
  }
  //...
}
```

If the hash of attributes for an object contains the key `_destroy` with a value that evaluates to `true` (e.g. 1, '1')
then the object will be destroyed. This form allows users to remove addresses:

```php
@formWith(model: $person as $form)
  Addresses:
  <ul>
    @fieldsFor($form, 'addresses' as $addressesForm)
      <li>
        @checkBox($addressesForm, '_destroy')
        @label($addressesForm, 'kind')
        @textField($addressesForm, 'kind')
        ...
      </li>
    @endBlock
  </ul>
@endBlock
```

Don't forget to update the permitted params in your controller to also include
the `_destroy` field:

```php
private personParams() {
  $this->params()
    ->require('person')
    ->permit(['name', 'addresses_attributes' => ['id', 'kind', 'street', '_destroy']]);
}
```

### Preventing Empty Records

**TODO**

It is often useful to ignore sets of fields that the user has not filled in. You can control this by passing a
`reject_if` option to `addNestedAttribute`. This function will be called with each hash of attributes submitted by the
form. If the proc returns `true` then Laravel Support will not build an associated object for that hash. The example
below only tries to build an address if the `kind` attribute is set.

```php
class Person extends Model {
  protected static function bootTraits() {
    parent::bootTraits();
    static::addNestedAttribute('addresses', [
      'reject_if' => fn($attrs) => empty(Arr::get($attrs, 'kind'))
      }
    ]);
  }

  public function addresses() {
    return $this->hasMany(Address::class);
  }
}
```

As a convenience you can instead pass the string `all_blank` which will create a proc that will reject records where all
the attributes are blank excluding any value for `_destroy`.

### Adding Fields on the Fly

Rather than rendering multiple sets of fields ahead of time you may wish to add them only when a user clicks on an "Add
new address" button. Laravel Support does not provide any built-in support for this. When generating new sets of fields
you must ensure the key of the associated array is unique - the current JavaScript date (milliseconds since the [epoch]
(https://en.wikipedia.org/wiki/Unix_time)) is a common choice.

## Using Tag Helpers without a Form Builder

In case you need to render form fields outside of the context of a form builder, Laravel Support provides tag helpers
for common form elements. For example, `checkBoxTag`:

```php
@checkBoxTag('accept')
```

Output:

```html
<input type="checkbox" name="accept" id="accept" value="1" />
```

Generally, these helpers have the same name as their form builder counterparts plus a `Tag` suffix.

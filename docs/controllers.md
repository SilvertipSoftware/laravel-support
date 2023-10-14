# Controller Improvements

## Controller Naming Convention

The naming convention of controllers favors pluralization of the last
word in the controller's name, although it is not strictly required
(e.g. `ApplicationController`). For example, `ClientsController` is
preferable to `ClientController`, `SiteAdminsController` is
preferable to `SiteAdminController` or `SitesAdminsController`, and
so on.

NOTE: The controller naming convention differs from the naming
convention of models, which are expected to be named in singular
form.

## Parameters

### Strong Parameters

With strong parameters, request input parameters can be prevented from
being used in Model mass assignments until they have been permitted.
This means that you can make a conscious decision about which
attributes to permit for mass update. This is a security measure to
help prevent accidentally allowing users to update sensitive model
attributes, and is an alternative/supplement to guarded attributes,
which can sometimes be annoying to deal with in backend,
non-user-based coding.

In addition, parameters can be marked as required and will flow through a
predefined exception flow that will result in a 400 Bad Request being
returned if not all required parameters are passed in.

```php
class PeopleController extends Controller

  /**
   *  This will pass with flying colors as long as there's a person key
   * in the parameters, otherwise it'll raise an exception, which will get
   * caught by the framework and turned into a 400 Bad Request error.
   */
  public function update() {
    $person = People::find(request('id'));
    $person->updateOrFail($this->personParams());
    return redirect()->path($person);
  }

  private function personParams() {
    /**
     *  Using a private method to encapsulate the permissible parameters
     * is just a good pattern since you'll be able to reuse the same
     * permit list between store and update. Also, you can specialize
     * this method with per-user checking of permissible attributes.
     */
    return $this->params()->require('person')->permit(['name', 'age']);
  }
}
```

#### Permitted Scalar Values

Calling `permit` like:

```php
$params->permit('id');
```

permits the specified key (`id`) for inclusion if it appears in `$params` and
it has a permitted scalar value associated. Otherwise, the key is going
to be filtered out, so arrays/hashes, or any other objects cannot be
injected.

The permitted scalar types are `string`, `int`, `float`, `bool`,
`UploadedFile`, and `null`.

To declare that the value in `$params` must be an array of permitted
scalar values, map the key to an empty array:

```php
$params->permit(['id' => []]);
```

Sometimes it is not possible or convenient to declare the valid keys of
a hash parameter or its internal structure. Just map to an instance of `AnyStructure`:

```php
$params->permit(['preferences' => new AnyStructure()]);
```

but be careful because this opens the door to arbitrary input. In this
case, `permit` ensures values in the returned structure are permitted
scalars and filters out anything else.

#### Nested Parameters

You can also use `permit` on nested parameters, like:

```php
$params->permit([
  'name',
  'emails' => [],
  'friends' => [
    [
      'name',
      'family' => ['name'],
      'hobbies' => []
    ]
  ]
])
```

This declaration permits the `name`, `emails`, and `friends`
attributes. It is expected that `emails` will be an array of permitted
scalar values, and that `friends` will be an array of resources with
specific attributes: they should have a `name` attribute (any
permitted scalar values allowed), a `hobbies` attribute as an array of
permitted scalar values, and a `family` attribute which is restricted
to having a `name` (any permitted scalar values allowed here, too).

#### More Examples

You may want to also use the permitted attributes in your `create`
action. This raises the problem that you can't use `require` on the
root key because, normally, it does not exist when calling `create`:

```php
/**
 * using `fetch` you can supply a default and use
 * the Strong Parameters API from there.
 */
$params->fetch('blog', [])->permit('title', 'author');
```

The model class trait `NestedAttributes` allows you to update and destroy
associated records. This is based on the `id` and `_destroy` parameters:

```php
// permit id and _destroy
$params->require('author')
  ->permit(['name', 'books_attributes' => [['title', 'id', '_destroy']]]);
```

Imagine a scenario where you have parameters representing a product
name, and a hash of arbitrary data associated with that product, and
you want to permit the product name attribute and also the whole
data hash:

```php
private function productParams()
  $this->params()->require('product')->permit(['name', 'data': new AnyStructure]);
end
```

#### Outside the Scope of Strong Parameters

The strong parameter API was designed with the most common use cases
in mind. It is not meant as a silver bullet to handle all of your
parameter filtering problems. However, you can easily mix the API with your
own code to adapt to your situation.

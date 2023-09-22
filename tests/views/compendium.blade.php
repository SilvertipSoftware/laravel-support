@tag('div')
@tag('br', ['clear' => 'left'])
@tag('input', ['value' => [123, 456]])
@tag('p', ['class' => 'open'], true)
    @tag('br')
@closeTag('p')

@fhFieldId('post', 'title')
@fhFieldName('post', 'title', ['subtitle'])

@contentTag('div', 'Some inline content?')

@contentTag('div', ['class' => 'nested-content'] as $_)
    single nested.
    @contentTag('div' as $_)
        double nested
    @endBlock
@endBlock

@contentTag('form', ['class' => 'nested-form', 'action' => 'http://www.example.com'] as $_)
    @fhLabel('post', 'title' as $_)
        <span>This is a label with content</span>
    @endBlock
    @textFieldTag('title', 'A Title', ['class' => 'error'])

    @numberFieldTag('qty', 5, ['min' => 0])

    @fhLabel('post', 'qty', ['class' => 'small_label'] as $_)
        This is a small label
    @endBlock
    @rangeFieldTag('qty', 5, ['range' => [0,100]])

    @hiddenFieldTag('_delete', 0)
    @passwordFieldTag()
    @passwordFieldTag('password_confirmation')
    @colorFieldTag('car')
    @emailFieldTag('username')
    @dateFieldTag('appt', '2023-02-15')
    @timeFieldTag('appt')
    @datetimeFieldTag('appt')
    @monthFieldTag('birthday')
    @weekFieldTag('quarter_end')
    @searchFieldTag('q')
    @urlFieldTag('homepage')

    @checkBoxTag('active')
    @checkBoxTag('preferred', 'preferred', true)
    @checkBoxTag('active', 1, false, ['class' => 'highlight'])

    @radioButtonTag('size', 'sm')
    @radioButtonTag('size', 'md', true)
    @radioButtonTag('size', 'lg', false, ['class' => 'highlight'])

    @buttonTag(null as $_)
        <b>Press me</b>
    @endBlock
    @buttonTag('Press here!', ['class' => 'cta-button'])
    @buttonTag('Play >', ['class' => 'escaping-btn'])

    @submitTag()
    @submitTag('Save it', ['class' => 'cta-button'])

    <select name="manual">
        @optionsForSelect(['<Denmark>', 'USA', 'Sweden'])
        @optionsForSelect(['Canada', 'Mexico'], ['disabled' => 'Mexico'])
        @weekdayOptionsForSelect(null, true)
    </select>

    @fhSelect('contract', 'type', ['Surface', 'Underground', 'Fly'], [], [])
    @fhSelect('contract', 'country', null, [], [] as $_)
        @optionsForSelect(['Canada', 'Spain', 'Mexico'], ['disabled' => 'Mexico'])
    @endBlock

    @fhCollectionSelect('contract', 'fave_post', $posts, 'id', 'title', [], ['class' => 'important'])
    @fhTimeZoneSelect('contract', 'timezone', ['Pacific/Galapagos'], ['model' => timezone_identifiers_list(DateTimeZone::PER_COUNTRY, 'EC')])
    @fhWeekdaySelect('contract', 'start_day')

    @fhFieldsFor('user', $posts[0], [] as $f)
        {{ $f->emailField('email', ['class' => 'important-input']) }}
        @fhCollectionRadioButtons('post', 'state', [['Draft'], ['Public'], ['Private']], 0, 0)
        @fhCollectionCheckBoxes('post', 'options', [[100, 'One'], [200, 'Two']], 0, 1, [], [])

        @fhCollectionCheckBoxes('post', 'options', [[1, 'one'], [2, 'two']], 0, 1, [], [] as $b)
            <div class="check-wrapper">{{ $b->checkBox() }}</div>
            @label($b)

            <br/>
        @endBlock

        @label($f, 'body', ['class' => 'its-a-label'] as $_)
            Hey, blockhead!
        @endBlock

        @button($f)
        @button($f, 'Create User')
        @button($f as $_)
            <b>Create</b>
        @endBlock
    @endBlock
@endBlock

@formWith(url: '/' as $blankForm)
  Form contents
@endBlock

@formWith(url: '/search', options: ['method' => 'get'] as $form)
  @label($form, 'query', 'Search for:')
  @textField($form, 'query')
  @submit($form, "Search")
@endBlock

@formWith(url: '/' as $form)
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
@endBlock

@php
  $posts[0]->body = 'My Body';
@endphp

@formWith(model: $posts[0] as $form)
  @textField($form, 'title')
  @textArea($form, 'body', ['size' => "60x10"])
  @submit($form)
@endBlock

<h2>FieldsFor</h2>

@formWith(model: $posts[0] as $personForm)
  @textField($personForm, 'name')
  @fhFieldsFor('contact_detail', $posts[0]->contact_detail as $contactDetailForm)
    @textField($contactDetailForm, 'phone_number')
  @endBlock
@endBlock
@formWith(model: $posts[0] as $personForm)
  @textField($personForm, 'name')
  @fieldsFor($personForm, 'contact_detail', $posts[0]->contact_detail as $contactDetailForm)
    @textField($contactDetailForm, 'phone_number')
  @endBlock
@endBlock


@formWith(model: ['admin', $posts[0]] as $form)
@endBlock

@formWith(url: "/posts/1", options: ['method' => 'patch'] as $form)
  @button($form, "Delete", ['formmethod' => 'delete', 'data' => ['confirm' => 'Are you sure?']])
  @button($form, "Update")
@endBlock

@formWith(url: '/' as $form)
  @select($form, 'city', [["Berlin", "BE"], ["Chicago", "CHI"], ["Madrid", "MD"]])
  @select($form, 'city', [["Berlin", "BE"], ["Chicago", "CHI"], ["Madrid", "MD"]], ['selected' => "CHI"])
  @select($form, 'city', [
    "Europe" => [ ["Berlin", "BE"], ["Madrid", "MD"] ],
    "North America" => [ ["Chicago", "CHI"] ],
  ], ['selected' => "CHI"])
@endBlock

@php
  $posts[0]->city = 'MD';
@endphp
@formWith(model: $posts[0] as $form)
  @select($form, 'city', [["Berlin", "BE"], ["Chicago", "CHI"], ["Madrid", "MD"]])
@endBlock

@php
  $cities = collect([["Berlin", 3], ["Chicago", 1], ["Madrid", 2]]);

  \Illuminate\Support\Facades\DB::table('cities')->insert(['id' => 1, 'name' => 'Chigaco']);
  \Illuminate\Support\Facades\DB::table('cities')->insert(['id' => 2, 'name' => 'Madrid']);
  \Illuminate\Support\Facades\DB::table('cities')->insert(['id' => 3, 'name' => 'Berlin']);
@endphp

<h2>--- Collection Helpers on Builder ---</h2>

@formWith(model: $posts[0] as $form)
  @select($form, 'city_id', $cities)
  @collectionSelect($form, 'city_id', \Illuminate\Support\Facades\DB::table('cities')->orderBy('name'), 'id', 'name')
  @collectionRadioButtons($form, 'city_id', \Illuminate\Support\Facades\DB::table('cities')->orderBy('name'), 'id', 'name')
  @collectionRadioButtons($form, 'city_id', \Illuminate\Support\Facades\DB::table('cities')->orderBy('name'), 'id', 'name' as $b)
    <div>@label($b)</div><div>{{ $b->radioButton() }}</div>
  @endBlock

  @collectionCheckBoxes($form, 'city_ids', \Illuminate\Support\Facades\DB::table('cities')->orderBy('name'), 'id', 'name')
  @collectionCheckBoxes($form, 'city_ids', \Illuminate\Support\Facades\DB::table('cities')->orderBy('name'), 'id', 'name' as $b)
    <div>{{ $b->checkBox() }}</div>
  @endBlock
@endBlock

@formWith(model: $posts[0] as $form)
  @fileField($form, 'picture')
@endBlock


@formWith(url: '/uploads', options: ['multipart' => true] as $form)
  @fileFieldTag('picture')
@endBlock

@formWith(model: $posts[0] as $personForm)
  @textField($personForm, 'name')
  @foreach ([(object)['id'=>23, 'city'=>'Paris'], (object)['id'=>45, 'city'=>'London']] as $address)
    @fieldsFor($personForm, 'address', ['index' => $address->id] as $addressForm)
      @textField($addressForm, 'city')
    @endBlock
  @endforeach
@endBlock

@formWith(model: $posts[0] as $personForm)
  @textField($personForm, 'name')
  @foreach ([(object)['id'=>23, 'city'=>'Paris'], (object)['id'=>45, 'city'=>'London']] as $address)
    @fhFieldsFor('person[address][primary]', $address, ['index' => $address->id] as $addressForm)
      @textField($addressForm, 'city')
    @endBlock
  @endforeach
@endBlock

@formWith(model: $posts[0] as $personForm)
  @textField($personForm, 'name')
  @foreach ([new \App\Models\Comment(['id'=>23, 'city'=>'Paris']), new \App\Models\Comment(['id'=>45, 'city'=>'London'])] as $address)
    @fhFieldsFor('person[address][primary][]', $address as $addressForm)
      @textField($addressForm, 'city')
    @endBlock
  @endforeach
@endBlock

@checkBoxTag('accept')

<div>End</div>
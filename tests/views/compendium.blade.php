@tag('div')
@tag('br', ['clear' => 'left'])
@tag('input', ['value' => [123, 456]])
@tag('p', ['class' => 'open'], true)
    @tag('br')
@closeTag('p')

@fieldId('post', 'title')
@fieldName('post', 'title', ['subtitle'])

@contentTag('div', 'Some inline content?')

@contentTag('div', ['class' => 'nested-content'] as $_)
    single nested.
    @contentTag('div' as $_)
        double nested
    @endContentTag
@endContentTag

@contentTag('form', ['class' => 'nested-form', 'action' => 'http://www.example.com'] as $_)
    @label('post', 'title' as $_)
        <span>This is a label with content</span>
    @endLabel
    @textFieldTag('title', 'A Title', ['class' => 'error'])

    @numberFieldTag('qty', 5, ['min' => 0])

    @label('post', 'qty', ['class' => 'small_label'] as $_)
        This is a small label
    @endLabel
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
    @endButtonTag
    @buttonTag('Press here!', ['class' => 'cta-button'])
    @buttonTag('Play >', ['class' => 'escaping-btn'])

    @submitTag()
    @submitTag('Save it', ['class' => 'cta-button'])

    <select name="manual">
        @optionsForSelect(['<Denmark>', 'USA', 'Sweden'])
        @optionsForSelect(['Canada', 'Mexico'], ['disabled' => 'Mexico'])
        @weekdayOptionsForSelect(null, true)
    </select>

    @select('contract', 'type', ['Surface', 'Underground', 'Fly'], [], [])
    @select('contract', 'country', null, [], [] as $_)
        @optionsForSelect(['Canada', 'Spain', 'Mexico'], ['disabled' => 'Mexico'])
    @endSelect

    @collectionSelect('contract', 'fave_post', $posts, 'id', 'title', [], ['class' => 'important'])
    @timeZoneSelect('contract', 'timezone', ['Pacific/Galapagos'], ['model' => timezone_identifiers_list(DateTimeZone::PER_COUNTRY, 'EC')])
    @weekdaySelect('contract', 'start_day')

    @fieldsFor('user', $posts[0], [] as $f)
        {{ $f->emailField('email', ['class' => 'important-input']) }}
        @collectionRadioButtons('post', 'state', [['Draft'], ['Public'], ['Private']], 0, 0)
        @collectionCheckBoxes('post', 'options', [[100, 'One'], [200, 'Two']], 0, 1, [], [])

        @collectionCheckBoxes('post', 'options', [[1, 'one'], [2, 'two']], 0, 1, [], [] as $b)
            <div class="check-wrapper">{{ $b->checkBox() }}</div>
            @bldLabel($b)

            <br/>
        @endCollectionCheckBoxes

        @bldLabel($f, 'body', ['class' => 'its-a-label'] as $_)
            Hey, blockhead!
        @endBldLabel

        @bldButton($f)
        @bldButton($f, 'Create User')
        @bldButton($f as $_)
            <b>Create</b>
        @endBldButton
    @endFieldsFor
@endContentTag

@formWith(url: '/' as $blankForm)
  Form contents
@endFormWith

@formWith(url: '/search', options: ['method' => 'get'] as $form)
  @bldLabel($form, 'query', 'Search for:')
  @bldTextField($form, 'query')
  @bldSubmit($form, "Search")
@endFormWith

@formWith(url: '/' as $form)
    @bldTextArea($form, 'message', ['size' => "70x5"])
    @bldHiddenField($form, 'parent_id', ['value' => "foo"])
    @bldPasswordField($form, 'password')
    @bldNumberField($form, 'price', ['in' => [1.0, 20.0], 'step' => 0.5])
    @bldRangeField($form, 'discount', ['in' => [1, 100]])
    @bldDateField($form, 'born_on')
    @bldTimeField($form, 'started_at')
    @bldDatetimeLocalField($form, 'graduation_day')
    @bldMonthField($form, 'birthday_month')
    @bldWeekField($form, 'birthday_week')
    @bldSearchField($form, 'name')
    @bldEmailField($form, 'address')
    @bldTelephoneField($form, 'phone')
    @bldUrlField($form, 'homepage')
    @bldColorField($form, 'favorite_color')
@endFormWith

@php
  $posts[0]->body = 'My Body';
@endphp

@formWith(model: $posts[0] as $form)
  @bldTextField($form, 'title')
  @bldTextArea($form, 'body', ['size' => "60x10"])
  @bldSubmit($form)
@endFormWith

@formWith(model: $posts[0] as $personForm)
  @bldTextField($personForm, 'name')
  @fieldsFor('contact_detail', $posts[0]->contact_detail as $contactDetailForm)
    @bldTextField($contactDetailForm, 'phone_number')
  @endFieldsFor
@endFormWith

@formWith(model: ['admin', $posts[0]] as $form)
@endFormWith

@formWith(url: "/posts/1", options: ['method' => 'patch'] as $form)
  @bldButton($form, "Delete", ['formmethod' => 'delete', 'data' => ['confirm' => 'Are you sure?']])
  @bldButton($form, "Update")
@endFormWith

@formWith(url: '/' as $form)
  @bldSelect($form, 'city', [["Berlin", "BE"], ["Chicago", "CHI"], ["Madrid", "MD"]])
  @bldSelect($form, 'city', [["Berlin", "BE"], ["Chicago", "CHI"], ["Madrid", "MD"]], ['selected' => "CHI"])
  @bldSelect($form, 'city', [
    "Europe" => [ ["Berlin", "BE"], ["Madrid", "MD"] ],
    "North America" => [ ["Chicago", "CHI"] ],
  ], ['selected' => "CHI"])
@endFormWith

@php
  $posts[0]->city = 'MD';
@endphp
@formWith(model: $posts[0] as $form)
  @bldSelect($form, 'city', [["Berlin", "BE"], ["Chicago", "CHI"], ["Madrid", "MD"]])
@endFormWith

@php
  $cities = collect([["Berlin", 3], ["Chicago", 1], ["Madrid", 2]]);

  \Illuminate\Support\Facades\DB::table('cities')->insert(['id' => 1, 'name' => 'Chigaco']);
  \Illuminate\Support\Facades\DB::table('cities')->insert(['id' => 2, 'name' => 'Madrid']);
  \Illuminate\Support\Facades\DB::table('cities')->insert(['id' => 3, 'name' => 'Berlin']);
@endphp
@formWith(model: $posts[0] as $form)
  @bldSelect($form, 'city_id', $cities)
  @bldCollectionSelect($form, 'city_id', \Illuminate\Support\Facades\DB::table('cities')->orderBy('name'), 'id', 'name')
  @bldCollectionRadioButtons($form, 'city_id', \Illuminate\Support\Facades\DB::table('cities')->orderBy('name'), 'id', 'name')
  @bldCollectionCheckBoxes($form, 'city_ids', \Illuminate\Support\Facades\DB::table('cities')->orderBy('name'), 'id', 'name')
@endFormWith

@formWith(model: $posts[0] as $form)
  @bldFileField($form, 'picture')
@endFormWith


@formWith(url: '/uploads', options: ['multipart' => true] as $form)
  @fileFieldTag('picture')
@endFormWith

@formWith(model: $posts[0] as $personForm)
  @bldTextField($personForm, 'name')
  @foreach ([(object)['id'=>23, 'city'=>'Paris'], (object)['id'=>45, 'city'=>'London']] as $address)
    @bldFieldsFor($personForm, 'address', ['index' => $address->id] as $addressForm)
      @bldTextField($addressForm, 'city')
    @endBldFieldsFor
  @endforeach
@endFormWith

@formWith(model: $posts[0] as $personForm)
  @bldTextField($personForm, 'name')
  @foreach ([(object)['id'=>23, 'city'=>'Paris'], (object)['id'=>45, 'city'=>'London']] as $address)
    @fieldsFor('person[address][primary]', $address, ['index' => $address->id] as $addressForm)
      @bldTextField($addressForm, 'city')
    @endFieldsFor
  @endforeach
@endFormWith

@formWith(model: $posts[0] as $personForm)
  @bldTextField($personForm, 'name')
  @foreach ([new \App\Models\Comment(['id'=>23, 'city'=>'Paris']), new \App\Models\Comment(['id'=>45, 'city'=>'London'])] as $address)
    @fieldsFor('person[address][primary][]', $address as $addressForm)
      @bldTextField($addressForm, 'city')
    @endFieldsFor
  @endforeach
@endFormWith

@checkBoxTag('accept')

<div>End</div>
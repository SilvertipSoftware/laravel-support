@tag('div')
@tag('br', ['clear' => 'left'])
@tag('input', ['value' => [123, 456]])
@tag('p', ['class' => 'open'], true)
    @tag('br')
@closeTag('p')

@fieldId('post', 'title')
@fieldName('post', 'title', ['subtitle'])

@contentTagInline('div', 'Some inline content')

@contentTag('div', ['class' => 'nested-content'])
    single nested.
    @contentTag('div')
        double nested
    @endContentTag
@endContentTag

@contentTag('form', ['class' => 'nested-form', 'action' => 'http://www.example.com'])
    @labelTag('title')
        <span>This is a label with content</span>
    @endLabelTag
    @textFieldTag('title', 'A Title', ['class' => 'error'])

    @labelTagInline('qty', 'Quantity')
    @numberFieldTag('qty', 5, ['min' => 0])

    @labelTag('qty', ['class' => 'small_label'])
        This is a small label
    @endLabelTag
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

    @buttonTag()
        <b>Press me</b>
    @endButtonTag
    @buttonTagInline('Press here', ['class' => 'cta-button'])

    @submitTag()
    @submitTag('Save it', ['class' => 'cta-button'])

    <select name="manual">
        @optionsForSelect(['<Denmark>', 'USA', 'Sweden'])
        @optionsForSelect(['Canada', 'Mexico'], ['disabled' => 'Mexico'])
        @weekdayOptionsForSelect(null, true)
    </select>

    @selectInline('contract', 'type', ['Surface', 'Underground', 'Fly'], [], [])
    @select('contract', 'country', null, [], [])
        @optionsForSelect(['Canada', 'Spain', 'Mexico'], ['disabled' => 'Mexico'])
    @endSelect

    @collectionSelect('contract', 'fave_post', $posts, 'id', 'title', [], ['class' => 'important'])
    @timeZoneSelect('contract', 'timezone', ['Pacific/Galapagos'], ['model' => timezone_identifiers_list(DateTimeZone::PER_COUNTRY, 'EC')])
    @weekdaySelect('contract', 'start_day')

    @fieldsFor('post', null, [] do |$f|)
        @contentTag('div')
            {{ 'Andre was there' }}
            {{ $f->hiddenField('secret') }}
        @endContentTag
    @endFieldsFor
@endContentTag

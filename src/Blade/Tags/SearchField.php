<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;

class SearchField extends TextField {

    public function render() {
        $options = $this->options;

        $autosave = Arr::get($options, 'autosave');
        if ($autosave) {
            if ($autosave === true) {
                $host = request()?->getHost() ?? '';
                $options['autosave'] = implode('.', array_reverse(explode('.', $host)));
            }
            $options['results'] = Arr::get($options, 'results', 10);
        }

        if (Arr::get($options, 'onsearch')) {
            if (!Arr::has($options, 'incremental')) {
                $options['incremental'] = true;
            }
        }

        $this->options = $options;
        return parent::render();
    }
}

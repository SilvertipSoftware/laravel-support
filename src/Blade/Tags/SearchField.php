<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

class SearchField extends TextField {

    public function render(): HtmlString {
        $options = $this->options;

        $autosave = Arr::get($options, 'autosave');
        if ($autosave) {
            if ($autosave === true) {
                $host = request()->getHost() ?: '';
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

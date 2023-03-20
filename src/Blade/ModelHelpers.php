<?php

namespace SilvertipSoftware\LaravelSupport\Blade;

use Illuminate\Support\Facades\Blade;

class ModelHelpers {

    public static function registerDirectives() {
        Blade::directive('humanize', function ($expression) {
            return "<?php echo SilvertipSoftware\LaravelSupport\Blade\ModelHelpers::humanize($expression); ?>";
        });
    }

    public static function humanize($modelOrClass, $attr = null, $options = []) {
        $clz = is_string($modelOrClass) ? $modelOrClass : get_class($modelOrClass);

        if ($attr == null) {
            return $clz::modelName()->human($options);
        }

        return $clz::humanAttributeName($attr, $options);
    }
}

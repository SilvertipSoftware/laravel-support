<?php

namespace App\Models;

use Illuminate\Support\Arr;
use SilvertipSoftware\LaravelSupport\Eloquent\FluentModel;
use SilvertipSoftware\LaravelSupport\Eloquent\Model;

class Car extends FluentModel {
}

class Comment extends Model {
    public function __construct($attrs = []) {
        $id = Arr::get($attrs, 'id');
        $name = Arr::get($attrs, 'id') === null
            ? "new " . strtolower(class_basename(static::class))
            : strtolower(class_basename(static::class)) . ' ' . $id;
        parent::__construct(array_merge(['name' => $name], $attrs));
    }

    public $relevances;

    public function setRelevancesAttributes($attrs) {
    }
}

class Continent extends FluentModel {
}

class Tag extends Model {
    public function __construct($attrs = []) {
        $id = Arr::get($attrs, 'id');
        $value = Arr::get($attrs, 'id') === null
            ? "new " . strtolower(class_basename(static::class))
            : strtolower(class_basename(static::class)) . ' ' . $id;
        parent::__construct(array_merge(['value' => $value], $attrs));
    }

    public $relevances;

    public function setRelevancesAttributes($attrs) {
    }
}

class Post extends Model {

    private $privateProperty = 'PRIVATE';

    // public function toParam(): int|string {
    //     return $this->id;
    // }

    public function setAuthorAttributes($attrs) {
    }

    public function setCommentsAttributes($attrs) {
    }

    public function setTagsAttributes($attrs) {
    }
}

class PostDelegate extends Post {
    public static function humanAttributeName(string $attr, array $opts = []): string {
        return 'Delegate ' . parent::humanAttributeName($attr, $opts);
    }
}

class PostDelegator extends Post {
    public function toModel(): Model|FluentModel {
        return new PostDelegate();
    }
}

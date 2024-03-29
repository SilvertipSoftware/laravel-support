<?php

namespace App\Models;

use SilvertipSoftware\LaravelSupport\Eloquent\Model;

class User extends Model {

    protected array $validationRules = [
        'name' => ['required']
    ];

    public static function i18nScope(): string {
        return 'security';
    }

    public function __construct($attributes = []) {
        $this->addAutosavedRelation('reviews');

        parent::__construct($attributes);
    }

    public function reviews() {
        return $this->hasMany(Review::class);
    }
}

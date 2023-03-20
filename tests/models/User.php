<?php

namespace App\Models;

use SilvertipSoftware\LaravelSupport\Eloquent\Model;

class User extends Model {

    protected $validationRules = [
        'name' => ['required']
    ];

    public static function i18nScope() {
        return 'security';
    }

    public function __construct($attributes = []) {
        $this->addAutosavedRelation('comments');

        parent::__construct($attributes);
    }

    public function comments() {
        return $this->hasMany(Comment::class);
    }
}

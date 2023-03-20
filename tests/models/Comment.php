<?php

namespace App\Models;

use SilvertipSoftware\LaravelSupport\Eloquent\Model;

class Comment extends Model {

    protected $validationRules = [
        'content' => ['required']
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}

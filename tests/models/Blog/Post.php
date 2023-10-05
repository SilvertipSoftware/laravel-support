<?php

namespace App\Models\Blog;

use Closure;
use SilvertipSoftware\LaravelSupport\Eloquent\Model;

class Post extends Model {

    protected static string|Closure|null $modelRelativeNamespace = 'App\Models\Blog';
}

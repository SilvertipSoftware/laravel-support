<?php

namespace SilvertipSoftware\LaravelSupport\Http;

class ResourceController extends Controller {
    use Concerns\LoadsResource,
        Concerns\Resourceful;
}

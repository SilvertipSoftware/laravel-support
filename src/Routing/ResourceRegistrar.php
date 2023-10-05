<?php

namespace SilvertipSoftware\LaravelSupport\Routing;

use Illuminate\Routing\ResourceRegistrar as BaseResourceRegistrar;

class ResourceRegistrar extends BaseResourceRegistrar {

    public function getResourceWildcard(mixed $value): string {
        return parent::getResourceWildcard($value) . '_id';
    }
}

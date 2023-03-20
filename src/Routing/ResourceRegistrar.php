<?php

namespace SilvertipSoftware\LaravelSupport\Routing;

use Illuminate\Routing\ResourceRegistrar as BaseResourceRegistrar;

class ResourceRegistrar extends BaseResourceRegistrar {

    public function getResourceWildcard($value) {
        return parent::getResourceWildcard($value) . '_id';
    }
}

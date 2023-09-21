<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Http\Concerns;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;

trait ConditionalGet {

    public function freshWhen($object, $lastModified = null) {
        $request = request();

        // @phpstan-ignore-next-line
        $request->setResponseFreshnessInfo([
            'last_modified' => $lastModified ?: $this->computeLastModifiedFrom($object)
        ]);
    }

    public function isStale($object, $lastModified = null) {
        $this->freshWhen($object, $lastModified);

        // @phpstan-ignore-next-line
        return !request()->isFresh();
    }

    protected function computeLastModifiedFrom($object) {
        $modified = null;

        if (is_object($object)) {
            $modified = $object->updated_at ?: null;

            if (!$modified && (method_exists($object, 'max') || $object instanceof Builder)) {
                $modified = $object->max('updated_at');
            }
        } elseif (is_array($object)) {
            $modified = array_reduce($object, function ($memo, $obj) {
                return max($memo, $this->computeLastModifiedFrom($obj));
            }, null);
        }

        if ($modified && !($modified instanceof Carbon)) {
            $modified = new Carbon($modified);
        }

        return $modified;
    }
}

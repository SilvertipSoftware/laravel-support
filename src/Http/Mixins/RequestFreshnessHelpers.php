<?php

namespace SilvertipSoftware\LaravelSupport\Http\Mixins;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Fluent;

class RequestFreshnessHelpers {

    public static function register() {
        Request::macro('setResponseFreshnessInfo', function (array $info) {
            // @phpstan-ignore-next-line
            $this->responseFreshnessInfo = new Fluent($info);
        });

        Request::macro('getResponseFreshnessInfo', function () {
            // @phpstan-ignore-next-line
            if (empty($this->responseFreshnessInfo)) {
                // @phpstan-ignore-next-line
                $this->setResponseFreshnessInfo([]);
            }

            // @phpstan-ignore-next-line
            return $this->responseFreshnessInfo;
        });

        Request::macro('isFresh', function () {
            // @phpstan-ignore-next-line
            $ifModifiedSince = $this->headers->get('if_modified_since');
            if (!$ifModifiedSince) {
                return false;
            }

            try {
                $ifModifiedSince = new Carbon($ifModifiedSince);
            } catch (Exception $ex) {
                return false;
            }

            // @phpstan-ignore-next-line
            return $this->notModified($ifModifiedSince, $this->getResponseFreshnessInfo()->last_modified);
        });

        Request::macro('addFreshnessHeaders', function ($response) {
            // @phpstan-ignore-next-line
            $responseInfo = $this->getResponseFreshnessInfo();

            if ($responseInfo->last_modified) {
                $response->header('Last-Modified', $responseInfo->last_modified->toRfc7231String());
                $response->setMaxAge(0);
                $response->headers->addCacheControlDirective('must-revalidate');
            }
        });

        Request::macro('notModified', function ($reqTime, $respTime) {
            return $respTime && $reqTime->gte($respTime);
        });
    }
}

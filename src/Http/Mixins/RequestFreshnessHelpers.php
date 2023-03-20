<?php

namespace SilvertipSoftware\LaravelSupport\Http\Mixins;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Fluent;

class RequestFreshnessHelpers {

    public static function register() {
        Request::macro('setResponseFreshnessInfo', function (array $info) {
            $this->responseFreshnessInfo = new Fluent($info);
        });

        Request::macro('getResponseFreshnessInfo', function () {
            if (empty($this->responseFreshnessInfo)) {
                $this->setResponseFreshnessInfo([]);
            }

            return $this->responseFreshnessInfo;
        });

        Request::macro('isFresh', function () {
            $responseInfo = $this->getResponseFreshnessInfo();
            $ifModifiedSince = $this->headers->get('if_modified_since');
            if (!$ifModifiedSince) {
                return false;
            }

            try {
                $ifModifiedSince = new Carbon($ifModifiedSince);
            } catch (Exception $ex) {
                return false;
            }

            return $this->notModified($ifModifiedSince, $responseInfo->last_modified);
        });

        Request::macro('addFreshnessHeaders', function ($response) {
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

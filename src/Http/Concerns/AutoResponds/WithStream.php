<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns\AutoResponds;

use Illuminate\Support\Facades\View;

trait WithStream {

    protected function createStreamResponse($status = 200) {
        View::share(get_object_vars($this));
        $streamView = $this->viewNameForRoute();
        if (View::exists($streamView)) {
            return $this->makeStreamResponseFrom(
                response(view($streamView)->render(), $status)
            );
        } else {
            $htmlView = $this->viewNameForRoute('html');

            return $this->makeHtmlResponseFrom(
                response(view($htmlView)->render(), $status)
            );
        }
    }

    protected function makeStreamResponseFrom($response) {
        return $response
            ->header('Content-Type', 'text/vnd.turbo-stream.html');
    }

    protected function mapRedirectForStream($response) {
        return $this->makeHtmlResponseFrom($response);
    }
}

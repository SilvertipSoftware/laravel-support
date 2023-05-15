<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns\AutoResponds;

use Illuminate\Support\Facades\View;

trait WithStream {

    protected function createStreamResponse($status = 200) {
        $streamView = $this->viewNameForRoute();
        $data = $this->dataForView();

        if (View::exists($streamView)) {
            return $this->makeStreamResponseFrom(
                response(view($streamView, $data)->render(), $status)
            );
        } else {
            $htmlView = $this->viewNameForRoute('html');

            return $this->makeHtmlResponseFrom(
                response(view($htmlView, $data)->render(), $status)
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

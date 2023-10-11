<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns\AutoResponds;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

trait WithStream {

    protected function createTurboStreamResponse(int $status = 200): Response {
        $streamView = $this->viewNameForRoute();
        $data = $this->dataForView();

        if (View::exists($streamView)) {
            return $this->makeTurboStreamResponseFrom(
                response(view($streamView, $data)->render(), $status)
            );
        } else {
            $htmlView = $this->viewNameForRoute('html');

            return $this->makeHtmlResponseFrom(
                response(view($htmlView, $data)->render(), $status)
            );
        }
    }

    protected function makeTurboStreamResponseFrom(Response $response): Response {
        return $response
            ->header('Content-Type', 'text/vnd.turbo-stream.html');
    }

    protected function mapRedirectForTurboStream(Response $response): Response {
        return $this->makeHtmlResponseFrom($response);
    }
}

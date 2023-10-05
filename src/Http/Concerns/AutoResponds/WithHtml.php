<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns\AutoResponds;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use InvalidArgumentException;

trait WithHtml {

    protected function createHtmlResponse(): Response {
        $viewName = $this->viewNameForRoute();

        if (!View::exists($viewName)) {
            throw new InvalidArgumentException("View [{$viewName}] not found.");
        }

        $data = $this->dataForView();

        return response()->view($viewName, $data);
    }

    protected function makeHtmlResponseFrom(Response $response): Response {
        return $response
            ->header('Content-Type', 'text/html');
    }
}

<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns\AutoResponds;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use InvalidArgumentException;

trait WithHtml {

    protected function createHtmlResponse() {
        $viewName = $this->viewNameForRoute();

        if (!View::exists($viewName)) {
            throw new InvalidArgumentException("View [{$viewName}] not found.");
        }

        $data = $this->dataForView();

        return view($viewName, $data);
    }

    protected function makeHtmlResponseFrom($response) {
        return $response
            ->header('Content-Type', 'text/html');
    }
}

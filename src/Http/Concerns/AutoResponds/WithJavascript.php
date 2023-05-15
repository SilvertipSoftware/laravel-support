<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns\AutoResponds;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

trait WithJavascript {

    protected $javascriptRedirectView = 'js_redirect';

    protected function createJsResponse($status = 200) {
        $data = $this->dataForView();

        $content = $this->wrapJavascriptViewContent($this->viewNameForRoute(), $data);

        return $this->makeJavascriptResponseFrom(response($content, $status));
    }

    protected function makeJavascriptResponseFrom($response) {
        return $response
            ->header('Content-Type', 'text/javascript')
            ->header('X-Xhr-Redirect', true);
    }

    protected function mapRedirectForJs($response) {
        $data = [
            'redirectToUrl' => $response->getTargetUrl()
        ];

        $content = $this->wrapJavascriptViewContent($this->javascriptRedirectView, $data);

        return $this->makeJavascriptResponseFrom(response($content, $response->status()));
    }

    protected function wrapJavascriptViewContent($viewName, $data = []) {
        return "(function() {\n"
            . view($viewName, $data)->render()
            . "\n})();";
    }
}

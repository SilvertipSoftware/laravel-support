<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns\AutoResponds;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

trait WithJavascript {

    protected $javascriptRedirectView = 'js_redirect';

    protected function createJsResponse($status = 200) {
        View::share(get_object_vars($this));
        $content = $this->wrapJavascriptViewContent($this->viewNameForRoute());

        return $this->makeJavascriptResponseFrom(response($content, $status));
    }

    protected function makeJavascriptResponseFrom($response) {
        return $response
            ->header('Content-Type', 'text/javascript')
            ->header('X-Xhr-Redirect', true);
    }

    protected function mapRedirectForJs($response) {

        $content = $this->wrapJavascriptViewContent($this->javascriptRedirectView, [
            'redirectToUrl' => $response->getTargetUrl()
        ]);

        return $this->makeJavascriptResponseFrom(response($content, $response->status()));
    }

    protected function wrapJavascriptViewContent($viewName, $data = []) {
        return "(function() {\n"
            . view($viewName, $data)->render()
            . "\n})();";
    }
}

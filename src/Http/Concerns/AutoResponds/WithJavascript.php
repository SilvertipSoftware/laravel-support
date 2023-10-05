<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns\AutoResponds;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

trait WithJavascript {

    protected string $javascriptRedirectView = 'js_redirect';

    protected function createJsResponse(int $status = 200): Response {
        $data = $this->dataForView();

        $content = $this->wrapJavascriptViewContent($this->viewNameForRoute(), $data);

        return $this->makeJavascriptResponseFrom(response($content, $status));
    }

    protected function makeJavascriptResponseFrom(Response $response): Response {
        return $response
            ->header('Content-Type', 'text/javascript');
    }

    protected function mapRedirectForJs(RedirectResponse $response): Response {
        $data = [
            'redirectToUrl' => $response->getTargetUrl()
        ];

        $content = $this->wrapJavascriptViewContent($this->javascriptRedirectView, $data);

        return $this->makeJavascriptResponseFrom(response($content, $response->status()));
    }

    /**
     * @param array<string,mixed> $data
     */
    protected function wrapJavascriptViewContent(string $viewName, array $data = []): string {
        return "(function() {\n"
            . view($viewName, $data)->render()
            . "\n})();";
    }
}

<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns\AutoResponds;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RuntimeException;

trait WithJson {

    protected function createJsonResponse() {
        $modelName = $this->getModelNameForResponse();

        return response()->json([
            $modelName => ($this->{$modelName} ?: null)
        ]);
    }

    protected function getModelNameForResponse() {
        $viewName = $this->viewNameForRoute('html');
        $actionName = array_pop($viewName);
        $model = array_pop($viewName);

        if (method_exists($this, 'getSubjectResourceTag')) {
            $model = $this->getSubjectResourceTag();
        }

        return ($actionName == 'index')
            ? Str::plural($model)
            : Str::singular($model);
    }

    protected function mapRedirectForJson($response) {
        return $response->setContent('');
    }
}

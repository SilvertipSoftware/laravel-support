<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns\AutoResponds;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

trait WithJson {

    protected function createJsonResponse(): Response {
        $modelName = $this->getModelNameForResponse();

        return response()->json([
            $modelName => ($this->{$modelName} ?: null)
        ]);
    }

    protected function getModelNameForResponse(): string {
        $actionName = Route::getCurrentRoute()->getActionName();
        $model = $this->getSubjectResourceTag();

        return ($actionName == 'index')
            ? Str::plural($model)
            : Str::singular($model);
    }

    protected function mapRedirectForJson(Response $response): Response {
        return $response->setContent('');
    }
}

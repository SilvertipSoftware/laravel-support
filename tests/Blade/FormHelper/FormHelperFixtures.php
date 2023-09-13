<?php

namespace Tests\Blade\FormHelper;

require_once __DIR__ . '/../../models/TestFormModels.php';

use App\Models\Car;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostDelegator;
use App\Models\Tag;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;

trait FormHelperFixtures {

    protected function createFixtures() {
        Route::resource('posts', '');
        Route::resource('posts.comments', '');

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::resource('posts', '');
            Route::resource('posts.comments', '');
        });
        Lang::setLocale('en');
        Lang::addLines(Arr::dot([
            'eloquent' => [
                'attributes' => [
                    'post' => [
                        'cost' => 'Total cost',
                    ],
                    'post/language' => [
                        'spanish' => 'Español',
                    ],
                ],
            ],
            'helpers' => [
                'label' => [
                    'post' => [
                        'body' => 'Write entire text here',
                        'color' => ['red' => 'Rojo'],
                        'comments' => [
                            'body' => 'Write body here'
                        ]
                    ],
                    'tag' => [
                        'value' => 'Tag'
                    ],
                    'post_delegate' => [
                        'title' => 'Delegate model_name title'
                    ]
                ],
            ],
        ]), 'label');
        Lang::addLines(Arr::dot([
            'eloquent' => [
                'attributes' => [
                    'post' => [
                        'cost' => 'Total cost',
                    ],
                    'post/cost' => [
                        'uk' => 'Pounds'
                    ],
                ],
            ],
            'helpers' => [
                'placeholder' => [
                    'post' => [
                        'title' => 'What is this about?',
                        'written_on' => [
                            'spanish' => 'Escrito en'
                        ],
                        'comments' => [
                            'body' => 'Write body here'
                        ]
                    ],
                    'post_delegator' => [
                        'title' => 'Delegate model_name title'
                    ],
                    'tag' => [
                        'value' => 'Tag'
                    ]
                ]
            ]
        ]), 'placeholder');

        $this->post = new Post([
            'id' => 123,
            'title' => 'Hello World',
            'author_name' => '',
            'body' => 'This is a post',
            'secret' => 1,
            'written_on' => '2004-06-15'
        ]);
        $this->post->exists = true;
        $this->postDelegator = new PostDelegator(['title' => 'Hello World']);

        $this->comment = new Comment();
        $this->post->comments = collect([$this->comment]);

        $this->post->tags = collect([new Tag()]);

        $this->car = new Car(['color' => '#000FFF']);

        static::setContextVariables([
            'car' => $this->car,
            'post' => $this->post,
            'post_delegator' => $this->postDelegator
        ]);
    }

    private function formText(
        $action = "/",
        $id = null,
        $htmlClass = null,
        $remote = null,
        $multipart = null,
        $method = null
    ) {
        $method = $method == 'get' ? 'get' : 'post';

        return '<form accept-charset="UTF-8"'
            . ($action ? (' action="' . $action . '"') : '')
            . ($multipart ? ' enctype="multipart/form-data"' : '')
            . ($remote ? ' data-remote="true"' : '')
            . ($htmlClass ? (' class="' . $htmlClass . '"') : '')
            . ($id ? (' id="' . $id . '"') : '')
            . ' method="' . $method . '">';
    }

    private function hiddenFields($options = []) {
        $method = Arr::get($options, 'method');
        $enforceUtf8 = Arr::get($options, 'enforce_utf8', true);

        $out = '';

        if ($enforceUtf8) {
            $out .= '<input name="utf8" type="hidden" value="&#x2713;" autocomplete="off" />';
        }

        if ($method && !in_array($method, ['get', 'post'])) {
            $out .= '<input name="_method" type="hidden" value="' . $method . '" autocomplete="off" />';
        }

        return $out;
    }

    private function wholeForm($action = '/', $id = null, $htmlClass = null, $options = [], $callback = null) {
        $content = $callback ? $callback() : '';

        $method = Arr::get($options, 'method');
        $remote = Arr::get($options, 'remote');
        $multipart = Arr::get($options, 'multipart');

        return $this->formText($action, $id, $htmlClass, $remote, $multipart, $method)
            . $this->hiddenFields(Arr::only($options, ['method', 'enforce_utf8']))
            . $content
            . '</form>';

    }
}

<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use SilvertipSoftware\LaravelSupport\Http\Controller;

class EyesController extends Controller {

    public function index() {
    }

    public function show($id) {
        $this->message = 'Showing model ' . $id;
    }

    public function edit($id) {
        $this->message = 'Editing model ' . $id;
    }

    public function store() {
        return redirect('/eyes/NEWID', 302);
    }
}

class TimesController extends Controller {

    public function stale() {
        if ($this->isStale(null, Carbon::now()->startOfDay())) {
            return 'fresh code content';
        }
    }

    public function fresh() {
        $this->freshWhen(null, Carbon::now()->startOfDay());
    }
}

class ConesController extends Controller {

    public function store() {
        $mode = request('case');
        $params = $this->conesParams($mode)->toArray();

        return json_encode($params);
    }

    protected function conesParams($mode) {
        switch ($mode) {
            case 'with_eye':
                return $this->params()
                    ->require('cone')
                    ->permit(['color', 'eye_id']);
            case 'nested':
                return $this->params()
                    ->require('cone')
                    ->permit([
                        'color',
                        'eye_attributes' => [
                            'side'
                        ]
                    ]);
            case 'arrays':
                return $this->params()
                    ->require('cone')
                    ->permit([
                        'array_attribute' => [
                            ['color']
                        ]
                    ]);
            default:
                return $this->params()
                    ->require('cone')
                    ->permit(['color']);
        }
    }
}

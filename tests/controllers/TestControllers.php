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

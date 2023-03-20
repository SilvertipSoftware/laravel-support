<?php

use App\Models\Guitar;

require_once __DIR__ . '/DatabaseTestCase.php';
require_once __DIR__ . '/../models/TestModels.php';

class EventsTest extends DatabaseTestCase {

    public function testEventsOnCreate() {
        $guitar = new Guitar(['name' => 'Fender']);
        $guitar->firedEvents = [];

        $guitar->saveOrFail();

        $events = [
            'validating', 'validated',
            'saving',
            'creating', 'created',
            'saved',
            'afterCommit',
            'afterSavingCommit'
        ];

        $this->assertTrue($guitar->exists);
        $this->assertEquals($events, $guitar->firedEvents);
    }

    public function testEventsOnCreateDBFailure() {
        $guitar = new Guitar(['name' => 'Fender', 'spurious_column' => '6-string']);
        $guitar->firedEvents = [];

        $this->assertFalse($guitar->save());

        $events = [
            'validating', 'validated',
            'saving',
            'creating',
            'afterRollback',
            'afterSavingRollback'
        ];

        $this->assertEquals($events, $guitar->firedEvents);
    }

    public function testEventsOnUpdate() {
        $guitar = Guitar::create(['name' => 'Fender']);
        $guitar->firedEvents = [];

        $guitar->name = 'Gibsons';
        $guitar->saveOrFail();

        $events = [
            'validating', 'validated',
            'saving',
            'updating', 'updated',
            'saved',
            'afterCommit',
            'afterSavingCommit'
        ];

        $this->assertEquals($events, $guitar->firedEvents);
    }

    public function testEventsOnUpdateDBFailure() {
        $guitar = Guitar::create(['name' => 'Fender']);
        $guitar->firedEvents = [];

        $guitar->spurious_column = '6-string';
        $this->assertFalse($guitar->save());

        $events = [
            'validating', 'validated',
            'saving',
            'updating',
            'afterRollback',
            'afterSavingRollback'
        ];

        $this->assertEquals($events, $guitar->firedEvents);
    }

    public function testEventsOnDelete() {
        $guitar = Guitar::create(['name' => 'Fender']);
        $guitar->firedEvents = [];

        $guitar->deleteOrFail();

        $events = [
            'deleting', 'deleted',
            'afterCommit',
            'afterDeletingCommit'
        ];

        $this->assertFalse($guitar->exists);
        $this->assertEquals($events, $guitar->firedEvents);
    }
}

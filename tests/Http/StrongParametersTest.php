<?php

use Carbon\Carbon;

require_once __DIR__ . '/ControllerTestCase.php';


class StrongParametersTest extends ControllerTestCase {

    public function setUp(): void {
        parent::setUp();
    }

    public function testRequireRaisesWhenNotPresent() {
        $this->postJson('/cones', [])
            ->assertStatus(500);

        $this->postJson('/cones', ['cone' => null])
            ->assertStatus(500);

        $this->postJson('/cones', ['cone' => []])
            ->assertStatus(500);
    }

    public function testPermitWithNonePresent() {
        $data = [
            'cone' => [
                'other' => 'TEST'
            ]
        ];

        $this->postJson('/cones', $data)
            ->assertJson([]);
    }

    public function testPermitLetsKnownThrough() {
        $data = [
            'cone' => [
                'color' => 'red'
            ]
        ];

        $this->postJson('/cones', $data)
            ->assertJsonStructure(['color']);
    }

    public function testPermitMultiple() {
        $data = [
            'case' => 'with_eye',
            'cone' => [
                'color' => 'red',
                'eye_id' => 7
            ]
        ];

        $this->postJson('/cones', $data)
            ->assertJsonStructure(['color', 'eye_id']);
    }

    public function testPermitIgnoresMissing() {
        $data = [
            'case' => 'with_eye',
            'cone' => [
                'color' => 'red'
            ]
        ];

        $this->postJson('/cones', $data)
            ->assertJsonStructure(['color'])
            ->assertJsonMissing(['eye_id']);
    }

    public function testPermitFiltersUnkownScalars() {
        $data = [
            'cone' => [
                'color' => 'red',
                'eye_id' => 7
            ]
        ];

        $this->postJson('/cones', $data)
            ->assertJsonStructure(['color'])
            ->assertJsonMissing(['eye_id']);
    }

    public function testPermitFiltersUnkownArrays() {
        $data = [
            'cone' => [
                'color' => 'red',
                'other_attributes' => ['name' => 'TEST']
            ]
        ];

        $this->postJson('/cones', $data)
            ->assertJsonStructure(['color'])
            ->assertJsonMissing(['other_attributes']);
    }

    public function testPermitNestedStructures() {
        $data = [
            'case' => 'nested',
            'cone' => [
                'color' => 'red',
                'eye_attributes' => [
                    'side' => 'left',
                    'other_attribute' => 'TEST'
                ]
            ]
        ];

        $this->postJson('/cones', $data)
            ->assertJsonStructure(['color', 'eye_attributes' => ['side']])
            ->assertJsonMissing(['eye_attributes' => ['other_attribute']]);
    }

    public function testPermitArrays() {
        $data = [
            'case' => 'arrays',
            'cone' => [
                'array_attribute' => [
                    ['color' => 'red'],
                    ['color' => 'green'],
                    ['color' => 'blue', 'other' => 7]
                ]
            ]
        ];

        $this->postJson('/cones', $data)
            ->assertJsonStructure([
                'array_attribute' => [
                    '*' => ['color']
                ]
            ]);
    }
}

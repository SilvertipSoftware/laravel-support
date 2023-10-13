<?php

namespace Tests\Libs;

require_once __DIR__ . '/../models/TestModels.php';

use App\Models\AccountStatus;
use App\Models\Suit;
use Illuminate\Support\Facades\Lang;
use Orchestra\Testbench\TestCase;

class EnumSupportTest extends TestCase {

    public function testEnumsAreNamed() {
        $name = AccountStatus::modelName();

        $this->assertNotNull($name);
        $this->assertEquals('account_status', $name->singular);
    }

    public function testTranslatingValuesWithoutLocalization() {
        $this->assertEquals('Paid', AccountStatus::Paid->humanize());
        $this->assertEquals('Free For Life', AccountStatus::FreeForLife->humanize());
    }

    public function testTranslatingValuesWithoutLocalizationButWithDefault() {
        $this->assertEquals('Yay cash', AccountStatus::Paid->humanize(['default' => 'Yay cash']));
    }

    public function testTranslatingValuesWithLocalization() {
        Lang::addLines([
            'eloquent.attributes.account_status/value.Paid' => 'Show me the money!'
        ], 'enums');

        $this->assertEquals('Show me the money!', AccountStatus::Paid->humanize(['locale' => 'enums']));
    }

    public function testTranslatingValuesWithoutLocalizationAndParameters() {
        $opts = [
            'name' => 'Thom'
        ];

        $this->assertEquals('Paid', AccountStatus::Paid->humanize($opts));
    }

    public function testTranslatingValuesWithLocalizationAndDefault() {
        Lang::addLines([
            'eloquent.attributes.account_status/value.Paid' => 'Show me the money!'
        ], 'enums');

        $opts = [
            'locale' => 'enums',
            'default' => 'Yay cash'
        ];

        $this->assertEquals('Show me the money!', AccountStatus::Paid->humanize($opts));
    }

    public function testTranslatingValuesWithLocalizationAndParameters() {
        Lang::addLines([
            'eloquent.attributes.account_status/value.Paid' => ':name is paid'
        ], 'enums');

        $opts = [
            'locale' => 'enums',
            'name' => 'Thom'
        ];

        $this->assertEquals('Thom is paid', AccountStatus::Paid->humanize($opts));
    }

    public function testTranslatingValuesWithLocalizationAndChoice() {
        Lang::addLines([
            'eloquent.attributes.account_status/value.Paid' => '{1} one license|[2,*] :count licenses'
        ], 'enums');

        $opts = [
            'locale' => 'enums',
            'count' => 1
        ];

        $this->assertEquals('one license', AccountStatus::Paid->humanize($opts));

        $opts = [
            'locale' => 'enums',
            'count' => 5
        ];

        $this->assertEquals('5 licenses', AccountStatus::Paid->humanize($opts));
    }

    public function testOptionCreation() {
        $expected = [
            ['Cancelled', 2],
            ['Free For Life', 3],
            ['Paid', 1],
            ['Trial', 0],
        ];

        $this->assertEquals($expected, AccountStatus::orderedCasesForOptions()->all());
    }

    public function testOptionCreationWithLocalization() {
        Lang::addLines([
            'eloquent.attributes.account_status/value.Paid' => 'Yay cash'
        ], 'enums');

        $expected = [
            ['Cancelled', 2],
            ['Free For Life', 3],
            ['Trial', 0],
            ['Yay cash', 1],
        ];

        $opts = [
            'locale' => 'enums'
        ];

        $this->assertEquals($expected, AccountStatus::orderedCasesForOptions($opts)->all());
    }

    public function testOptionCreationWithIntOrder() {
        $expected = [
            ['Trial', 0],
            ['Paid', 1],
            ['Cancelled', 2],
            ['Free For Life', 3],
        ];

        $this->assertEquals($expected, AccountStatus::orderedCasesForOptions(orderBy: 1)->all());
    }

    public function testOptionCreationWithClosureOrder() {
        $expected = [
            ['Free For Life', 3],
            ['Trial', 0],
            ['Cancelled', 2],
            ['Paid', 1],
        ];

        $this->assertEquals(
            $expected,
            AccountStatus::orderedCasesForOptions(orderBy: fn ($tuple) => $tuple[2]->lastBillAmount())->all()
        );
    }

    public function testPureEnumNaming() {
        $this->assertNotNull(Suit::modelName());
    }

    public function testPureEnumTranslation() {
        $this->assertEquals('Hearts', Suit::Hearts->humanize());
    }

    public function testPureEnumOptionCreation() {
        $expected = [
            ['Clubs', 'Clubs'],
            ['Diamonds', 'Diamonds'],
            ['Hearts', 'Hearts'],
            ['Spades', 'Spades'],
        ];

        $this->assertEquals($expected, Suit::Hearts->orderedCasesForOptions()->all());
    }
}

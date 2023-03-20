<?php

use App\Models\Account;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Eye;
use App\Models\Order;
use App\Models\Tag;
use App\Models\Tagging;

require_once __DIR__ . '/DatabaseTestCase.php';
require_once __DIR__ . '/../models/TestModels.php';

class AutosavesRelationsTest extends DatabaseTestCase {

    public function testFailsForInvalidHasOneChild() {
        $company = new Company(['name' => 'Acme Inc.']);
        $account = new Account();

        $this->assertFalse($account->isValid());

        $company->setRelation('account', new Account());
        $this->assertFalse($company->isValid());

        $this->assertFalse($company->save());
        $this->assertTrue($company->errors->has('account'));
    }

    public function testNotResavedWhenUnchanged() {
        $company = new Company(['name' => 'Acme Inc']);
        $company->saveOrFail();
        $this->assertQueryCount(1);

        $this->resetQueries();
        $company->setRelation('account', new Account(['name' => 'First']));
        $company->saveOrFail();
        $this->assertQueryCount(1);

        $this->resetQueries();
        $newCompany = new Company(['name' => 'Acme 2']);
        $newCompany->setRelation('account', new Account(['name' => 'Second']));
        $newCompany->saveOrFail();
        $this->assertQueryCount(2);
    }

    public function testStoreTwoRelationsWithOneSave() {
        $numOrders = Order::count();
        $numCustomers = Customer::count();
        $order = new Order();

        $customer1 = new Customer(['name' => 'Joe']);
        $customer2 = new Customer(['name' => 'Jane']);

        $order->setRelation('billing', $customer1);
        $order->setRelation('shipping', $customer2);

        $this->resetQueries();
        $order->saveOrFail();
        $this->assertQueryCount(3);

        $this->assertTrue($customer1->exists);
        $this->assertTrue($customer2->exists);

        $order->refresh();

        $this->assertEquals($customer1->id, $order->billing->id);
        $this->assertEquals($customer2->id, $order->shipping->id);

        $this->assertEquals($numOrders + 1, Order::count());
        $this->assertEquals($numCustomers + 2, Customer::count());
    }

    public function testStoreOneModelInTwoRelationsWithOneSave() {
        $numOrders = Order::count();
        $numCustomers = Customer::count();
        $order = new Order();

        $customer1 = new Customer(['name' => 'Joe']);

        $order->setRelation('billing', $customer1);
        $order->setRelation('shipping', $customer1);

        $this->resetQueries();
        $order->saveOrFail();
        // this should really be 2...
        $this->assertQueryCount(3);

        $this->assertTrue($customer1->exists);

        $order->refresh();

        $this->assertEquals($customer1->id, $order->billing->id);

        $this->assertEquals($numOrders + 1, Order::count());
        $this->assertEquals($numCustomers + 1, Customer::count());
    }

    public function testStoreOneModelInTwoRelationsWithOneSaveInExistingModel() {
        $numOrders = Order::count();
        $numCustomers = Customer::count();
        $order = Order::create();

        $customer1 = new Customer(['name' => 'Joe']);

        $order->setRelation('billing', $customer1);
        $order->setRelation('shipping', $customer1);

        $order->saveOrFail();
        $this->assertTrue($customer1->exists);

        $order->refresh();

        $customer2 = new Customer(['name' => 'Jane']);
        $order->setRelation('billing', $customer2);
        $order->setRelation('shipping', $customer2);

        $order->saveOrFail();
        $this->assertTrue($customer2->exists);

        $this->assertEquals($customer2->id, $order->billing->id);

        $this->assertEquals($numOrders + 1, Order::count());
        $this->assertEquals($numCustomers + 2, Customer::count());
    }

    public function testStorePolymorphicBelongsTo() {
        $numTaggings = Tagging::count();

        $tag = Tag::create(['label' => 'Misc']);
        $tagging = new Tagging(['tag_id' => $tag->id]);
        $customer = Customer::create(['name' => 'Joe']);

        $tagging->setRelation('taggable', $customer);
        $tagging->saveOrFail();

        $tagging->refresh();

        $this->assertEquals($numTaggings + 1, Tagging::count());
        $this->assertEquals($customer->id, $tagging->taggable_id);
        $this->assertEquals(Customer::class, $tagging->taggable_type);
    }

    public function testMarkingForDestruction() {
        $order = new Order();

        $this->assertFalse($order->isMarkedForDestruction());

        $order->markForDestruction();

        $this->assertTrue($order->isMarkedForDestruction());
    }
}

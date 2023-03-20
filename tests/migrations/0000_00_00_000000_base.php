<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class Base extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        if (!Schema::hasTable('tags')) {
            Schema::create('tags', function (Blueprint $table) {
                $table->increments('id');
                $table->string('label');
            });
        }

        if (!Schema::hasTable('taggings')) {
            Schema::create('taggings', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('tag_id')
                    ->unsigned();
                $table->foreign('tag_id')
                    ->references('id')->on('tags');

                $table->morphs('taggable');
            });
        }

        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
            });
        }

        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
            });
        }

        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('billing_customer_id')
                    ->unsigned()
                    ->nullable();
                $table->foreign('billing_customer_id')
                    ->references('id')->on('customers');
                $table->integer('shipping_customer_id')
                    ->unsigned()
                    ->nullable();
                $table->foreign('shipping_customer_id')
                    ->references('id')->on('customers');
            });
        }

        if (!Schema::hasTable('eyes')) {
            Schema::create('eyes', function (Blueprint $table) {
                $table->increments('id');
                $table->string('side')->nullable();
            });
        }

        if (!Schema::hasTable('cones')) {
            Schema::create('cones', function (Blueprint $table) {
                $table->increments('id');
                $table->string('color');
                $table->integer('eye_id')
                    ->unsigned();
                $table->foreign('eye_id')
                    ->references('id')->on('eyes');
            });
        }

        if (!Schema::hasTable('irises')) {
            Schema::create('irises', function (Blueprint $table) {
                $table->increments('id');
                $table->string('color');
                $table->integer('eye_id')
                    ->unsigned();
                $table->foreign('eye_id')
                    ->references('id')->on('eyes');
            });
        }

        if (!Schema::hasTable('retinas')) {
            Schema::create('retinas', function (Blueprint $table) {
                $table->increments('id');
                $table->string('status');
                $table->integer('eye_id')
                    ->unsigned()
                    ->nullable();
                $table->foreign('eye_id')
                    ->references('id')->on('eyes');
            });
        }

        if (!Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->integer('company_id')
                    ->unsigned();
                $table->foreign('company_id')
                    ->references('id')->on('companies');
            });
        }

        if (!Schema::hasTable('movies')) {
            Schema::create('movies', function (Blueprint $table) {
                $table->increments('movieid');
                $table->string('name');
            });
        }

        if (!Schema::hasTable('guitars')) {
            Schema::create('guitars', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
            });
        }

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('comments')) {
            Schema::create('comments', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')
                    ->unsigned()
                    ->nullable();
                $table->string('content');
                $table->timestamps();

                $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->onDelete('cascade');
            });
        }
    }
}

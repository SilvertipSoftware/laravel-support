<?php

namespace App\Models;

use SilvertipSoftware\LaravelSupport\Eloquent\Model;

class Account extends Model {

    public $timestamps = false;

    public function company() {
        return $this->belongsTo(Company::class);
    }

    protected function initializeTraits() {
        parent::initializeTraits();

        $this->addValidationRules('name', ['required']);
    }
}

class Company extends Model {

    public $timestamps = false;

    public function account() {
        return $this->hasOne(Account::class);
    }

    protected static function bootTraits() {
        parent::bootTraits();

        static::addAutosavedRelation('account');
    }

    protected function initializeTraits() {
        parent::initializeTraits();

        $this->addValidationRules('name', ['required']);
    }
}

class Cone extends Model {
    public $timestamps = false;
}

class Eye extends Model {

    public $timestamps = false;

    public $createdFlagStack = [];

    public function cones() {
        return $this->hasMany(Cone::class);
    }

    public function iris() {
        return $this->hasOne(Iris::class);
    }

    public function retina() {
        return $this->hasOne(Retina::class);
    }

    public function permanent_iris() {
        return $this->hasOne(Iris::class);
    }

    public function update_only_iris() {
        return $this->hasOne(Iris::class);
    }

    public function update_and_destroy_iris() {
        return $this->hasOne(Iris::class);
    }

    protected static function bootTraits() {
        Eye::created(function ($eye) {
            $eye->createdFlagStack[] = $eye->iris
                ? !$eye->iris->exists
                : 'UNSET';
        });

        parent::bootTraits();
        static::addNestedAttribute('iris', ['allow_destroy' => true]);
        static::addNestedAttribute('permanent_iris', ['allow_destroy' => false]);
        static::addNestedAttribute('update_only_iris', ['update_only' => true]);
        static::addNestedAttribute('update_and_destroy_iris', ['update_only' => true, 'allow_destroy' => true]);
        static::addNestedAttribute('cones', ['allow_destroy' => true]);

        Eye::created(function ($eye) {
            $eye->createdFlagStack[] = $eye->iris
                ? !$eye->iris->exists
                : 'UNSET';
        });
    }
}

class Iris extends Model {
    public $timestamps = false;

    public function eye() {
        return $this->belongsTo(Eye::class);
    }
}

class Retina extends Model {
    public $timestamps = false;

    public function eye() {
        return $this->belongsTo(Eye::class);
    }

    public function permanent_eye() {
        return $this->belongsTo(Eye::class, 'eye_id');
    }

    public function update_only_eye() {
        return $this->belongsTo(Eye::class, 'eye_id');
    }

    public function update_and_destroy_eye() {
        return $this->belongsTo(Eye::class, 'eye_id');
    }

    protected static function bootTraits() {
        parent::bootTraits();

        static::addNestedAttribute('eye', ['allow_destroy' => true]);
        static::addNestedAttribute('permanent_eye', ['allow_destroy' => false]);
        static::addNestedAttribute('update_only_eye', ['update_only' => true]);
        static::addNestedAttribute('update_and_destroy_eye', ['allow_destroy' => true, 'update_only' => true]);
    }
}

class Customer extends Model {
    public $timestamps = false;

    public function taggings() {
        return $this->morphMany(Tagging::class, 'taggable');
    }
}

class Order extends Model {
    public $timestamps = false;

    public function billing() {
        return $this->belongsTo(Customer::class, 'billing_customer_id');
    }

    public function shipping() {
        return $this->belongsTo(Customer::class, 'shipping_customer_id');
    }

    public function nested_billing() {
        return $this->belongsTo(Customer::class, 'billing_customer_id');
    }

    protected static function bootTraits() {
        parent::bootTraits();

        static::addAutosavedRelation(['billing', 'shipping']);
        static::addNestedAttribute(['nested_billing']);
    }
}

class Tag extends Model {
    public $timestamps = false;

    public function taggings() {
        return $this->hasMany(Tagging::class);
    }
}

class Tagging extends Model {
    public $timestamps = false;

    public function tag() {
        return $this->belongsTo(Tag::class);
    }

    public function taggable() {
        return $this->morphTo();
    }

    protected static function bootTraits() {
        parent::bootTraits();

        static::addAutosavedRelation('taggable');
    }
}

class Guitar extends Model {
    public $timestamps = false;

    public $firedEvents = [];

    protected function fireModelEvent($event, $halt = true) {
        $this->firedEvents[] = $event;

        parent::fireModelEvent($event, $halt);
    }
}

class InvalidNestedAttrModel extends Model {

    protected static function bootTraits() {
        parent::bootTraits();

        static::addNestedAttribute('unknown');
    }
}

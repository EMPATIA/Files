<?php

namespace App\One;

use Illuminate\Support\Facades\Facade;

class OneFacade extends Facade {
    protected static function getFacadeAccessor() {
        return 'one';
    }
}
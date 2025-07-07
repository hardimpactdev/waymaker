<?php

namespace HardImpact\Waymaker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \HardImpact\Waymaker\Waymaker
 */
class Waymaker extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \HardImpact\Waymaker\Waymaker::class;
    }
}

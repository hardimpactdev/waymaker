<?php

namespace NckRtl\Waymaker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \NckRtl\Waymaker\Waymaker
 */
class Waymaker extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \NckRtl\Waymaker\Waymaker::class;
    }
}

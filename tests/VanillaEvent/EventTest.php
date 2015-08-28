<?php

namespace Rentalhost\VanillaEvent;

use PHPUnit_Framework_TestCase;

class EventTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test properties.
     */
    public function testProperties()
    {
        static::assertClassHasAttribute('index', Event::class);
        static::assertClassHasAttribute('register', Event::class);
        static::assertClassHasAttribute('data', Event::class);
        static::assertClassHasAttribute('returnedData', Event::class);
        static::assertClassHasAttribute('registeredData', Event::class);
        static::assertClassHasAttribute('target', Event::class);
        static::assertClassHasAttribute('currentTarget', Event::class);
    }
}

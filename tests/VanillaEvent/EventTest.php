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
        $this->assertClassHasAttribute("index", Event::class);
        $this->assertClassHasAttribute("register", Event::class);
        $this->assertClassHasAttribute("data", Event::class);
        $this->assertClassHasAttribute("returnedData", Event::class);
        $this->assertClassHasAttribute("registeredData", Event::class);
        $this->assertClassHasAttribute("target", Event::class);
        $this->assertClassHasAttribute("currentTarget", Event::class);
    }
}

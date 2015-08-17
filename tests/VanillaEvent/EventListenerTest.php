<?php

namespace Rentalhost\VanillaEvent;

use PHPUnit_Framework_TestCase;

class EventListenerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test basic methods.
     * @covers Rentalhost\VanillaEvent\EventListener::__construct
     * @covers Rentalhost\VanillaEvent\EventListener::on
     * @covers Rentalhost\VanillaEvent\EventListener::off
     * @covers Rentalhost\VanillaEvent\EventListener::one
     * @covers Rentalhost\VanillaEvent\EventListener::has
     * @covers Rentalhost\VanillaEvent\EventListener::filter
     * @covers Rentalhost\VanillaEvent\EventListener::fire
     * @covers Rentalhost\VanillaEvent\EventRegister::__construct
     * @covers Rentalhost\VanillaEvent\EventRegister::splitNames
     * @return void
     */
    public function testBasic()
    {
        $noopCallback = function () {};

        $eventListener = new EventListener;

        $count = 0;
        $countCallback = function () use (&$count) { $count++; };

        // Global event listener.
        $this->assertInstanceOf(EventListener::class, EventListener::$global);

        // Invalid.
        $this->assertSame(false, $eventListener->on("*", $noopCallback));
        $this->assertSame(false, $eventListener->on("!!INVALID!!", $noopCallback));

        // On.
        $this->assertInstanceOf(EventRegister::class, $eventListener->on("event1", $countCallback));
        $this->assertInstanceOf(EventRegister::class, $eventListener->on("event1", $countCallback));
        $this->assertInstanceOf(EventRegister::class, $eventListener->on("event2", $countCallback));

        // Has.
        $this->assertFalse($eventListener->has());
        $this->assertFalse($eventListener->has(123));
        $this->assertFalse($eventListener->has(false));
        $this->assertFalse($eventListener->has(true));

        $this->assertTrue($eventListener->has("event1"));
        $this->assertTrue($eventListener->has("event2"));
        $this->assertFalse($eventListener->has("event3"));

        $this->assertTrue($eventListener->has($countCallback));

        $this->assertTrue($eventListener->has("event1", $countCallback));
        $this->assertTrue($eventListener->has("event2", $countCallback));
        $this->assertFalse($eventListener->has("event3", $countCallback));

        $this->assertNotSame($noopCallback, $countCallback);

        $this->assertFalse($eventListener->has("event1", $noopCallback));
        $this->assertFalse($eventListener->has("event2", $noopCallback));
        $this->assertFalse($eventListener->has("event3", $noopCallback));

        // Simple fire.
        $this->assertInstanceOf(Event::class, $eventListener->fire("event1"));

        $this->assertSame(2, $count);

        // One.
        $this->assertInstanceOf(EventRegister::class, $eventListener->one("event3", $countCallback));

        $this->assertTrue($eventListener->has("event1", $countCallback));

        $eventListener->fire("event3");
        $eventListener->fire("event3");

        $this->assertSame(3, $count);

        // Off.
        $this->assertSame(2, $eventListener->off("event1"));
        $this->assertSame(1, $eventListener->off("event2"));
        $this->assertSame(0, $eventListener->off("event3"));

        // Event cancel.
        $count = 0;

        $eventListener->on("event1", function () use ($countCallback) { $countCallback(); });
        $eventListener->on("event1", function () use ($countCallback) { $countCallback(); return false; });
        $eventListener->on("event1", function () use ($countCallback) { $countCallback(); });

        $eventListener->fire("event1");

        $this->assertSame(2, $count);

        $eventListener->off("event1");

        // Namespace.
        $count = 0;

        $eventListener->on("namespace1::event10", function () use ($countCallback) { $countCallback(); });
        $eventListener->on("namespace2::event10", function () { return false; });
        $eventListener->on("namespace1::event10", function () use ($countCallback) { $countCallback(); });

        $eventListener->fire("event10");

        $this->assertSame(1, $count);

        $eventListener->fire("namespace1::event10");

        $this->assertSame(3, $count);
        $this->assertSame(3, $eventListener->off("event10"));

        // Namespace Off.
        $eventListener->on("namespace1::event10", $noopCallback);
        $eventListener->on("namespace2::event20", $noopCallback);
        $eventListener->on("namespace2::event30", $noopCallback);
        $eventListener->on("namespace2::event40", $noopCallback);
        $eventListener->on("namespace2::event40", $noopCallback);

        $this->assertCount(1, $eventListener->filter("event10"));
        $this->assertCount(1, $eventListener->filter("event20"));
        $this->assertCount(1, $eventListener->filter("event30"));
        $this->assertCount(2, $eventListener->filter("event40"));

        $eventListener->off("event40");

        $this->assertCount(1, $eventListener->filter("event10"));
        $this->assertCount(1, $eventListener->filter("event20"));
        $this->assertCount(1, $eventListener->filter("event30"));
        $this->assertCount(0, $eventListener->filter("event40"));

        $eventListener->off("namespace2::*");

        $this->assertCount(1, $eventListener->filter("event10"));
        $this->assertCount(0, $eventListener->filter("event20"));
        $this->assertCount(0, $eventListener->filter("event30"));

        $eventListener->off("namespace1::event10");

        $this->assertCount(0, $eventListener->filter("event10"));

        // Empty namespace is different of general namespace.
        $eventListener->on("namespace1::event10", $noopCallback);
        $eventListener->on("namespace2::event20", $noopCallback);

        $this->assertCount(0, $eventListener->filter("::event10"));
        $this->assertCount(0, $eventListener->filter("::event20"));
        $this->assertCount(0, $eventListener->filter("::*"));

        $eventListener->on("::event10", $noopCallback);
        $eventListener->on("::event20", $noopCallback);

        $eventListener->off("namespace1::*");
        $eventListener->off("namespace2::*");

        $this->assertCount(1, $eventListener->filter("::event10"));
        $this->assertCount(1, $eventListener->filter("::event20"));
        $this->assertCount(2, $eventListener->filter("::*"));

        $eventListener->off("::*");
        $eventListener->off("::*");

        // Complex test.
        $phpunit = $this;
        $register1 = $eventListener->one("namespace1::number.push", function ($event) use ($phpunit, $eventListener, &$register1) {
            $fireDataExpected = new Event;
            $fireDataExpected->index = 0;
            $fireDataExpected->register = $register1;
            $fireDataExpected->register->listener = $eventListener;
            $fireDataExpected->data = [ "eventData" => 3 ];
            $fireDataExpected->registeredData = [ "initialNumber" => 5 ];
            $fireDataExpected->returnedData = null;
            $fireDataExpected->target = "number.push";
            $fireDataExpected->currentTarget = "namespace1::number.push";

            $phpunit->assertEquals($fireDataExpected, $event);

            return $event->registeredData["initialNumber"];
        }, [ "initialNumber" => 5 ]);

        $register2 = $eventListener->one("namespace2::number.push", function ($event) use ($phpunit, $eventListener, &$register2) {
            $fireDataExpected = new Event;
            $fireDataExpected->index = 1;
            $fireDataExpected->register = $register2;
            $fireDataExpected->register->listener = $eventListener;
            $fireDataExpected->data = [ "eventData" => 3 ];
            $fireDataExpected->registeredData = null;
            $fireDataExpected->returnedData = 5;
            $fireDataExpected->target = "number.push";
            $fireDataExpected->currentTarget = "namespace2::number.push";

            $phpunit->assertEquals($fireDataExpected, $event);

            return $event->returnedData + $event->data["eventData"];
        });

        $register3 = $eventListener->one("namespace3::number.push", function ($event) use ($phpunit, $eventListener, &$register3) {
            $fireDataExpected = new Event;
            $fireDataExpected->index = 2;
            $fireDataExpected->register = $register3;
            $fireDataExpected->register->listener = $eventListener;
            $fireDataExpected->data = [ "eventData" => 3 ];
            $fireDataExpected->registeredData = null;
            $fireDataExpected->returnedData = 8;
            $fireDataExpected->target = "number.push";
            $fireDataExpected->currentTarget = "namespace3::number.push";

            $phpunit->assertEquals($fireDataExpected, $event);
        });

        $this->assertInstanceOf(Event::class, $eventListener->fire("number.push", [ "eventData" => 3 ]));
        $this->assertCount(0, $eventListener->filter("number.push"));
    }
}

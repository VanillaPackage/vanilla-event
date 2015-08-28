<?php

namespace Rentalhost\VanillaEvent;

use PHPUnit_Framework_TestCase;

/**
 * Class EventListenerTest
 * @package Rentalhost\VanillaEvent
 */
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
        $noopCallback = function () {
        };

        $eventListener = new EventListener;

        $count = 0;
        $countCallback = function () use (&$count) {
            $count++;
        };

        // Global event listener.
        static::assertInstanceOf(EventListener::class, EventListener::$global);

        // Invalid.
        static::assertSame(false, $eventListener->on('*', $noopCallback));
        static::assertSame(false, $eventListener->on('!!INVALID!!', $noopCallback));

        // On.
        static::assertInstanceOf(EventRegister::class, $eventListener->on('event1', $countCallback));
        static::assertInstanceOf(EventRegister::class, $eventListener->on('event1', $countCallback));
        static::assertInstanceOf(EventRegister::class, $eventListener->on('event2', $countCallback));

        // Has.
        static::assertFalse($eventListener->has());
        static::assertFalse($eventListener->has(123));
        static::assertFalse($eventListener->has(false));
        static::assertFalse($eventListener->has(true));

        static::assertTrue($eventListener->has('event1'));
        static::assertTrue($eventListener->has('event2'));
        static::assertFalse($eventListener->has('event3'));

        static::assertTrue($eventListener->has($countCallback));

        static::assertTrue($eventListener->has('event1', $countCallback));
        static::assertTrue($eventListener->has('event2', $countCallback));
        static::assertFalse($eventListener->has('event3', $countCallback));

        static::assertNotSame($noopCallback, $countCallback);

        static::assertFalse($eventListener->has('event1', $noopCallback));
        static::assertFalse($eventListener->has('event2', $noopCallback));
        static::assertFalse($eventListener->has('event3', $noopCallback));

        // Simple fire.
        static::assertInstanceOf(Event::class, $eventListener->fire('event1'));

        static::assertSame(2, $count);

        // One.
        static::assertInstanceOf(EventRegister::class, $eventListener->one('event3', $countCallback));

        static::assertTrue($eventListener->has('event1', $countCallback));

        $eventListener->fire('event3');
        $eventListener->fire('event3');

        static::assertSame(3, $count);

        // Off.
        static::assertSame(2, $eventListener->off('event1'));
        static::assertSame(1, $eventListener->off('event2'));
        static::assertSame(0, $eventListener->off('event3'));

        // Event cancel.
        $count = 0;

        $eventListener->on('event1', function () use ($countCallback) {
            $countCallback();
        });
        $eventListener->on('event1', function () use ($countCallback) {
            $countCallback();

            return false;
        });
        $eventListener->on('event1', function () use ($countCallback) {
            $countCallback();
        });

        $eventListener->fire('event1');

        static::assertSame(2, $count);

        $eventListener->off('event1');

        // Namespace.
        $count = 0;

        $eventListener->on('namespace1::event10', function () use ($countCallback) {
            $countCallback();
        });
        $eventListener->on('namespace2::event10', function () {
            return false;
        });
        $eventListener->on('namespace1::event10', function () use ($countCallback) {
            $countCallback();
        });

        $eventListener->fire('event10');

        static::assertSame(1, $count);

        $eventListener->fire('namespace1::event10');

        static::assertSame(3, $count);
        static::assertSame(3, $eventListener->off('event10'));

        // Namespace Off.
        $eventListener->on('namespace1::event10', $noopCallback);
        $eventListener->on('namespace2::event20', $noopCallback);
        $eventListener->on('namespace2::event30', $noopCallback);
        $eventListener->on('namespace2::event40', $noopCallback);
        $eventListener->on('namespace2::event40', $noopCallback);

        static::assertCount(1, $eventListener->filter('event10'));
        static::assertCount(1, $eventListener->filter('event20'));
        static::assertCount(1, $eventListener->filter('event30'));
        static::assertCount(2, $eventListener->filter('event40'));

        $eventListener->off('event40');

        static::assertCount(1, $eventListener->filter('event10'));
        static::assertCount(1, $eventListener->filter('event20'));
        static::assertCount(1, $eventListener->filter('event30'));
        static::assertCount(0, $eventListener->filter('event40'));

        $eventListener->off('namespace2::*');

        static::assertCount(1, $eventListener->filter('event10'));
        static::assertCount(0, $eventListener->filter('event20'));
        static::assertCount(0, $eventListener->filter('event30'));

        $eventListener->off('namespace1::event10');

        static::assertCount(0, $eventListener->filter('event10'));

        // Empty namespace is different of general namespace.
        $eventListener->on('namespace1::event10', $noopCallback);
        $eventListener->on('namespace2::event20', $noopCallback);

        static::assertCount(0, $eventListener->filter('::event10'));
        static::assertCount(0, $eventListener->filter('::event20'));
        static::assertCount(0, $eventListener->filter('::*'));

        $eventListener->on('::event10', $noopCallback);
        $eventListener->on('::event20', $noopCallback);

        $eventListener->off('namespace1::*');
        $eventListener->off('namespace2::*');

        static::assertCount(1, $eventListener->filter('::event10'));
        static::assertCount(1, $eventListener->filter('::event20'));
        static::assertCount(2, $eventListener->filter('::*'));

        $eventListener->off('::*');
        $eventListener->off('::*');

        // Complex test.
        $phpunit = $this;
        /** @noinspection PhpUnusedLocalVariableInspection */
        $register1 = $eventListener->one('namespace1::number.push', function ($event) use ($phpunit, $eventListener, &$register1) {
            $expectedEvent = new Event;
            $expectedEvent->index = 0;
            $expectedEvent->register = $register1;
            $expectedEvent->register->eventListener = $eventListener;
            $expectedEvent->data = [ 'eventData' => 3 ];
            $expectedEvent->registeredData = [ 'initialNumber' => 5 ];
            $expectedEvent->returnedData = null;
            $expectedEvent->target = 'number.push';
            $expectedEvent->currentTarget = 'namespace1::number.push';

            $phpunit->assertEquals($expectedEvent, $event);
            $phpunit->assertEquals($expectedEvent->register, $event->register);

            return $event->registeredData['initialNumber'];
        }, [ 'initialNumber' => 5 ]);

        /** @noinspection PhpUnusedLocalVariableInspection */
        $register2 = $eventListener->one('namespace2::number.push', function ($event) use ($phpunit, $eventListener, &$register2) {
            $expectedEvent = new Event;
            $expectedEvent->index = 1;
            $expectedEvent->register = $register2;
            $expectedEvent->register->eventListener = $eventListener;
            $expectedEvent->data = [ 'eventData' => 3 ];
            $expectedEvent->registeredData = null;
            $expectedEvent->returnedData = 5;
            $expectedEvent->target = 'number.push';
            $expectedEvent->currentTarget = 'namespace2::number.push';

            $phpunit->assertEquals($expectedEvent, $event);
            $phpunit->assertEquals($expectedEvent->register, $event->register);

            return $event->returnedData + $event->data['eventData'];
        });

        /** @noinspection PhpUnusedLocalVariableInspection */
        $register3 = $eventListener->one('namespace3::number.push', function ($event) use ($phpunit, $eventListener, &$register3) {
            $expectedEvent = new Event;
            $expectedEvent->index = 2;
            $expectedEvent->register = $register3;
            $expectedEvent->register->eventListener = $eventListener;
            $expectedEvent->data = [ 'eventData' => 3 ];
            $expectedEvent->registeredData = null;
            $expectedEvent->returnedData = 8;
            $expectedEvent->target = 'number.push';
            $expectedEvent->currentTarget = 'namespace3::number.push';

            $phpunit->assertEquals($expectedEvent, $event);
            $phpunit->assertEquals($expectedEvent->register, $event->register);
        });

        static::assertInstanceOf(Event::class, $eventListener->fire('number.push', [ 'eventData' => 3 ]));
        static::assertCount(0, $eventListener->filter('number.push'));
    }
}

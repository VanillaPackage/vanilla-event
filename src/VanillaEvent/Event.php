<?php

namespace Rentalhost\VanillaEvent;

class Event
{
    /**
     * Event trigger index, based on zero-index.
     * @var integer
     */
    public $index;

    /**
     * Event register that fired this event.
     * @var EventRegister
     */
    public $register;

    /**
     * Event data, passed on event trigger.
     * @var mixed
     */
    public $data;

    /**
     * Event data, passed on event register.
     * @var mixed
     */
    public $registeredData;

    /**
     * Event data, passed on last event return.
     * @var mixed
     */
    public $returnedData;

    /**
     * Event target, the event type that was passed to fire method.
     * @var string
     */
    public $target;

    /**
     * Event current target, the current fullname of event register.
     * @var string
     */
    public $currentTarget;
}

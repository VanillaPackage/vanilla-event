<?php

namespace Rentalhost\VanillaEvent;

use Rentalhost\VanillaParameter\Parameter;

/**
 * Class EventListener
 * @package Rentalhost\VanillaEvent
 */
class EventListener
{
    /**
     * Store global event listener.
     * @var self
     */
    public static $global;

    /**
     * Stores listeners.
     * @var EventRegister[]
     */
    private $registers;

    /**
     * Construct listener.
     */
    public function __construct()
    {
        $this->registers = [ ];
    }

    /**
     * Add an event listener.
     *
     * @param  string   $name     Event name.
     * @param  callable $callback Event callback.
     * @param  mixed    $data     Event data.
     * @param  integer  $priority Event priority.
     *
     * @return EventRegister|false
     */
    public function on($name, $callback, $data = null, $priority = 5)
    {
        $eventRegister = new EventRegister($this, $name, $callback, $data, $priority);

        if (!$eventRegister->name
            || $eventRegister->name === '*'
        ) {
            return false;
        }

        $this->registers[] = $eventRegister;

        return $eventRegister;
    }

    /**
     * Remove event registers.
     *
     * @var string?   $name     Event name or namespace.
     * @var callable? $callback Event callable.
     *
     * @return int Number of removed events.
     */
    public function off()
    {
        $registers = call_user_func_array([ $this, 'filter' ], func_get_args());

        if (!$registers) {
            return 0;
        }

        foreach ($this->registers as $key => $register) {
            if (in_array($register, $registers, true)) {
                unset( $this->registers[$key] );
            }
        }

        $this->registers = array_values($this->registers);

        return count($registers);
    }

    /**
     * Add an event listener that will run one time.
     *
     * @param  string   $name     Event name.
     * @param  callable $callback Event callback.
     * @param  mixed    $data     Event data.
     * @param  integer  $priority Event priority.
     *
     * @return false|EventRegister
     */
    public function one($name, $callback, $data = null, $priority = 5)
    {
        $eventRegister = $this->on($name, $callback, $data, $priority);
        $eventRegister->one = true;

        return $eventRegister;
    }

    /**
     * Check if event was registered.
     * @return boolean
     */
    public function has()
    {
        return (bool) call_user_func_array([ $this, 'filter' ], func_get_args());
    }

    /**
     * Returns events by filter name, namespace or callback.
     * @return EventRegister[]
     */
    public function filter()
    {
        Parameter::organize(func_get_args())
            ->expects(1)
            ->add($filterNames, 'string')
            ->add($filterCallback, 'callable');

        if (!$filterNames
            && !$filterCallback
        ) {
            return [ ];
        }

        // Filters data.
        $filterNamespace = null;
        $filterName = null;

        // Match name/namespace filters.
        if ($filterNames) {
            EventRegister::splitNames($filterNames, $filterName, $filterNamespace);
        }

        // Collect registers.
        $registers = [ ];

        foreach ($this->registers as $register) {
            if ($filterNamespace !== null
                && $filterNamespace !== $register->namespace
            ) {
                continue;
            }

            if ($filterName !== null
                && $filterName !== $register->name
                && $filterName !== '*'
            ) {
                continue;
            }

            if ($filterCallback !== null
                && $filterCallback !== $register->callback
            ) {
                continue;
            }

            $registers[] = $register;
        }

        return $registers;
    }

    /**
     * Fire an event.
     *
     * @param string $name           Event name.
     * @param mixed  $additionalData Event additional data.
     *
     * @return Event
     */
    public function fire($name, $additionalData = null)
    {
        $eventHandler = new Event;
        $eventHandler->target = $name;
        $eventHandler->data = $additionalData;

        $eventRegisters = $this->filter($name);

        foreach ($eventRegisters as $key => $eventRegister) {
            $eventHandler->index = $key;
            $eventHandler->register = $eventRegister;
            $eventHandler->registeredData = $eventRegister->data;
            $eventHandler->currentTarget = "{$eventRegister->namespace}::{$eventRegister->name}";
            $eventHandler->returnedData = call_user_func($eventRegister->callback, $eventHandler);

            // Cancel event, if it returns false.
            if ($eventHandler->returnedData === false) {
                break;
            }

            // If event is "one", remove it from registers.
            if ($eventRegister->one === true) {
                unset( $this->registers[array_search($eventRegister, $this->registers, true)] );

                $this->registers = array_values($this->registers);
            }
        }

        return $eventHandler;
    }
}

// Initialize global event listener.
// @codeCoverageIgnoreStart
EventListener::$global = new EventListener;
// @codeCoverageIgnoreEnd

<?php

namespace Rentalhost\VanillaEvent;

class EventRegister
{
    /**
     * Event listener, that registered this event.
     * @var EventListener
     */
    public $eventListener;

    /**
     * Stores event namespace.
     * @var string
     */
    public $namespace;

    /**
     * Event name.
     * @var string
     */
    public $name;

    /**
     * Event callback.
     * @var callable
     */
    public $callback;

    /**
     * Event database.
     * @var mixed
     */
    public $data;

    /**
     * Event priority.
     * @var integer
     */
    public $priority;

    /**
     * Indicate if event will removed after first trigger.
     * @var boolean
     */
    public $one;

    /**
     * Construct a new event register.
     *
     * @param EventListener $eventListener Event listener.
     * @param string        $name          Event name.
     * @param callable      $callback      Event callback.
     * @param mixed         $data          Event database.
     * @param integer       $priority      Event priority.
     */
    public function __construct($eventListener, $name, $callback, $data, $priority)
    {
        static::splitNames($name, $this->name, $this->namespace);

        $this->eventListener = $eventListener;
        $this->callback = $callback;
        $this->data = $data;
        $this->priority = $priority;
    }

    /**
     * Parse event names, separating in name and namespace.
     *
     * @param string      $names      Event names.
     * @param string      &$name      Event name.
     * @param string|null &$namespace Event namespace.
     *
     * @return boolean
     */
    public static function splitNames($names, &$name, &$namespace)
    {
        // Match function name.
        if (!$names ||
            !preg_match('/^(?:([\w\d.]+)::)?(^::)?([\w\d\.]+|\*)?$/', $names, $namesMatches)
        ) {
            return false;
        }

        $name = $namesMatches[3];

        $namespace = null;
        if ($namesMatches[2] === '::') {
            $namespace = '';
        }
        else if ($namesMatches[1]) {
            $namespace = $namesMatches[1];
        }

        return true;
    }
}

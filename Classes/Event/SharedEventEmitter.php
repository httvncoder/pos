<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 01.09.14
 * Time: 21:34
 */

namespace Cundd\PersistentObjectStore\Event;


use Cundd\PersistentObjectStore\RuntimeException;


/**
 * Shared Event Emitter to dispatch events
 *
 * @package Cundd\PersistentObjectStore\Emitter
 */
class SharedEventEmitter
{
    /**
     * @var SharedEventEmitter
     */
    static protected $_sharedEventEmitter;
    /**
     * Event Emitter instance
     *
     * @var \Evenement\EventEmitterInterface
     * @Inject
     */
    protected $eventEmitter;
    /**
     * Event loop
     *
     * @var \React\EventLoop\LoopInterface
     */
    protected $eventLoop;

    /**
     * Save the instance as shared Event Emitter
     */
    function __construct()
    {
        static::$_sharedEventEmitter = $this;
    }

    static public function futureEmit($event, array $arguments = [])
    {
        return static::sharedEventEmitter()->getEventEmitter()->emit($event, $arguments);
    }

    /**
     * Returns the shared Event Emitter
     *
     * @return SharedEventEmitter
     */
    static public function sharedEventEmitter()
    {
        if (!static::$_sharedEventEmitter) {
            throw new RuntimeException('Shared Event Emitter has not been created', 1413369080);
        }
        return static::$_sharedEventEmitter;
    }

    static public function on($event, callable $listener)
    {
        return static::sharedEventEmitter()->getEventEmitter()->on($event, $listener);
    }

    static public function once($event, callable $listener)
    {
        return static::sharedEventEmitter()->getEventEmitter()->once($event, $listener);
    }

    static public function removeListener($event, callable $listener)
    {
        return static::sharedEventEmitter()->getEventEmitter()->removeListener($event, $listener);
    }

    static public function removeAllListeners($event = null)
    {
        return static::sharedEventEmitter()->getEventEmitter()->removeAllListeners($event);
    }

    static public function listeners($event)
    {
        return static::sharedEventEmitter()->getEventEmitter()->listeners($event);
    }

    static public function emit($event, array $arguments = [])
    {
        return static::sharedEventEmitter()->getEventEmitter()->emit($event, $arguments);
    }

    /**
     * Returns the event loop
     *
     * @return \React\EventLoop\LoopInterface
     */
    public function getEventLoop()
    {
        return $this->eventLoop;
    }

    /**
     * Set the event loop
     *
     * @param \React\EventLoop\LoopInterface $eventLoop
     * @return $this
     */
    public function setEventLoop($eventLoop)
    {
        $this->eventLoop = $eventLoop;
        return $this;
    }

    /**
     * Schedule a future emit of the given event
     *
     * If the event loop is not set, the event will be emitted immediately
     *
     * @param string $event
     * @param array  $arguments
     */
    public function scheduleFutureEmit($event, array $arguments = [])
    {
        if ($this->eventLoop) {
            $this->eventLoop->futureTick(function () use ($event, $arguments) {
                $this->getEventEmitter()->emit($event, $arguments);
            });
        } else {
            $this->getEventEmitter()->emit($event, $arguments);
        }
    }

    /**
     * Returns the Event Emitter
     *
     * @return \Evenement\EventEmitterInterface
     */
    public function getEventEmitter()
    {
        return $this->eventEmitter;
    }
}
<?php
/**
 * @file
 * Test the EventEmitter class.
 *
 * @author: marand
 *
 * @copyright (c) 2013 Ouest Systèmes Informatiques (OSInet).
 *
 * @license General Public License version 2 or later
 */

namespace OSInet\Heisencache\tests;

use OSInet\Heisencache\EventEmitter;

class EventEmitterTest extends \PHPUnit_Framework_TestCase {

  const SUBSCRIBER_CLASS = 'OSInet\Heisencache\DebugSubscriber';

  protected function getMockSubscriber(array $events, $class = NULL) {
    $subscriber = isset($class)
      ? $this->getMock(self::SUBSCRIBER_CLASS, $events, array(), $class)
      : $this->getMock(self::SUBSCRIBER_CLASS, $events, array());

    return $subscriber;
  }

  public function testOnSingleSubscriberSingleEvent() {
    $event1 = 'event1';
    $emitter = new EventEmitter();

    $subscriber = $this->getMockSubscriber(array($event1));

    try {
      $emitter->on($event1, $subscriber);
    }
    catch (\Exception $e) {
      $this->fail('Passing a subscriber to on() does not throw an exception.');
    }

    $actual = $emitter->getSubscribersByEventName($event1);
    $this->assertInternalType('array', $actual, "getSubscribersByEventName() returns an array.");
    $this->assertEquals(count($actual), 1, "Exactly 1 subscriber returned for event.");
    $this->assertEquals(reset($actual), $subscriber, "Single subscriber on event is returned correctly.");

    try {
      $emitter->on($event1, $subscriber);
    }
    catch (\Exception $e) {
      $this->fail('Passing the same subscriber twice to on() does not throw an exception.');
    }
  }

  public function testOnSingleSubscriberInvalidEvent() {
    $event1 = 'event1';
    $event2 = 'event2';
    $emitter = new EventEmitter();

    $subscriber = $this->getMockSubscriber(array($event1));

    try {
      $emitter->on($event2, $subscriber);
      $this->fail('Passing a subscriber to on() throws an exception.');
    }
    catch (\InvalidArgumentException $e) {
    }
  }

  /**
   * TODO: Find a way to cause the reallocation problem.
   */
  public function testOnReallocatedSubscriber() {
    $event1 = 'event1';
    $emitter = new EventEmitter();

    $subscriber = $this->getMockSubscriber(array($event1));
    $mock_class = get_class($subscriber);
    $hash1 = spl_object_hash($subscriber);

    try {
      $emitter->on($event1, $subscriber);
    }
    catch (\Exception $e) {
      $this->fail('Passing a subscriber to on() does not throw an exception.');
    }
    unset($subscriber);

    $subscriber = $this->getMockSubscriber(array($event1), $mock_class);
    $hash2 = spl_object_hash($subscriber);
    if ($hash1 != $hash2) {
      try {
        $emitter->on($event1, $subscriber);
        $this->fail('Passing a subscriber with a reallocated object has to on() throws an exception.');
      }
      catch (\Exception $e) {
      }
    }
    else {
      // Attempt to reuse the hash failed: no way to induce the problem.
    }
  }

  public function testOnSingleSubscriberTwoEvents() {
    $event1 = 'event1';
    $event2 = 'event2';
    $emitter = new EventEmitter();

    $subscriber = $this->getMockSubscriber(array($event1, $event2));

    try {
      $emitter->on($event1, $subscriber);
    }
    catch (\Exception $e) {
      $this->pass('Passing a subscriber to on() does not throw an exception.');
      }

    $actual = $emitter->getSubscribersByEventName($event1);
    $this->assertInternalType('array', $actual, "getSubscribersByEventName() returns an array.");
    $this->assertEquals(count($actual), 1, "Exactly 1 subscriber returned for first event.");
    $this->assertEquals(reset($actual), $subscriber, "Single subscriber on first event is returned correctly.");

    try {
      $emitter->on($event2, $subscriber);
    }
    catch (\Exception $e) {
      echo "Exception: " . $e->getMessage() . "\n";
      $this->fail('Passing the same subscriber to on() for a second event does not throw an exception.');
    }
    $actual = $emitter->getSubscribersByEventName($event2);
    $this->assertInternalType('array', $actual, "getSubscribersByEventName() returns an array.");
    $this->assertEquals(count($actual), 1, "Exactly 1 subscriber returned for second event.");
    $this->assertEquals(reset($actual), $subscriber, "Single subscriber on second event is returned correctly.");
  }

  public function testOnTwoSubscribersSingleEvent() {
    $event1 = 'event1';
    $emitter = new EventEmitter();

    $sub1 = $this->getMockSubscriber(array($event1));
    $sub2 = $this->getMockSubscriber(array($event1));

    try {
      $emitter->on($event1, $sub1);
    }
    catch (\Exception $e) {
      $this->fail('Passing a subscriber to on() does not throw an exception.');
    }

    $actual = $emitter->getSubscribersByEventName($event1);
    $this->assertInternalType('array', $actual, "getSubscribersByEventName() returns an array.");
    $this->assertEquals(count($actual), 1, "Exactly 1 subscriber returned for event.");
    $this->assertEquals(reset($actual), $sub1, "Single subscriber on first event is returned correctly.");

    try {
      $emitter->on($event1, $sub2);
    }
    catch (\Exception $e) {
      $this->fail('Passing a different subscriber to on() for the same event does not throw an exception.');
    }

    $actual = $emitter->getSubscribersByEventName($event1);
    $this->assertInternalType('array', $actual, "getSubscribersByEventName() returns an array.");
    $this->assertEquals(count($actual), 2, "Exactly 2 subscribers returned for event.");

    $this->assertTrue(in_array($sub1, $actual), "First subscriber on event is returned correctly.");
    $this->assertTrue(in_array($sub2, $actual), "Second subscriber on event is returned correctly.");
  }

  public function testRegister() {
    $event1 = 'event1';
    $event2 = 'event2';
    $events = array($event1, $event2);
    $mocked = array_merge($events, array('getEvents'));

    $subscriber = $this->getMockSubscriber($mocked);
    $subscriber->expects($this->once())
      ->method('getEvents')
      ->will($this->returnValue($events));

    $emitter = new EventEmitter();
    $emitter->register($subscriber);

    foreach ($events as $eventName) {
      $actual = $emitter->getSubscribersByEventName($eventName);
      $this->assertTrue(in_array($subscriber, $actual));
    }
  }
}

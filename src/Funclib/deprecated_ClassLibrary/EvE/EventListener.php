<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\ClassLibrary\Ifaces\EventOperation;

class EventListener {
	private $call_on_event;
	private static $instance;
	private function __construct() {
		$this->call_on_event = array ();
	}
	public static function getInstance() {
		if (empty ( EventListener::$instance ))
			EventListener::$instance = new EventListener ();
		return EventListener::$instance;
	}
	public function registerEventOperation(EventOperation $arg) {
		$this->call_on_event [] = $arg;
	}
	public function callOperations() {
		if (count ( $this->call_on_event ) > 0) {
			foreach ( $this->call_on_event as $event ) {
				$event->CallEventOperation ();
			}
		} else {
		}
	}
}

?>
<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\Viewport\Iface;

use zeradun\api_manager\includes\Ember\ClassLibrary\Viewport;

abstract class ViewingObj {
	private $viewport;
	function __construct($viewport) {
		if ($this->viewport instanceof Viewport) {
			$this->viewport = $viewport;
		} else
			throw new \Exception ( "Given Object must be a 'Viewport'" );
	}
	protected function getViewport() {
		if ($this->viewport instanceof Viewport) {
			return $this->viewport;
		} else
			return null;
	}
	protected function getContainer($container_content) {
	}
}

?>
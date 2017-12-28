<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\system;

use zeradun\api_manager\includes\Ember\ClassLibrary\system\sysObj;

abstract class ListItem extends sysObj {
	public abstract function isEqual(ListItem $listItem);
	public abstract function isGreater(ListItem $listItem);
	public abstract function isSmaller(ListItem $listItem);
}

?>
<?php
namespace Funclib\Useables;

use Funclib\Useables\sysObj;

abstract class EListItem extends sysObj {
	public abstract function isEqual(ListItem $listItem);
	public abstract function isGreater(ListItem $listItem);
	public abstract function isSmaller(ListItem $listItem);
}

?>
<?php
namespace Funclib\Useables;

use Funclib\Useables\sysObj;

abstract class EListItem extends sysObj {
    public abstract function isEqual(EListItem $listItem);
    public abstract function isGreater(EListItem $listItem);
    public abstract function isSmaller(EListItem $listItem);
}

?>
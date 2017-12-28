<?php
namespace Funclib\Useables;

abstract class sysObj {
	public function isSameInstance($obj, $throwException = 0) {
		if (! is_bool ( $throwException )) {
			throw new \Exception ( "No Boolean value given" );
		}
		try {
			$result = (( string ) get_class ( $this ) == ( string ) get_class ( $obj ));
			if ($throwException) {
				if ($result) {
					return true;
				} else {
					throw new \Exception ( "Wrong class type given" );
				}
			} else {
				return $result;
			}
			return;
		} catch ( \Exception $e ) {
			return false;
		}
	}
}

?>
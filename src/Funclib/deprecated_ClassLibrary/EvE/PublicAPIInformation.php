<?php

namespace zeradun\api_manager\includes\Ember\ClassLibrary\EvE;

use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\APICacheInfo;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\CharacterSelect;
use zeradun\api_manager\includes\Ember\ClassLibrary\EvE\APIManager;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\Database;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\ParseEngine;
use zeradun\api_manager\includes\Ember\ClassLibrary\system\FuncLib;

class PublicAPIInformation {
	private $api;
	private $character;
	private function __construct() {
		$this->api = CharacterSelect::getInstance ()->getAPIKey ();
		$this->character = CharacterSelect::getInstance ()->getCharacter ();
		$this->loadConquerableStations ();
	}
	private static $instance;
	
	/**
	 *
	 * @return PublicAPIInformation
	 */
	public static function getInstance() {
		if (empty ( PublicAPIInformation::$instance )) {
			PublicAPIInformation::$instance = new PublicAPIInformation ();
		}
		return PublicAPIInformation::$instance;
	}
	private function loadConquerableStations() {
		if (APICacheInfo::getInstance ()->isExpired ( "ConquerableStationList" )) {
			$result = APIManager::getInstance ()->getQuery ( $this->character, $this->api, "ConquerableStationList" );
			
			$sql = "SELECT * FROM ember.conquerablestations";
			$resource = Database::getInstance ()->sql_query ( $sql );
			$row = Database::getInstance ()->sql_fetch_array ( $resource );
			while ( ! empty ( $row ) ) {
				$stations [$row ['stationID']] = $row;
				$row = Database::getInstance ()->sql_fetch_array ( $resource );
			}
			
			$stations_res = $result->toArray ();
			$stations_res = $stations_res ['result'] ['outposts'];
			
			$sql = "";
			foreach ( $stations_res as $r ) {
				$r ['corporationName'] = FuncLib::sqlInjectionSafe ( $r ['corporationName'] );
				$r ['corporationName'] = preg_replace ( "/'/", "&#39", $r ['corporationName'] );
				$r ['stationName'] = FuncLib::sqlInjectionSafe ( $r ['stationName'] );
				$r ['stationName'] = preg_replace ( "/'/", "&#39", $r ['stationName'] );
				if (! empty ( $stations [$r ['stationID']] )) {
					$sql .= "UPDATE ember.conquerablestations SET
									stationName = '" . $r ['stationName'] . "',
									stationTypeID = " . $r ['stationTypeID'] . ",
									solarSystemID = " . $r ['solarSystemID'] . ",
									corporationID = " . $r ['corporationID'] . ",
									corporationName = '" . $r ['corporationName'] . "'
								WHERE stationID = " . $r ['stationID'] . ";\n";
				} else {
					$sql .= "INSERT INTO ember.conquerablestations
							 (stationID, stationName, stationTypeID, solarSystemID, corporationID, corporationName)
							VALUES (" . $r ['stationID'] . ",
									'" . $r ['stationName'] . "',
									" . $r ['stationTypeID'] . ",
									" . $r ['solarSystemID'] . ",
									" . $r ['corporationID'] . ",
									'" . $r ['corporationName'] . "');\n";
				}
			}
			$result = Database::getInstance ()->multiple_sql_query ( $sql );
		}
	}
}

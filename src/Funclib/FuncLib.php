<?php

namespace Funclib;


/**
 * *************************************************************
 * Copyright notice
 *
 * (c) 2007 Sebastian Winterhalder <zeradun@embin.ch>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 * *************************************************************
 */

// a newer Version to luck

/**
 * class.FuncLib
 *
 * @author Sebastian Winterhalder <zeradun@embin.ch>
 *
 *
 */

class FuncLib {
    const ARRAY_PREFIX = 'uName';
    const FORM_POST_PREFIX = 'MFFORM';
    
    /**
     * getUniqueFieldname($configData, $uName)
     *
     * @param Array $configData
     * @param String $uName
     * @return String
     */
    public static function getUniqueFieldname($configData, $uName = "") {
        if ($uName == "") {
            $uName = FuncLib::generateName ( $configData );
        }
        
        if (FuncLib::isUniqueFieldname ( $configData, $uName ))
            return $uName;
            else
                return FuncLib::getUniqueFieldname ( $configData, FuncLib::generateName ( $configData ) );
    }
    
    /**
     * generateName($configData)
     *
     * @param Array $configData
     * @return String
     */
    private static function generateName($configData) {
        $res = "F";
        
        $res .= FuncLib::generateRandomString ( 2 );
        $res .= rand ( 0, 9 );
        $res .= rand ( 0, 9 );
        $res .= rand ( 0, 9 );
        $res .= FuncLib::generateRandomString ( 3 );
        return $res;
    }
    
    /**
     * generateRandomString($length)
     *
     * @param int $length
     * @return String
     */
    private static function generateRandomString($length) {
        $chr = "";
        for($x = 0; $x < $length; $x ++) {
            $chr .= chr ( rand ( 66, 90 ) );
        }
        return $chr;
    }
    
    /**
     * Check if given field name is unique in array
     */
    public static function isUniqueFieldname($configData, $uName) {
        return FuncLib::existsXTimes ( $configData, $uName );
    }
    
    /**
     * Exists Time
     *
     * @param Array $configData
     * @param String $uName
     * @param int $times
     * @return int
     */
    public static function existsXTimes($configData, $uName, $times = 0) {
        $c = 0;
        if (! is_array ( $configData ))
            throw new \Exception ( 'Config Data is Empty, it should be an array' );
            
            foreach ( $configData as $page ) {
                foreach ( $page as $field ) {
                    if ($field ['uName'] == $uName) {
                        $c ++;
                    }
                }
            }
            return (intval ( $times ) == $c);
    }
    
    /**
     * shortenText($text, $len)
     *
     * @param String $text
     * @param Int $len
     * @return String
     */
    public static function shortenText($text, $len) {
        if (strlen ( $text ) > $len - 3) {
            $arr = array ();
            for($x = 0; $x < $len - 3; $x ++) {
                $arr [] = $text [$x];
            }
            return implode ( $arr ) . "...";
        } else
            return $text;
    }
    
    /**
     * Remove Quotationmark
     *
     * @param String $string
     * @param String $quote
     * @return String
     */
    public static function removeQuotationmark($string, $quote = '"') {
        return str_replace ( $quote, '\'', $string );
    }
    
    /**
     * Get the field Object with unique Field ID
     *
     * @param $ufid String
     *        	Field ID
     * @param $objArray Array
     *        	Object Fields
     * @return Object
     */
    public static function getFieldObject($ufid, $objArray) {
        foreach ( $objArray as $obj ) {
            if ($obj->getForm ()->getUFID () == $ufid) {
                return $obj;
            }
        }
    }
    
    /**
     * Convert an array to an CSV String
     *
     * @param Array $array
     * @return String
     */
    public static function convertToCSV($array) {
        if (is_array ( $array ))
            return implode ( ',', $array );
            else
                return '';
    }
    
    /**
     * Convert a CSV String to an array
     *
     * @param String $csv
     * @return Array
     */
    public static function convertFromCSV($csv) {
        if ($csv == "")
            return array ();
            return explode ( ',', $csv );
    }
    
    /**
     * Enter description here...
     *
     * @param Array $array
     * @param String $assocKey
     * @param Boolean $asc
     * @return Array
     */
    public static function insertionSort($array, $assocKey, $asc = true) {
        $i = $j = $index = 0;
        for($i = 1; $i < count ( $array ); $i ++) {
            $index = $array [$i];
            $j = $i;
            while ( $j > 0 && FuncLib::isStringGreaterThan ( $array [$j - 1] [$assocKey], $index [$assocKey], $asc ) ) {
                $array [$j] = $array [$j - 1];
                $j = $j - 1;
            }
            $array [$j] = $index;
        }
        return $array;
    }
    
    /**
     * Enter description here...
     *
     * @param String $string1
     * @param String $string2
     * @param Boolean $asc
     * @return Boolean
     */
    public static function isStringGreaterThan($string1, $string2, $asc = true) {
        for($x = 0; $x < strlen ( $string1 ); $x ++) {
            if ($asc) {
                if (ord ( $string1 [$x] ) > ord ( $string2 [$x] ))
                    return true;
                    elseif (ord ( $string1 [$x] < ord ( $string2 [$x] ) ))
                    return false;
                    return true;
            } else {
                if (ord ( $string1 [$x] ) < ord ( $string2 [$x] ))
                    return true;
                    elseif (ord ( $string1 [$x] > ord ( $string2 [$x] ) ))
                    return false;
                    return true;
            }
        }
        return true;
    }
    
    /**
     * Sort an array with the index
     *
     * @param Array $array
     * @return Array
     */
    public static function sortArrayWithAscIndex($array) {
        $r1 = array ();
        
        foreach ( $array as $key => $value ) {
            if (! is_array ( $value ))
                $r1 [] = $value;
                else
                    $r1 [] = FuncLib::sortArrayWithAscIndex ( $value );
        }
        
        $res = $r1;
        return $res;
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $array
     * @param unknown_type $current
     * @param unknown_type $value
     * @param unknown_type $afterCurrent
     * @return unknown
     */
    public static function insertIntoArray($array, $current, $value, $afterCurrent = true) {
        return $array;
    }
    public static function sqlInjectionSafe($string) {
        if (is_string ( $string ))
            return $string = preg_replace ( "((DELETE)|(SELECT)|(INPUT)|(DROP))", "", $string );
    }
    
    /**
     * Delete an Index from the wanted array, and move the values
     * Works only with integer as index
     *
     * @param unknown_type $array
     * @param unknown_type $index
     * @return unknown
     */
    public static function removeFromArray($array, $index) {
        if (! is_array ( $array ))
            throw new \Exception ( "Array Excepted" );
            
            for($x = $index; $x < count ( $array ) - 1; $x ++) {
                $field = $array [$x + 1];
                foreach ( $field as $row ) {
                    foreach ( $row as $col ) {
                        $col->setPage ( $x );
                    }
                }
                $array [$x] = $field;
            }
            
            unset ( $array [count ( $array ) - 1] );
            
            return $array;
    }
    
    /**
     * get the current charset of the typo3 system
     *
     * @return String
     */
    public static function getTypo3Charset() {
        if ($GLOBALS ['TYPO3_CONF_VARS'] ['BE'] ['forceCharset']) { // First priority: forceCharset! If set, this will be authoritative!
            $charset = $GLOBALS ['TYPO3_CONF_VARS'] ['BE'] ['forceCharset'];
        } elseif (is_object ( $GLOBALS ['LANG'] )) {
            $charset = $GLOBALS ['LANG']->charSet; // If "LANG" is around, that will hold the current charset
        } else {
            $charset = 'iso-8859-1'; // THIS is just a hopeful guess!
        }
        
        return $charset;
    }
    
    /**
     * Enter description here...
     *
     * @return String
     */
    public static function encodeUTF8($string) {
        // Check if the non-default php module mbstring is loaded
        // If not available, just dont change the input
        $loaded_ext = get_loaded_extensions ();
        if (array_search ( 'mbstring', $loaded_ext ) !== false) {
            if (mb_detect_encoding ( $string ) == "UTF-8") {
                return $string; // Return the string, its already encoded
            } else {
                return utf8_encode ( $string ); // Encode the string and return
            }
        } else {
            return $string; // mbstring extension is not loaded, return it back
        }
    }
    
    /**
     * detect encoding of a given string
     * Returns false if cannot detect encoding
     *
     * @param
     *        	String
     *
     * @return Mixed
     */
    public static function detect_encoding($string) {
        // Check if the non-default php module mbstring is loaded
        // If not available, just dont change the input
        $loaded_ext = get_loaded_extensions ();
        if (array_search ( 'mbstring', $loaded_ext ) !== false) {
            $result = mb_detect_encoding ( $string );
            return $result;
        } else {
            return false; // mbstring extension is not loaded, return false
        }
        return false;
    }
    
    /**
     * change the charset of a string
     *
     * @param String $str
     * @param String $charset
     */
    public static function set_encoding($str, $charset) {
        $input_charset = FuncLib::detect_encoding ( $str );
        if ($input_charset == $charset || $input_charset == 'ASCII') {
            return $str;
        }
        $result = @iconv ( $input_charset, $charset . "", $str );
        if ($input_charset != false && $result != false) {
            return $result;
        } else {
            return false;
        }
    }
    
    /**
     * set the correct charset depending of system charset
     *
     * @param String $string
     * @return String
     */
    public static function set_correct_charset($string) {
        if (strtolower ( FuncLib::detect_encoding ( $string ) ) == 'utf-8') {
            if (strtolower ( FuncLib::getTypo3Charset () ) == 'utf-8') {
                return $string;
            } else {
                return utf8_decode ( $string );
            }
        } else {
            return FuncLib::encodeUTF8 ( $string );
        }
    }
    
    public static function is_not_empty($tmp) {
        if ($tmp != "") {
            return true;
        } else {
            return false;
        }
    }
    public static function is_longer_than_x($tmp, $x) {
        if (strlen ( $tmp ) > $x)
            return True;
            else
                return False;
    }
    public static function is_valid_password($tmp) {
        // checks if a string is valid for a password
        // that means: at least 5 characters which are prntable (ASCII 20 - 7E)
        if (preg_match ( "#^[\x20-\x7E]{5,}$#", $tmp )) {
            return true;
        } else {
            return false;
        }
    }
    public static function contains_word_character($tmp) {
        // checks if a string contains at least 1 word character
        if (preg_match ( '#([\w]{1})#', $tmp )) {
            return true;
        } else {
            return false;
        }
    }
    public static function contains_3_word_characters($tmp) {
        // checks if a string contains at least 3 word characters
        if (preg_match ( '#([\w]{1})(.*?)([\w]{1})(.*?)([\w]{1})#s', $tmp )) {
            return true;
        } else {
            return false;
        }
    }
    public static function contains_digit($tmp) {
        // checks if a string contains at least 1 digit
        if (preg_match ( '#([\d]{1})#', $tmp )) {
            return true;
        } else {
            return false;
        }
    }
    public static function contains_word_character_or_digit($tmp) {
        // checks if a string contains at least 1 word character or digit
        if (preg_match ( '#([\d\w]{1})#', $tmp )) {
            return true;
        } else {
            return false;
        }
    }
    public static function is_valid_email($tmp) {
        if (preg_match ( '^[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+$^', $tmp ) && strlen ( $tmp ) <= 80) {
            return true;
        } else {
            return false;
        }
    }
    public static function is_valid_url($tmp) {
        if (preg_match ( '#^(http|news|https|ftp|aim)://#i', $tmp ) && strlen ( $tmp ) <= 255) {
            return true;
        } else {
            return false;
        }
    }
    
    public static function makePostInputSafe($post) {
        return htmlspecialchars ( FuncLib::sqlInjectionSafe ( $post ) );
    }
    
    /**
     * create a random string with defined length
     * @param number $length
     * @return string
     */
    public static function createRandomString($length = 10) {
        $shuffle = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUFVXYZ1234567890$!?=-_.,:;";
        $str_len = strlen($shuffle)-1;
        $string = "";
        for ($x = 0; $x < $length; $x++) {
            $string .= $shuffle[rand(0, $str_len)];
        }
        return $string;
    }
}

?>
<?php
namespace Funclib\Ifaces;

interface ExceptionMessageConverter {
    
    /**
     * Implement a Method, that allows to convert a complex exception of given type to a simple string message
     * @param \Exception $e
     * @return string
     */
    public function ConvertExceptionMessage(\Exception $e) : string;
    
}

?>
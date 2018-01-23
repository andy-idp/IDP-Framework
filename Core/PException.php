<?php

namespace Core;

use \ErrorException;

class PException extends ErrorException {

    /**
     * @access public
     * @return string
     */
    public function __toString() {
        return $this->message;
    }

    /**
     * @access public
     * @return string
     */
    public function getType() {
        switch ($this->severity) {
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_CORE_WARNING:
            case E_COMPILE_ERROR:
            case E_COMPILE_WARNING:
            case E_USER_ERROR:
                return "Fatal error";
                break;

            case E_WARNING:
            case E_USER_WARNING:
                return "Warning";
                break;

            case E_NOTICE:
            case E_USER_NOTICE:
                return "Note";
                break;

            case E_STRICT:
                return "Syntaxe Obsolète";
                break;

            default:
                return "Unknown error";
                break;
        }
    }

    /**
     * @access public
     * @return string
     */
    public function getErreurLog() {
        return $this->getType() . ' [' . $this->severity . '] : "' . $this->message . '" - ' . $this->file . ' à la ligne ' . $this->line;
    }

}

?>

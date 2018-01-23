<?php
set_error_handler('error2exception');
register_shutdown_function('errorfatal');
set_exception_handler('customException');

function error2exception($severite, $message, $fichier, $ligne)
{
    customException(new \Core\PException($message, 0, $severite, $fichier, $ligne));
}

function errorfatal()
{
    if (is_array($e = error_get_last())) {
        $severite = isset($e['type']) ? $e['type'] : E_USER_ERROR;
        $message  = isset($e['message']) ? $e['message'] : '';
        $fichier  = isset($e['file']) ? $e['file'] : '';
        $ligne    = isset($e['line']) ? $e['line'] : '';
        if ($severite > 0) {
            customException(new \Core\PException($message, 0, $severite, $fichier, $ligne));
        }
    }
}

function customException($e)
{
    if (get_class($e) != "core\PException") {
        $e = new \Core\PException("|" . strtoupper(get_class($e)) . "| " . $e->getMessage(), 0, E_USER_ERROR, $e->getFile(), $e->getLine());
    }

    if ($e->getSeverity() == E_ERROR || $e->getSeverity() == E_PARSE || $e->getSeverity() == E_USER_ERROR || $e->getSeverity() == E_COMPILE_ERROR || $e->getSeverity() == E_CORE_ERROR) {
        header('Content-Type: text/html; charset=utf-8');
        echo "<strong>Error :</strong> " . $e;
    }

    if ($handle = fopen(__DIR__ . "/../logs/logs_" . date("m-Y") . ".txt", 'a')) {
        fwrite($handle, "[" . date("d-m-Y H:i:s") . " " . $_SERVER["REMOTE_ADDR"] . "] " . $e->getErreurLog() . "\n");
        fclose($handle);
    }
}

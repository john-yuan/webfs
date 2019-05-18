<?php

set_exception_handler(function ($exception) {
    $error = array();
    $errorStack = array();
    $traceStack = array();
    $code = $exception->getCode();
    $message = $exception->getMessage();

    while ($exception) {
        $error_info = array(
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'message' => $exception->getMessage()
        );

        array_push($errorStack, $error_info);
        array_push($traceStack, preg_split('/[\r\n]+/', $exception->getTraceAsString()));

        $exception = $exception->getPrevious();
    }

    $error['type'] = 'exception';
    $error['time'] = date('Y-m-d H:i:s');
    $error['errorStack'] = $errorStack;
    $error['traceStack'] = $traceStack;

    $date = date('Y-m-d');
    $store = store('error/error-' . $date);
    $store->lock();
    $errors = $store->get('errors', array());
    array_push($errors, $error);
    $store->set('errors', $errors);
    $store->unlock();

    unset($error['type']);
    unset($error['time']);
    unset($error['traceStack']);

    http()->error('ERR_UNCAUGHT_SYSTEM_ERROR', $message, $error);
});

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    $error = array(
        'type' => 'error',
        'time' => date('Y-m-d H:i:s'),
        'errno' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errfile
    );

    $date = date('Y-m-d');
    $store = store('error/error-' . $date);
    $store->lock();
    $errors = $store->get('errors', array());
    array_push($errors, $error);
    $store->set('errors', $errors);
    $store->unlock();
});

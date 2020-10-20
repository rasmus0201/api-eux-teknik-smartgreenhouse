<?php

if (!function_exists('dd')) {
    function dd()
    {
        echo '<pre>';
        foreach (func_get_args() as $arg) {
            var_dump($arg);
        }
        echo '</pre>';
        die;
    }
}

if (!function_exists('sanitize_input')) {
    function sanitize_input($input)
    {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input);

        return $input;
    }
}

if (!function_exists('abort')) {
    function abort($code, $message = 'Not found')
    {
        http_response_code($code);
        die($message);
    }
}

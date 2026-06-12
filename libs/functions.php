<?php

use CMS\Lang;

if (!function_exists('__')) {
    function __(string $key, mixed ...$args): string
    {
        return Lang::get($key, ...$args);
    }
}

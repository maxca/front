<?php
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;

if (!function_exists('getCookieName')) {
    function getCookieName($key)
    {
        return isset($_COOKIE[$key]) ? Crypt::decrypt($_COOKIE[$key]) : false;
    }
}

if (!function_exists('getBearer')) {
    function getBearer($tokenName)
    {
        $token = getCookieName($tokenName);
        return !empty($token) ? "Bearer {$token}" : '';

    }
}

if (!function_exists('setCookieShared')) {
    function setCookieShared($name, $data, $time = '')
    {
        $time = !empty($time) ? $time : env('EXPIRE_TIME', 1440);
        return Cookie::queue($name, $data, $time, null, env('COOKIE_DOMAIN'));
    }
}

if (!function_exists('forgetCookieShared')) {
    function forgetCookieShared($name, $path = '/')
    {
        return Cookie::forget($name, $path, env('COOKIE_DOMAIN'));
    }
}

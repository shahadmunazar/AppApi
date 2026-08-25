<?php

use Carbon\Carbon;

if (!function_exists('businessDate')) {
    function businessDate($datetime = null)
    {
        $time = $datetime
            ? Carbon::parse($datetime)->timezone('Asia/Kolkata')
            : Carbon::now('Asia/Kolkata');

        if ($time->format('H:i:s') < '03:21:00') {
            $time->subDay();
        }

        return $time->toDateString();
    }
}

if (!function_exists('businessStart')) {
    function businessStart($datetime = null)
    {
        return businessDate($datetime) . ' 03:21:00';
    }
}

if (!function_exists('businessEnd')) {
    function businessEnd($datetime = null)
    {
        return Carbon::parse(businessDate($datetime))->addDay()->format('Y-m-d') . ' 03:20:59';
    }
}

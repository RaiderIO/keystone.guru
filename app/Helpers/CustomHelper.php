<?php

/**
 * Checks if a specific alert is already dismissed and thus should not be rendered anymore.
 *
 * @param $id int The ID of the alert
 * @return bool True if the user dismissed it, false if they did not yet.
 */
function isAlertDismissed($id)
{
    return isset($_COOKIE['alert-dismiss-' . $id]);
}

/**
 * @return string A random hex color.
 */
function randomHexColor(): string
{
    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}

/**
 * Get the initials from a name
 * @param $name string
 * @return string
 */
function initials(string $name): string
{
    $explode = explode(' ', $name);
    if (count($explode) > 1) {
        $result = join('', array_map(function ($element)
        {
            return $element[0];
        }, $explode));
    } else {
        $result = substr($name, 0, 2);
    }

    return strtoupper($result);
}
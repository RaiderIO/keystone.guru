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
function randomHexColor()
{
    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}
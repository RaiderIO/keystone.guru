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
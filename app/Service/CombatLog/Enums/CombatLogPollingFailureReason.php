<?php

namespace App\Service\CombatLog\Enums;

/**
 * The ways a polled Raider.IO run can fail to yield combat log data. Individually none of these are
 * worth reporting - they are the normal background noise of an upstream API that occasionally 502s
 * and of players uploading logs we cannot parse - so they are counted per hour by
 * CombatLogPollingHealthServiceInterface and only reported on in bulk (#4173).
 */
enum CombatLogPollingFailureReason: string
{
    /** Raider.IO answered the search API with something that isn't a run listing (typically a Cloudflare 5xx page). */
    case SearchApiError = 'search_api_error';

    /**
     * Raider.IO yielded no usable combat log segments for a polled run - not uploaded yet, an API
     * error, or an empty segments array. Which of the three it was stays in the logs; for the volume
     * signal this aggregate is, they are the same thing: a run that gave us nothing.
     */
    case SegmentsUnavailable = 'segments_unavailable';

    /**
     * The job gave up after exhausting its retries. Chiefly a segment that could not be downloaded or
     * wasn't a combat log at all, but also whatever infrastructure blip took the last attempt down.
     */
    case RunFailedAfterRetries = 'run_failed_after_retries';

    /** The combat log downloaded fine, but a line in it could not be parsed. */
    case ParseFailed = 'parse_failed';
}

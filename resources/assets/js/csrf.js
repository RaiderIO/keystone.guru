/**
 * CSRF token resolution for jQuery ajax requests.
 *
 * Laravel refreshes the encrypted `XSRF-TOKEN` cookie on every response, so reading it
 * per-request always yields a token valid for the *current* session - even after the session
 * was rotated in another tab (re-login) or expired. This replaces the static
 * `<meta name="csrf-token">` value, which goes stale on long-open tabs and surfaced as 419
 * "page expired" errors on the next ajax write. See issue #3452 (follow-up to #3391).
 *
 * The cookie holds the *encrypted* token; Laravel's `VerifyCsrfToken` decrypts the
 * `X-XSRF-TOKEN` header (js-cookie url-decodes the raw cookie value for us, exactly as axios
 * does). The meta tag holds the *raw* token and is only used as a fallback - sent as
 * `X-CSRF-TOKEN` - when the cookie is unavailable.
 *
 * The two headers must never be sent together: `VerifyCsrfToken::getTokenFromRequest()`
 * consults `X-CSRF-TOKEN` first and ignores `X-XSRF-TOKEN` when it is present, which would
 * re-introduce the stale-token problem. Hence this returns a single header/value pair.
 */

/**
 * Resolve the CSRF token to send with the next ajax request, preferring the always-fresh
 * cookie over the potentially-stale meta tag.
 *
 * @returns {{header: string, value: string}|null}
 */
function getCsrfToken() {
    const cookieToken = (typeof Cookies !== 'undefined' && Cookies !== null)
        ? Cookies.get('XSRF-TOKEN')
        : null;
    if (cookieToken) {
        return {header: 'X-XSRF-TOKEN', value: cookieToken};
    }

    const metaToken = $('meta[name="csrf-token"]').attr('content');
    if (metaToken) {
        return {header: 'X-CSRF-TOKEN', value: metaToken};
    }

    return null;
}

module.exports = {getCsrfToken};

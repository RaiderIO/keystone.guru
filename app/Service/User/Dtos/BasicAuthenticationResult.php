<?php

namespace App\Service\User\Dtos;

/**
 * Why an incoming request could (not) be authenticated from its `Authorization: Basic` header.
 *
 * Everything but Success answers a 401 with the same opaque body, so this exists purely so the
 * failure can be logged specifically - "the header never arrived" and "the password was wrong"
 * are diagnosed in completely different places.
 */
enum BasicAuthenticationResult: string
{
    case Success = 'success';

    /** The request carried no Authorization header at all - it was never sent, or stripped in transit. */
    case MissingHeader = 'missing_header';

    /** An Authorization header was sent, but not a Basic one. */
    case UnsupportedScheme = 'unsupported_scheme';

    /** A Basic header was sent, but it did not decode into a non-empty username and password. */
    case MalformedCredentials = 'malformed_credentials';

    /** Well-formed credentials that do not belong to any user, or do not match that user's password. */
    case CredentialsRejected = 'credentials_rejected';
}

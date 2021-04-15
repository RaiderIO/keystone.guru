@extends('layouts.sitepage', ['showLegalModal' => false, 'title' => __('Cookies')])

@section('header-title', __('Cookies Policy'))

@section('content')
    <p>
        This website, https://keystone.guru/ (the "Website"), is operated by Keystone Guru.
    </p>

    <h5>
        What are cookies?
    </h5>
    <p>
        Cookies are a small text files that are stored in your web browser that allows Keystone Guru or a third party to
        recognize you. Cookies can be used to collect, store and share bits of information about your activities across
        websites, including on Keystone Guru website.
    </p>

    <p>
        Cookies might be used for the following purposes:
    </p>

    <ul>
        <li>To enable certain functions</li>
        <li>To provide analytics</li>
        <li>To store your preferences</li>
        <li>To enable ad delivery and behavioral advertising</li>
        <li>Keystone Guru uses both session cookies and persistent cookies.</li>
    </ul>

    <p>
        A session cookie is used to identify a particular visit to our Website. These cookies expire after a short time,
        or when you close your web browser after using our Website. We use these cookies to identify you during a single
        browsing session, such as when you log into our Website.
    </p>

    <p>
        A persistent cookie will remain on your devices for a set period of time specified in the cookie. We use these
        cookies where we need to identify you over a longer period of time. For example, we would use a persistent
        cookie if you asked that we keep you signed in.
    </p>

    <h5>
        How do third parties use cookies on the Keystone Guru Website?
    </h5>
    <p>
        Third party companies like analytics companies and ad networks generally use cookies to collect user information
        on an anonymous basis. They may use that information to build a profile of your activities on the Keystone Guru
        Website and other websites that you've visited.
    </p>

    <h5>
        What are your cookies options?
    </h5>
    <p>
        If you don't like the idea of cookies or certain types of cookies, you can change your browser's settings to
        delete cookies that have already been set and to not accept new cookies. To learn more about how to do this,
        visit the help pages of your browser.
    </p>

    <p>
        Please note, however, that if you delete cookies or do not accept them, you might not be able to use all of the
        features we offer, you may not be able to store your preferences, and some of our pages might not display
        properly.
    </p>

    <h5>
        Exact cookies placed for functionality on the site
    </h5>
    <p>
        Removal of any of the following cookies will cause certain features to stop working.
    <table width="100%">
        <tr>
            <th>
                Cookie name
            </th>
            <th>
                Purpose
            </th>
        </tr>
        <tr>
            <td>
                __cfduid
            </td>
            <td>
                Uniquely identifies you as a secure user in an insecure environment, for more information visit the
                <a href="https://support.cloudflare.com/hc/en-us/articles/200170156-What-does-the-Cloudflare-cfduid-cookie-do-">
                    <i class="fas fa-external-link"></i> Cloudflare Support Article</a>.
            </td>
        </tr>
        <tr>
            <td>
                PHPSESSID
            </td>
            <td>
                Your session when logged into the website. Removal of this cookie causes you to be logged out
                immediately. Anyone having access to the value of this cookie will be instantly logged in as you.
                Never share this value with anyone. Blocking this cookie causes you to not be able to log in.
            </td>
        </tr>
        <tr>
            <td>
                XSRF-TOKEN
            </td>
            <td>
                Prevents
                <a href="https://en.wikipedia.org/wiki/Cross-site_request_forgery">
                    <i class="fas fa-external-link"></i> Cross-site request forgery</a>.
            </td>
        </tr>
        <tr>
            <td>
                alert-dismiss-[string]
            </td>
            <td>
                Keeps track of which dialogs you've dismissed. When set, a specific dialog you've dismissed will no
                longer keep showing up.
            </td>
        </tr>
        <tr>
            <td>
                changelog_release
            </td>
            <td>
                Keeps track of the latest release you've viewed so we can show the NEW keyword next to the changelog in
                the header when there's a new release.
            </td>
        </tr>
        <tr>
            <td>
                viewed_teams
            </td>
            <td>
                Keeps track of whether you've viewed the Teams feature so we can show the NEW keyword next to it until
                you have.
            </td>
        </tr>
        <tr>
            <td>
                cookieconsent_status
            </td>
            <td>
                Keeps track of whether to show you your rights for cookies or not (the "cookie bar").
            </td>
        </tr>
        <tr>
            <td>
                io
            </td>
            <td>
                This cookie is set whenever you use the collaborative route editing feature when using Teams.
            </td>
        </tr>
        <tr>
            <td>
                laravel_session
            </td>
            <td>
                Placed when you log into the site and keeps you logged in when you selected "remember me".
            </td>
        </tr>
        <tr>
            <td>
                polyline_default_color
            </td>
            <td>
                Remembers the color you set when changing the color of a polyline in the map editor.
            </td>
        </tr>
        <tr>
            <td>
                polyline_default_weight
            </td>
            <td>
                Remembers the stroke width you set when changing the stroke width of a polyline in the map editor.
            </td>
        </tr>
        <tr>
            <td>
                pull_gradient
            </td>
            <td>
                Remembers the gradient that you've selected for your pulls in the map editor.
            </td>
        </tr>
        <tr>
            <td>
                pull_gradient_apply_always
            </td>
            <td>
                Remembers if you want the gradient that you've selected in the map editor to be applied automatically when performing changes to your pull.
            </td>
        </tr>
        <tr>
            <td>
                enemy_display_type
            </td>
            <td>
                Remembers the enemy display type you selected in the sidebar so it can persist across sessions.
            </td>
        </tr>
        <tr>
            <td>
                routes_viewmode
            </td>
            <td>
                Keeps track of whether to display the Routes page in "biglist" or "list" mode upon navigation.
            </td>
        </tr>
        <tr>
            <td>
                hidden_map_object_groups
            </td>
            <td>
                Remembers the what map elements are hidden from view in the map.
            </td>
        </tr>
        <tr>
            <td>
                kill_zones_number_style
            </td>
            <td>
                Remembers if you want to display your pulls in percentages or enemy forces.
            </td>
        </tr>
        <tr>
            <td>
                map_number_style
            </td>
            <td>
                Remembers if you want to display enemies on the map in percentages or enemy forces.
            </td>
        </tr>
        <tr>
            <td>
                map_unkilled_enemy_opacity
            </td>
            <td>
                Remembers the opacity of unkilled enemies.
            </td>
        </tr>
        <tr>
            <td>
                map_unkilled_important_enemy_opacity
            </td>
            <td>
                Remembers the opacity of unkilled important enemies.
            </td>
        </tr>
        <tr>
            <td>
                map_enemy_aggressiveness_border
            </td>
            <td>
                Remembers if you want to show an aggressiveness border around enemies on the map view or not.
            </td>
        </tr>
        <tr>
            <td>
                map_enemy_dangerous_border
            </td>
            <td>
                Remembers if you want to show a dangerous border around enemies on the map view or not.
            </td>
        </tr>
        <tr>
            <td>
                theme
            </td>
            <td>
                Remembers the theme you wish to have the website in.
            </td>
        </tr>

    </table>
    </p>

    <h5>
        Where can I find more information about cookies?
    </h5>
    <p>
        You can learn more about cookies by visiting the following third party websites:

    <ul>
        <li>
            <a href="https://cookiesandyou.com">Cookiesandyou.com <i class="fas fa-external-link-alt"></i></a>
        </li>
        <li>
            <a href="https://policies.google.com/technologies/cookies?hl=en">How Google uses Cookies <i
                        class="fas fa-external-link-alt"></i></a>
        </li>
    </ul>


    </p>

    This Cookies Policy was created by cookiespolicytemplate.com for https://keystone.guru/
@endsection
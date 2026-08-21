<?php

return [

    'edittabs' => [
        'details' => [
            'title' => 'Teamdetails',
        ],
        'members' => [
            'title'                                      => 'Mitglieder',
            'invite_new_members'                         => 'Neue Mitglieder einladen',
            'invite_code_share_warning'                  => 'Sei vorsichtig, mit wem du den Einladungscode teilst, jeder mit dem Link kann deinem Team beitreten!',
            'copy_to_clipboard_title'                    => 'In die Zwischenablage kopieren',
            'refresh_invite_link_title'                  => 'Einladungslink erneuern',
            'default_role'                               => 'Standardrolle',
            'ad_free_giveaway_description_not_available' => 'Mit einem :patreon-Abonnement kannst du bis zu :max Teammitgliedern werbefreien Zugang zu Keystone.guru schenken.',
            'ad_free_giveaway_description_available'     => 'Danke, dass du :patreon von Keystone.guru abonniert hast! Du kannst :current weiteren Teammitgliedern werbefreien Zugang zu Keystone.guru schenken.',
        ],
        'overview' => [
            'title'   => 'Übersicht',
            'routes'  => 'Routen',
            'members' => 'Mitglieder',
        ],
        'routepublishing' => [
            'title'            => 'Routenveröffentlichung',
            'description'      => 'Mit der Routenveröffentlichung kannst du Routen zeitgesteuert mit der Welt teilen. Du kannst pro Route ein Datum und eine Uhrzeit festlegen, zu der sie automatisch in den Status Veröffentlicht versetzt und für alle sichtbar wird.',
            'enabled'          => 'Aktiviert',
            'timezone_warning' => 'Geplante Veröffentlichungszeiten verwenden die Zeitzone deines Profils. Stelle sicher, dass deine Zeitzone in deinen :link korrekt eingestellt ist.',
            'profile_link'     => 'Profileinstellungen',
        ],
        'routes' => [
            'title'                  => 'Routenliste',
            'add_route'              => 'Route hinzufügen',
            'add_route_no_moderator' => 'Du musst Moderator dieses Teams sein, um Routen hinzuzufügen',
            'stop_adding_routes'     => 'Hinzufügen von Routen beenden',
        ],
        'tags' => [
            'title'       => 'Tags',
            'description' => 'Du kannst hier Tags für die Routen des Teams verwalten. Jeder, der Mitglied dieses Teams ist, kann die an die Routen angehängten Tags anzeigen.
                                    Die persönlichen Tags, die möglicherweise vom Routeninhaber angehängt wurden, werden nicht sichtbar sein.',
        ],
    ],
    'edit' => [
        'title'          => 'Team %s',
        'menu_title'     => 'Teams',
        'to_team_list'   => 'Teamliste',
        'team_header'    => 'Team %s',
        'icon_image_alt' => 'Kein Bild',
        'routes'         => 'Routen',
        'members'        => 'Mitglieder',
    ],
    'invite' => [
        'linkpreview_title'           => 'Einladung, dem Team %s beizutreten',
        'linkpreview_description'     => 'Du wurdest eingeladen, dem Team %s beizutreten. Melde dich bei Keystone.guru an oder registriere dich, um dem Team beizutreten, es ist kostenlos!',
        'title'                       => 'Einladung, dem Team %s beizutreten',
        'header'                      => 'Einladung, dem Team %s beizutreten',
        'invalid_team'                => 'Ungültiges Team',
        'logo_image_alt'              => 'Team-Logo',
        'already_a_member'            => 'Du bist bereits Mitglied des Teams %s!',
        'invited_to_join'             => 'Du wurdest eingeladen, dem Team %s beizutreten.',
        'accept_the_invitation'       => 'Nimm die Einladung an, dem Team beizutreten!',
        'login_or_register_to_accept' => 'Melde dich bei Keystone.guru an oder registriere dich, um dem Team beizutreten, es ist kostenlos!',
        'return_to_team'              => 'Zum Team zurückkehren',
        'accept_invitation'           => 'Einladung annehmen',
        'login'                       => 'Anmelden',
        'register'                    => 'Registrieren',
        'invite_not_found'            => 'Dieses Team konnte nicht gefunden werden. Vielleicht wurde der Einladungslink geändert oder das Team wurde gelöscht.',
        'back_to_homepage'            => 'Zurück zur Startseite',
    ],
    'list' => [
        'title'                => 'Meine Teams',
        'header'               => 'Meine Teams',
        'create_team'          => 'Team erstellen',
        'table_header_team'    => 'Team',
        'table_header_members' => 'Mitglieder',
        'table_header_routes'  => 'Routen',
    ],
    'new' => [
        'title'  => 'Neues Team',
        'header' => 'Neues Team',
    ],

];

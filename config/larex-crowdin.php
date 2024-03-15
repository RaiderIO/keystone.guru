<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Crowdin API Token
     |--------------------------------------------------------------------------
     |
     | Enter your Crowdin Personal Access Token here.
     |
     | Standard account
     | You can generate your token at this url:
     | https://crowdin.com/settings#api-key
     | Please note: this library supports only the Crowdin API v2
     |
     | Enterprise account:
     | You can generate your token at this url:
     | https://<your-organization-name>.crowdin.com/u/user_settings/access-tokens
     |
     */

    'token' => env('LAREX_CROWDIN_TOKEN'),

    /*
     |--------------------------------------------------------------------------
     | Crowdin Project ID
     |--------------------------------------------------------------------------
     |
     | Enter your Crowdin Project ID here.
     |
     | Standard account
     | You can get your project id (API v2) at this url:
     | https://crowdin.com/project/<your-project>/tools/api
     |
     | Enterprise account
     | You can get your project id at this url:
     | https://<your-organization-name>.crowdin.com/u/projects/<your-project-id>#home
     |
     */

    'project_id' => (int)env('LAREX_CROWDIN_PROJECT_ID'),

    /*
     |--------------------------------------------------------------------------
     | Crowdin Organization Name
     |--------------------------------------------------------------------------
     |
     | Optional.
     | Enter ONLY your Crowdin Organization Name here.
     | This is required only for Enterprise account.
     | https://<your-organization-name>.crowdin.com
     |                ^^^this^^^
     | If you are using a standard account, leave this field blank.
     |
     */

    'organization' => env('LAREX_CROWDIN_ORGANIZATION'),
];

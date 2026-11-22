<?php

return [
    'authorize' => [
        'title'        => '애플리케이션 승인',
        'heading'      => ':client에서 Keystone.guru 계정에 액세스하려고 합니다',
        'description'  => ':user(으)로 로그인되어 있습니다. 승인하면 :client에서 사용자를 대신하여 Keystone.guru API를 사용할 수 있습니다.',
        'scopes_intro' => '다음 권한을 요청합니다:',
        'revoke_hint'  => '프로필의 API 토큰 탭에서 언제든지 이 액세스를 취소할 수 있습니다.',
        'approve'      => '승인',
        'deny'         => '거부',
    ],
];

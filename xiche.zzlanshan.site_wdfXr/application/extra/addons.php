<?php

return [
    'autoload' => false,
    'hooks' => [
        'epay_config_init' => [
            'epay',
        ],
        'addon_action_begin' => [
            'epay',
        ],
        'action_begin' => [
            'epay',
        ],
        'app_init' => [
            'qrcode',
            'xiluxc',
        ],
        'config_init' => [
            'summernote',
        ],
        'user_sidenav_after' => [
            'xiluxc',
        ],
        'xiluxc_shop_user' => [
            'xiluxc',
        ],
        'xiluxc_vip_calculate' => [
            'xiluxc',
        ],
        'xiluxc_service_calculate' => [
            'xiluxc',
        ],
        'xiluxc_add_score' => [
            'xiluxc',
        ],
        'xiluxc_reduce_score' => [
            'xiluxc',
        ],
        'xiluxc_recharge_success' => [
            'xiluxc',
        ],
        'xiluxc_money_pay' => [
            'xiluxc',
        ],
        'xiluxc_refund_success' => [
            'xiluxc',
        ],
        'xiluxc_withdraw' => [
            'xiluxc',
        ],
        'xiluxc_withdraw_refuse' => [
            'xiluxc',
        ],
        'xiluxc_shop_withdraw' => [
            'xiluxc',
        ],
        'xiluxc_shop_withdraw_refuse' => [
            'xiluxc',
        ],
        'xiluxc_service_buy_message' => [
            'xiluxc',
        ],
        'xiluxc_package_buy_message' => [
            'xiluxc',
        ],
        'xiluxc_service_verifier_message' => [
            'xiluxc',
        ],
        'xiluxc_package_verifier_message' => [
            'xiluxc',
        ],
    ],
    'route' => [
        '/qrcode$' => 'qrcode/index/index',
        '/qrcode/build$' => 'qrcode/index/build',
    ],
    'priority' => [],
    'domain' => '',
];

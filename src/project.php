<?php

return [
    'perPageAllow' => [10, 20, 50, 100],
    'dbBackupList' => [
        'sys_permissions',
        'sys_roles',
        'sys_role_has_permissions',
        'sys_model_has_roles',
        'personal_access_tokens',
    ],
    'migrationBlacklists' => [
        'create_users_table',
        'create_cache_table',
        'create_jobs_table',
    ],
    'dbSkipGenModel' => [
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'migrations',
        'password_reset_tokens',
        'sessions',
        'personal_access_tokens',
        'users',
    ],
    'hasApiTokens' => ['admins', 'wechats'],
    'hasRoles' => ['sys_permissions', 'sys_roles', 'company_admins', 'admins'],
    'hasNodeTrait' => ['sys_permissions'],
    'tablePrefix' => '',
    'showDoc' => env('SHOW_DOC', true),
    'erMaps' => [
        '标准' => [
            'admins',
            'sys_permissions',
            'sys_roles',
            'sys_role_has_permissions',
            'sys_model_has_roles',
            'personal_access_tokens',
        ],
    ]
];

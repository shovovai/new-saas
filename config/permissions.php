<?php

use App\Enums\TeamRole;

/**
 * Source-of-truth for permission keys and their default role assignment.
 *
 * This is only the *seed* data — the `permissions` and `role_permissions`
 * tables are the runtime source of truth once seeded, and are editable
 * from the admin panel without a deploy or code change.
 */
return [

    'permissions' => [
        'team.manage' => ['label' => 'Manage team settings', 'group' => 'Team'],
        'team.invite' => ['label' => 'Invite team members', 'group' => 'Team'],
        'team.remove_member' => ['label' => 'Remove team members', 'group' => 'Team'],
        'team.transfer_ownership' => ['label' => 'Transfer team ownership', 'group' => 'Team'],

        'websites.create' => ['label' => 'Add websites', 'group' => 'Websites'],
        'websites.manage' => ['label' => 'Edit / rename / group websites', 'group' => 'Websites'],
        'websites.delete' => ['label' => 'Delete websites', 'group' => 'Websites'],
        'websites.verify' => ['label' => 'Run website verification', 'group' => 'Websites'],

        'monitoring.manage' => ['label' => 'Pause / resume monitoring', 'group' => 'Monitoring'],
        'scans.run' => ['label' => 'Run performance / SEO / security scans', 'group' => 'Scanning'],
        'ai.use' => ['label' => 'Use the AI assistant', 'group' => 'AI'],
        'pentest.run' => ['label' => 'Run authorized penetration tests', 'group' => 'Pen Testing'],
        'pentest.authorize' => ['label' => 'Authorize a pen test scope', 'group' => 'Pen Testing'],

        'reports.view' => ['label' => 'View reports', 'group' => 'Reports'],
        'reports.export' => ['label' => 'Export / download reports', 'group' => 'Reports'],

        'billing.manage' => ['label' => 'Manage subscription & billing', 'group' => 'Billing'],
        'api.manage' => ['label' => 'Manage API keys', 'group' => 'API'],
        'settings.manage' => ['label' => 'Manage team settings & integrations', 'group' => 'Settings'],
    ],

    'defaults' => [
        TeamRole::Owner->value => ['*'],
        TeamRole::Admin->value => [
            'team.manage', 'team.invite', 'team.remove_member',
            'websites.create', 'websites.manage', 'websites.delete', 'websites.verify',
            'monitoring.manage', 'scans.run', 'ai.use', 'pentest.run', 'pentest.authorize',
            'reports.view', 'reports.export', 'billing.manage', 'api.manage', 'settings.manage',
        ],
        TeamRole::Developer->value => [
            'websites.create', 'websites.manage', 'websites.verify',
            'monitoring.manage', 'scans.run', 'ai.use',
            'reports.view', 'reports.export',
        ],
        TeamRole::SeoExpert->value => [
            'scans.run', 'ai.use', 'reports.view', 'reports.export',
        ],
        TeamRole::SecurityAnalyst->value => [
            'scans.run', 'ai.use', 'pentest.run', 'pentest.authorize', 'reports.view', 'reports.export',
        ],
        TeamRole::Client->value => [
            'reports.view',
        ],
        TeamRole::Viewer->value => [
            'reports.view',
        ],
    ],
];

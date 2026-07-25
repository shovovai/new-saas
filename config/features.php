<?php

/**
 * Every feature the product can gate maps to one row here, seeded into the
 * `features` table. Availability per plan lives entirely in `plan_features`
 * (admin-editable, zero-deploy) — this file only defines what a feature
 * *is*, never which plan includes it.
 */
return [
    'monitoring.core' => ['name' => 'Uptime & Change Monitoring', 'category' => 'monitoring'],
    'performance.scans' => ['name' => 'Performance Scans (Core Web Vitals, Lighthouse)', 'category' => 'scanning'],
    'seo.scans' => ['name' => 'SEO Scans', 'category' => 'scanning'],
    'security.scans' => ['name' => 'Security Header & Config Scans', 'category' => 'scanning'],
    'accessibility.scans' => ['name' => 'Accessibility Scans', 'category' => 'scanning'],

    'ai.assistant' => ['name' => 'Per-Website AI Assistant', 'category' => 'ai'],
    'ai.report_summarization' => ['name' => 'AI Report Summarization', 'category' => 'ai'],
    'ai.fix_generation' => ['name' => 'AI Fix Generation', 'category' => 'ai'],

    'pentest.module' => ['name' => 'Authorized Penetration Testing', 'category' => 'pentest'],

    'reports.pdf' => ['name' => 'PDF Reports', 'category' => 'reports'],
    'reports.csv' => ['name' => 'CSV Export', 'category' => 'reports'],
    'reports.scheduled' => ['name' => 'Scheduled Reports (daily/weekly/monthly)', 'category' => 'reports'],

    'notifications.email' => ['name' => 'Email Notifications', 'category' => 'notifications'],
    'notifications.sms' => ['name' => 'SMS Notifications', 'category' => 'notifications'],
    'notifications.slack' => ['name' => 'Slack Notifications', 'category' => 'notifications'],
    'notifications.discord' => ['name' => 'Discord Notifications', 'category' => 'notifications'],
    'notifications.telegram' => ['name' => 'Telegram Notifications', 'category' => 'notifications'],
    'notifications.webhook' => ['name' => 'Webhook Notifications', 'category' => 'notifications'],

    'api.access' => ['name' => 'API Access', 'category' => 'api'],
    'white_label' => ['name' => 'White Label', 'category' => 'branding'],
    'priority_support' => ['name' => 'Priority Support', 'category' => 'support'],
];

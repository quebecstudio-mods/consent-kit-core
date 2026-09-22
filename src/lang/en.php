<?php

/**
 * English wording. A site replaces any of it, or adds a language, with a file
 * of the same shape; missing keys fall back to the fallback language.
 */
return [
    'texts' => [
        'title' => 'We use cookies',
        'panelTitle' => 'Your cookie preferences',
        'body' => 'This site uses cookies that are required for it to work. With your consent, we also use cookies to measure traffic. You can change your mind at any time.',
        'accept' => 'Accept',
        'refuse' => 'Decline',
        'manage' => 'Manage',
        'save' => 'Save my choices',
        'details' => 'More information',
        'alwaysOn' => 'Always on',
        'policyLabel' => 'Privacy policy',
        'reopenLabel' => 'Cookie preferences',
        'confirmation' => 'Your preferences have been saved.',
        'close' => 'Close',
        'reloadNotice' => 'The page will reload to apply your choice.',
        'gpcNotice' => 'Your browser signals a refusal of optional cookies. They stay off; you can turn any on here if you want to.',
        'colName' => 'Cookie',
        'colProvider' => 'Set by',
        'colPurpose' => 'Purpose',
        'colDuration' => 'Retention',
        'firstParty' => 'This site',
        'videoPlay' => 'Play video',
        'videoNotice' => 'Loading this video allows YouTube (Google) to set cookies on your device.',
        'videoLabel' => 'Load and play this video from YouTube',
    ],

    'categories' => [
        'necessary' => [
            'label' => 'Necessary',
            'description' => 'Required for the site to work and for form security. These cannot be turned off.',
            'cookies' => [
                'craft-session' => ['purpose' => 'Keeps your browsing session.', 'duration' => 'Session'],
                'craft-csrf' => ['purpose' => 'Protects forms against cross-site request forgery.', 'duration' => 'Session'],
                'consent' => ['purpose' => 'Remembers your cookie choices.', 'duration' => '6 months'],
            ],
        ],
        'statistics' => [
            'label' => 'Statistics',
            'description' => 'Help us understand how the site is used so we can improve it.',
        ],
        'marketing' => [
            'label' => 'Marketing',
            'description' => 'Used to measure our communications and to show you relevant content.',
        ],
    ],
];

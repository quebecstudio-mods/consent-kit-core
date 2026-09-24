<?php

namespace QuebecStudioMods\ConsentKit\Core\Settings;

/**
 * Every setting the control panels offer, described once: which section it
 * belongs to, what control edits it, and the English label and help the
 * wording files are keyed by. Defaults are not here — they stay with the
 * configuration each host publishes.
 *
 * An integration renders this in its own host's form language, and may
 * replace a help string the host words differently — `:tag` is the template
 * call, which every host spells its own way.
 *
 * What is not here is what no two hosts share: the cookie inventory, whose
 * repeatable field has no common shape, and anything a host computes from
 * its own state, like a warning drawn from the values in force.
 */
final class Catalogue
{
    /** Sections in the order a screen shows them, each with its own fields. */
    public const SECTIONS = [
        'general' => [
            'label' => 'General',
            'fields' => [
                'autoInject' => [
                    'label' => 'Automatic injection',
                    'help' => 'Places the banner at the end of `<body>`, without touching any template. Turn this off only if the site needs to position it itself, with :tag. The `<head>` bootstrap always stays automatic.',
                    'kind' => 'toggle',
                ],
                'defaultLanguage' => [
                    'label' => 'Fallback language',
                    'help' => 'Used when the current locale has no wording. The addon ships English and French, plus any the site adds in lang/vendor/cookie-consent.',
                    'kind' => 'text',
                    'width' => '50',
                ],
            ],
        ],
        'cookie' => [
            'label' => 'Consent cookie',
            'fields' => [
                'cookieName' => [
                    'label' => 'Cookie name',
                    'help' => 'Name of the cookie that remembers the visitor’s choice. Renaming it invalidates existing consents — bump the policy version at the same time.',
                    'kind' => 'text',
                    'width' => '50',
                ],
                'cookieMaxAge' => [
                    'label' => 'Lifetime',
                    'help' => 'In seconds. 15,552,000 is 180 days.',
                    'kind' => 'integer',
                    'width' => '50',
                    'validate' => 'min:0',
                ],
                'version' => [
                    'label' => 'Policy version',
                    'help' => 'Bump this when a cookie appears in a non-necessary category, a category is added, or a purpose changes. Visitors will then be asked again.',
                    'kind' => 'integer',
                    'width' => '50',
                    'validate' => 'min:1',
                ],
            ],
        ],
        'policy' => [
            'label' => 'Privacy policy',
            'fields' => [
                'policyUrl' => [
                    'label' => 'Privacy policy URL',
                    'help' => 'Shown in the banner. Leave it empty for no link.',
                    'kind' => 'text',
                ],
            ],
        ],
        'behaviour' => [
            'label' => 'Behaviour',
            'fields' => [
                'gpcHidesBanner' => [
                    'label' => 'Skip the banner on a Global Privacy Control refusal',
                    'help' => 'Some browsers send a signal meaning “I refuse optional cookies”. That refusal is always honoured: optional categories start off, and nothing is set before consent. This setting only decides whether the banner still asks. With the banner skipped, a visitor changes their mind from the reopen tab — so keep that tab on, or provide your own entry point.',
                    'kind' => 'toggle',
                ],
                'reopenButton' => [
                    'label' => 'Reopen tab',
                    'help' => 'Small tab shown once the visitor has decided, so the banner can be reopened. Required for compliance — withdrawal must be as easy as consent. Turn it off only if the site provides its own entry point calling window.qsmConsentKit.open().',
                    'kind' => 'toggle',
                ],
                'reopenPosition' => [
                    'label' => 'Reopen tab position',
                    'help' => 'Bottom edge of the screen, on this side. “Auto” follows the display mode: on the right for a bottom right corner, on the left otherwise.',
                    'kind' => 'select',
                    'options' => [
                        'auto' => 'Auto',
                        'left' => 'Left',
                        'right' => 'Right',
                    ],
                    'if' => [
                        'reopenButton' => true,
                    ],
                    'width' => '50',
                ],
            ],
        ],
        'measurement' => [
            'label' => 'Measurement',
            'help' => 'Which category a visitor has to accept before Google Consent Mode and Matomo are granted. A site that measures nothing leaves both empty.',
            'fields' => [
                'analyticsCategory' => [
                    'label' => 'Analytics category',
                    'help' => 'Drives Matomo and Google analytics_storage.',
                    'kind' => 'text',
                    'width' => '50',
                ],
                'marketingCategory' => [
                    'label' => 'Marketing category',
                    'help' => 'Drives Google ad_storage, ad_user_data and ad_personalization.',
                    'kind' => 'text',
                    'width' => '50',
                ],
            ],
        ],
        'video' => [
            'label' => 'Video',
            'fields' => [
                'videoFacade' => [
                    'label' => 'YouTube facade',
                    'help' => 'YouTube videos load only when the visitor clicks, so nothing reaches Google beforehand — the click is the consent, for that video alone. Only YouTube is covered: videos hosted elsewhere are untouched by this setting. Turning this off embeds YouTube directly, which lets Google set cookies as soon as the page is displayed, without any consent.',
                    'kind' => 'toggle',
                ],
                'videoThumbnails' => [
                    'label' => 'YouTube thumbnails',
                    'help' => 'Show the real thumbnail on the facade. The server fetches it from YouTube once, caches it, and serves it from this domain — the visitor never contacts Google before clicking. Turning this off falls back to a plain gradient.',
                    'kind' => 'toggle',
                    'if' => [
                        'videoFacade' => true,
                    ],
                ],
                'videoConsentCategory' => [
                    'label' => 'Category that lifts the facade',
                    'help' => 'A visitor who accepted this category gets the video loaded outright, without clicking. Left empty, the facade always applies — which is the safer answer: consent for a category is broader than consent for one video, and Law 25 asks for specific consent.',
                    'kind' => 'text',
                    'if' => [
                        'videoFacade' => true,
                    ],
                    'width' => '50',
                ],
            ],
        ],
        'appearance' => [
            'label' => 'Appearance',
            'fields' => [
                'colorScheme' => [
                    'label' => 'Colour scheme',
                    'help' => '“Auto” follows the visitor’s system preference. The dark scheme uses the palette set through --qsm-ck-dark-*.',
                    'kind' => 'select',
                    'options' => [
                        'auto' => 'Auto (recommended)',
                        'light' => 'Light',
                        'dark' => 'Dark',
                    ],
                    'width' => '50',
                ],
                'displayMode' => [
                    'label' => 'Display mode',
                    'help' => 'Full width along the bottom, or a box: floating in the middle, or in a bottom corner. On a narrow screen every mode is full width.',
                    'kind' => 'select',
                    'options' => [
                        'full' => 'Full width',
                        'floating' => 'Floating box',
                        'corner-left' => 'Bottom left corner',
                        'corner-right' => 'Bottom right corner',
                    ],
                    'width' => '50',
                ],
                'backdropStyle' => [
                    'label' => 'Panel backdrop',
                    'help' => 'Effect applied behind the “Manage” panel. Blur signals the modality without hiding the page.',
                    'kind' => 'select',
                    'options' => [
                        'blur' => 'Blur (recommended)',
                        'dim' => 'Dim',
                        'none' => 'None',
                    ],
                    'width' => '50',
                ],
            ],
        ],
        'registry' => [
            'label' => 'Consent register',
            'fields' => [
                'registry' => [
                    'label' => 'Record decisions',
                    'help' => 'Each decision is written down as the browser makes it: the server clock, the site, the categories answered, and a fingerprint of the wording that was on screen. The cookie’s own timestamp lives on the visitor’s device and proves nothing. Off by default — a register is something a site announces in its privacy policy.',
                    'kind' => 'toggle',
                ],
                'registryGrace' => [
                    'label' => 'Keep records for',
                    'help' => 'Months kept beyond the life of the consent cookie itself, so a proof outlives what it attests. Zero keeps every record until it is purged by hand.',
                    'kind' => 'integer',
                    'if' => [
                        'registry' => true,
                    ],
                    'width' => '50',
                    'validate' => 'min:0',
                ],
                'registryUser' => [
                    'label' => 'Record the signed-in user',
                    'help' => 'When a decision comes from someone signed in, their account is recorded with it. This is the one identity the server can assert rather than be told. Deleting an account clears the link and leaves the decision.',
                    'kind' => 'toggle',
                    'if' => [
                        'registry' => true,
                    ],
                ],
                'registryRequestContext' => [
                    'label' => 'Record where the decision came from',
                    'help' => 'The visitor’s address and browser, stored as they are, so a record answers where a decision came from — which a hash cannot. It also makes the register personal data, to be declared and to be answered for. Off by default.',
                    'kind' => 'toggle',
                    'if' => [
                        'registry' => true,
                    ],
                ],
            ],
        ],
    ];

    /**
     * Every field, keyed by its handle, with the section it sits in.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function fields(): array
    {
        $fields = [];

        foreach (self::SECTIONS as $section => $definition) {
            foreach ($definition['fields'] as $handle => $field) {
                $fields[$handle] = ['section' => $section] + $field;
            }
        }

        return $fields;
    }
}

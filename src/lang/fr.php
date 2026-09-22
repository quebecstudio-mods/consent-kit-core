<?php

/**
 * French wording. A site replaces any of it, or adds a language, with a file
 * of the same shape; missing keys fall back to the fallback language.
 */
return [
    'texts' => [
        'title' => 'Nous utilisons des témoins',
        'panelTitle' => 'Vos préférences de témoins',
        'body' => 'Ce site utilise des témoins (« cookies ») nécessaires à son fonctionnement. Avec votre accord, nous en utilisons aussi pour mesurer la fréquentation. Vous pouvez changer d\'avis en tout temps.',
        'accept' => 'Accepter',
        'refuse' => 'Refuser',
        'manage' => 'Gérer',
        'save' => 'Enregistrer mes choix',
        'details' => 'Plus d\'information',
        'alwaysOn' => 'Toujours actif',
        'policyLabel' => 'Politique de confidentialité',
        'reopenLabel' => 'Préférences de témoins',
        'confirmation' => 'Vos préférences ont été enregistrées.',
        'close' => 'Fermer',
        'reloadNotice' => 'La page va être rechargée pour appliquer votre choix.',
        'gpcNotice' => 'Votre navigateur signale un refus des témoins facultatifs. Ils restent désactivés ; vous pouvez en activer ici si vous le souhaitez.',
        'colName' => 'Témoin',
        'colProvider' => 'Déposé par',
        'colPurpose' => 'Finalité',
        'colDuration' => 'Conservation',
        'firstParty' => 'Ce site',
        'videoPlay' => 'Lire la vidéo',
        'videoNotice' => 'En chargeant cette vidéo, YouTube (Google) pourra déposer des témoins sur votre appareil.',
        'videoLabel' => 'Charger et lire cette vidéo depuis YouTube',
    ],

    'categories' => [
        'necessary' => [
            'label' => 'Nécessaires',
            'description' => 'Indispensables au fonctionnement du site et à la sécurité des formulaires. Ils ne peuvent pas être désactivés.',
            'cookies' => [
                'craft-session' => ['purpose' => 'Maintient votre session de navigation.', 'duration' => 'Session'],
                'craft-csrf' => ['purpose' => 'Protège les formulaires contre la falsification de requête.', 'duration' => 'Session'],
                'consent' => ['purpose' => 'Mémorise vos choix en matière de témoins.', 'duration' => '6 mois'],
            ],
        ],
        'statistics' => [
            'label' => 'Statistiques',
            'description' => 'Nous aident à comprendre comment le site est utilisé, afin de l\'améliorer.',
        ],
        'marketing' => [
            'label' => 'Marketing',
            'description' => 'Servent à mesurer l\'efficacité de nos communications et à vous proposer des contenus pertinents.',
        ],
    ],
];

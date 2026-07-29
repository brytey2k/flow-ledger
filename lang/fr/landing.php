<?php

declare(strict_types=1);

return [
    'meta' => [
        'title_suffix' => "Des workflows d'approbation que les équipes financières peuvent prouver",
        'description' => "Flow Ledger achemine les demandes de décaissement, de paiement et de justification à travers des workflows d'approbation configurables, avec une piste d'audit complète, des rôles multi-tenant et le SSO intégré.",
    ],

    'nav' => [
        'how_it_works' => 'Fonctionnement',
        'security' => 'Sécurité',
        'get_started' => 'Commencer',
        'toggle_menu' => 'Basculer le menu',
        'language' => 'Langue',
    ],

    'hero' => [
        'headline' => 'Chaque approbation, comptabilisée.',
        'lede' => "Flow Ledger achemine les demandes de décaissement, de paiement et de justification à travers les chaînes d'approbation que votre organisation utilise déjà — succursale par succursale, département par département, avec un historique de chaque décision.",
        'get_started' => 'Commencer',
        'see_how_it_works' => 'Voir comment ça marche',
        'note' => 'Nouveau sur Flow Ledger ? Demandez à votre administrateur l\'accès à votre espace de travail.',
        'figure_alt' => "Tableau de bord Flow Ledger affichant le solde de caisse d'une succursale par rapport à son seuil",
        'figure_caption' => 'Un espace de travail en direct — soldes de succursales, seuils et demandes réunis en une seule vue.',
    ],

    'steps' => [
        'heading' => 'Comment une demande circule dans Flow Ledger.',
        'lede' => 'Quatre étapes, les mêmes que celles que suit déjà votre équipe financière — avec un système qui suit chaque transmission.',
        'submit' => [
            'heading' => 'Soumettre la demande',
            'body' => "Le personnel soumet des demandes de paiement, de décaissement ou de justification associées à un code de coût, un département et une succursale — chaque demande part donc avec le contexte dont la finance a besoin pour l'examiner.",
            'alt' => 'Liste des demandes de paiement affichant les montants, succursales et statuts',
        ],
        'route' => [
            'heading' => 'Acheminer pour approbation',
            'body' => "Les groupes d'approbation à étapes multiples et parallèles font passer la demande par les bonnes personnes, avec des seuils et des périmètres d'approbateur par étape. Modifiez un workflow plus tard, et les demandes déjà en cours continuent de fonctionner sur la version avec laquelle elles ont démarré — rien ne casse en cours d'approbation.",
            'alt' => "Configuration d'un workflow d'approbation montrant des étapes séquentielles et un groupe d'approbation finale en parallèle",
        ],
        'release' => [
            'heading' => 'Libérer les fonds',
            'body' => 'Les décaissements approuvés sont enregistrés dans le journal de caisse de la succursale. Les comptages de caisse rapprochent les montants disponibles de ceux enregistrés, succursale par succursale.',
            'alt' => 'Vue du journal de caisse affichant les soldes de caisse de quatre succursales',
        ],
        'close' => [
            'heading' => 'Clore la boucle',
            'body' => "Les demandes de justification relient les dépenses au décaissement d'origine — avec l'écart et tout remboursement dû suivis aux côtés d'un journal d'activité complet indiquant qui a approuvé quoi, et quand.",
            'alt' => "Liste des demandes de justification montrant le montant dépensé et l'écart par rapport à chaque avance",
        ],
    ],

    'bento' => [
        'heading' => 'Découvrez Flow Ledger en action.',
        'lede' => "Cinq écrans d'un espace de travail en direct — les mêmes vues que votre équipe financière ouvrirait aujourd'hui.",
        'dashboard' => [
            'title' => 'Chaque succursale, un seul écran',
            'text' => 'Soldes de caisse, seuils et demandes — la vue complète dès la connexion.',
        ],
        'workflow_stages' => [
            'title' => 'Les chaînes que vous suivez déjà',
            'text' => "Étapes d'approbation séquentielles et parallèles, configurées pour correspondre à la façon dont votre équipe valide.",
        ],
        'payment_requests' => [
            'title' => 'Chaque demande, suivie',
            'text' => 'Montants, succursales et statut pour chaque demande de paiement et de décaissement.',
        ],
        'cashbook' => [
            'title' => 'Rapproché, succursale par succursale',
            'text' => 'Comptages de caisse par rapport aux montants disponibles, pour chaque succursale dans un seul journal.',
        ],
        'retirements' => [
            'title' => 'La boucle, close',
            'text' => "Les justifications relient les dépenses au décaissement d'origine, avec l'écart suivi automatiquement.",
        ],
    ],

    'spec' => [
        'heading' => 'Conçu pour les organisations, pas seulement pour les utilisateurs.',
        'lede' => 'Chaque tenant est son propre espace de travail, avec ses propres personnes, permissions et historique des actions.',
        'multi_tenant' => [
            'term' => 'Espaces de travail multi-tenant',
            'description' => 'Chaque organisation obtient un espace de travail isolé — avec ses propres succursales, départements, rôles et demandes.',
        ],
        'roles_permissions' => [
            'term' => 'Rôles et permissions',
            'description' => "Des rôles granulaires, par tenant, déterminent qui peut soumettre, approuver ou administrer — jusqu'aux permissions individuelles.",
        ],
        'sso' => [
            'term' => 'Authentification unique (SSO)',
            'description' => "Connectez-vous via votre fournisseur d'identité. La déconnexion reste synchronisée entre les sessions grâce à la déconnexion backchannel.",
        ],
        'activity_log' => [
            'term' => "Journal d'activité",
            'description' => 'Chaque demande, approbation, décaissement et justification est enregistré et attribuable.',
        ],
    ],

    'cta_strip' => [
        'heading' => 'Prêt à acheminer votre première approbation ?',
        'lede' => 'Commencez avec votre espace de travail Flow Ledger.',
        'get_started' => 'Commencer',
    ],

    'footer' => [
        'tagline' => "Des workflows d'approbation pour les équipes financières",
    ],
];

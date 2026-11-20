<?php

declare(strict_types=1);

return [
    'meta' => [
        'title_suffix' => 'Avances et dépenses du personnel, approuvées et justifiées',
        'description' => "Flow Ledger suit les avances et les notes de frais du personnel, de la demande à l'approbation, au décaissement et à la reddition de comptes, avec la trace de chaque signature, une caisse par succursale et des rapports pour la finance et l'audit.",
    ],

    'nav' => [
        'skip' => 'Aller au contenu',
        'footer' => 'Pied de page',
        'videos' => 'Vidéos',
        'primary' => 'Navigation principale',
        'how_it_works' => 'Fonctionnement',
        'security' => 'Contrôles',
        'film' => 'Vidéo',
        'user_guide' => 'Guide utilisateur',
        'book_demo' => 'Demander une démo',
        'toggle_menu' => 'Menu',
        'language' => 'Langue',
        'dark_mode' => 'Mode sombre',
        'home' => 'Accueil Flow Ledger',
    ],

    'hero' => [
        'headline' => 'Chaque cedi avancé, justifié.',
        'lede' => "Flow Ledger suit les avances et les notes de frais du personnel, de la demande à l'approbation, au décaissement et à la reddition de comptes. Chaque signature est enregistrée, chaque avance est relancée jusqu'à sa justification, et la finance voit la caisse de chaque succursale.",
        'book_demo' => 'Demander une démo',
        'see_how_it_works' => 'Voir le fonctionnement',
        'watch_film' => 'Voir la vidéo',
        'call_prompt' => 'Vous préférez en parler ? Appelez ou écrivez sur WhatsApp au',
        'shot_alt' => 'Le tableau de bord de Flow Ledger : approbations en attente, décaissements en attente, montants décaissés sur 30 jours, avances en retard et une succursale sous son seuil de caisse',
        'shot_caption' => "Le tableau de bord d'une organisation de démonstration.",
    ],

    'problem' => [
        'heading' => "Là où les approbations papier coûtent de l'argent.",
        'lede' => 'La plupart des équipes financières connaissent bien ces problèmes. Flow Ledger répond directement à chacun.',
        'without' => "Aujourd'hui",
        'with' => 'Avec Flow Ledger',
        'rows' => [
            'signatures' => [
                'problem' => "Une demande attend des jours la signature suivante, parce que quelqu'un est toujours en déplacement.",
                'answer' => "Les demandes passent en ligne par vos étapes d'approbation. Chaque approbateur dispose d'une boîte de réception de ce qui l'attend, et rien ne se perd dans un classeur.",
            ],
            'unretired' => [
                'problem' => 'Des avances sont versées et jamais justifiées.',
                'answer' => "Chaque avance décaissée attend sa reddition de comptes. Passé votre délai de grâce, des rappels partent vers l'employé, les approbateurs ou les rôles de votre choix, jusqu'au règlement.",
            ],
            'cash' => [
                'problem' => "L'argent en caisse ne correspond pas au livre, et personne ne sait pourquoi.",
                'answer' => 'Chaque succursale tient une caisse où les décaissements et les règlements sont enregistrés automatiquement. Les comptages de caisse font apparaître tout excédent ou déficit.',
            ],
            'trail' => [
                'problem' => "L'auditeur demande qui a approuvé un paiement, et personne ne peut répondre.",
                'answer' => "Chaque demande porte son propre historique : qui a fait quoi, et quand. Le journal d'activité ne peut être ni modifié ni supprimé, et il est conservé sept ans.",
            ],
        ],
    ],

    'film' => [
        'seconds' => ':count seconde|:count secondes',
        'heading' => 'Flow Ledger en :length.',
        'lede' => 'Une courte vidéo sur tout le cycle : une demande de paiement, ses approbations, le décaissement, la reddition de comptes et les rapports.',
        'walkthrough' => 'Voir la visite complète',
        'more_videos' => 'Autres vidéos',
        'user_guide' => 'Lire le guide utilisateur',
    ],

    'steps' => [
        'heading' => 'De la demande à la justification, au même endroit.',
        'lede' => "Les cinq étapes que votre équipe financière suit déjà, avec un système qui garde la trace. Les écrans ci-dessous proviennent d'une organisation de démonstration.",
        'request' => [
            'title' => 'Le personnel demande une avance ou un remboursement',
            'body' => "Chaque ligne porte un code de coût, les justificatifs sont joints, et la demande est rattachée dès le départ à la succursale et au département de l'employé.",
            'points' => [
                'Une avance avant la dépense, ou une dépense à rembourser',
                'Justificatifs exigés avant la soumission, si vous le souhaitez',
                'Enregistrez un brouillon et soumettez-le quand il est prêt',
            ],
            'alt' => "Une demande d'avance en brouillon avec son type, sa devise, ses notes et ses lignes",
        ],
        'approve' => [
            'title' => 'Les bonnes personnes approuvent, dans le bon ordre',
            'body' => 'Vous choisissez les étapes : chef de département, agent financier, directeur financier, ou toute personne qui signe dans votre organisation. Des étapes peuvent se dérouler en parallèle, et les petits montants peuvent sauter les étapes de votre choix.',
            'points' => [
                'Approuver, renvoyer pour correction ou rejeter, avec un commentaire',
                "Une demande renvoyée reprend à l'étape qui l'a renvoyée",
                'Personne ne peut approuver sa propre demande',
            ],
            'alt' => 'Un approbateur examinant une demande, avec les boutons Approuver, Renvoyer et Rejeter',
        ],
        'disburse' => [
            'title' => 'La finance décaisse depuis la caisse de la succursale',
            'body' => 'Les demandes approuvées attendent dans une seule file, à côté de la trésorerie disponible dans chaque succursale. Le décaissement est enregistré dans la caisse de cette succursale.',
            'points' => [
                'Qui a demandé ou approuvé une demande ne peut pas la décaisser',
                'Chaque décaissement est vérifié par rapport au solde de la caisse',
                'Notez une référence de transaction ou un numéro de chèque',
            ],
            'alt' => 'La file des décaissements avec la trésorerie disponible dans chaque succursale',
        ],
        'retire' => [
            'title' => 'Le personnel justifie chaque avance',
            'body' => "L'employé liste ce qu'il a réellement dépensé, avec les justificatifs. Flow Ledger calcule la différence : un remboursement dû à l'organisation, ou un solde dû à l'employé.",
            'points' => [
                "Les redditions de comptes suivent leur propre workflow d'approbation",
                'Le règlement enregistre la différence dans la caisse',
                "Les rappels continuent jusqu'à la justification de l'avance",
            ],
            'alt' => "Une reddition de comptes listant les dépenses d'une avance, avec la différence à rembourser",
        ],
        'report' => [
            'title' => "Voyez où est l'argent, et où il est bloqué",
            'body' => "Des rapports pour la finance, la direction et l'audit, filtrés par période et par succursale, et exportables en CSV ou PDF.",
            'points' => [
                'Avances non justifiées, selon leur ancienneté',
                'Position de caisse, décaissements et dépenses par succursale',
                "Délai d'approbation, taux de renvoi et SLA des workflows",
            ],
            'alt' => 'Le rapport des avances non justifiées, regroupées par nombre de jours',
        ],
    ],

    'controls' => [
        'heading' => 'Des réponses pour ceux qui signent.',
        'lede' => "Ce que la finance, l'audit interne et l'informatique demandent avant d'adopter un système qui gère de l'argent.",
        'shot_alt' => "L'historique d'une demande : création, soumission et chaque étape approuvée, avec qui l'a fait et quand",
        'shot_caption' => 'Chaque demande garde son historique : qui a fait quoi, et quand.',
        'items' => [
            'segregation' => [
                'question' => 'Une même personne peut-elle demander, approuver et décaisser la même avance ?',
                'answer' => "Non. Un demandeur ne peut pas approuver sa propre demande, une personne ne peut agir qu'à une seule étape d'un workflow, et personne ne peut décaisser une demande qu'il a faite ou approuvée. Si aucun approbateur indépendant n'est disponible, l'étape attend au lieu d'être sautée.",
            ],
            'audit' => [
                'question' => "Quelqu'un peut-il modifier l'historique après coup ?",
                'answer' => "Les demandes, approbations, décaissements, écritures de caisse, comptages et modifications des utilisateurs, rôles et paramètres sont enregistrés dans le journal d'activité. Ses entrées ne peuvent être ni modifiées ni supprimées dans Flow Ledger, et elles sont conservées sept ans. Les écritures de caisse créées par les décaissements et les règlements ne peuvent pas non plus être supprimées.",
            ],
            'branches' => [
                'question' => 'Nous avons de nombreuses succursales. Qui voit quoi ?',
                'answer' => "Les succursales forment une arborescence, depuis le siège. Par défaut, chacun ne voit que les demandes, la caisse et les rapports de sa succursale ; une permission distincte l'étend à une succursale et à toutes celles qui en dépendent. Les étapes d'approbation peuvent être limitées à la succursale de la demande ou au département du demandeur.",
            ],
            'roles' => [
                'question' => 'Qui décide de ce que chacun peut faire ?',
                'answer' => "Vos administrateurs. Chaque action est une permission, et les rôles les regroupent : vous pouvez par exemple créer un rôle d'approbateur qui ne fait qu'examiner les demandes. Personne ne peut accorder une permission qu'il ne possède pas lui-même.",
            ],
            'sso' => [
                'question' => 'Le personnel peut-il se connecter avec son compte professionnel ?',
                'answer' => "Oui. Flow Ledger prend en charge l'authentification unique via un fournisseur d'identité. Le compte est créé à la première connexion et peut être relié automatiquement à la fiche de l'employé. La déconnexion reste synchronisée entre les sessions.",
            ],
            'isolation' => [
                'question' => 'Nos données sont-elles séparées de celles des autres organisations ?',
                'answer' => 'Oui. Chaque organisation dispose de sa propre base de données et de son propre stockage de fichiers.',
            ],
            'export' => [
                'question' => 'Pouvons-nous exporter nos données ?',
                'answer' => "Chaque rapport s'exporte en CSV et en PDF, et chaque caisse de succursale en CSV. Dans l'autre sens, le personnel, les départements et les postes peuvent être importés depuis des tableurs au démarrage.",
            ],
            'currency' => [
                'question' => 'Est-ce que cela fonctionne en cedis, et dans d\'autres devises ?',
                'answer' => 'Oui. Chaque demande est dans la devise de votre choix, chaque succursale tient sa caisse dans sa devise de référence, et les comptages de caisse utilisent les billets et pièces que vous définissez pour cette devise.',
            ],
        ],
    ],

    'contact' => [
        'heading' => 'Découvrez Flow Ledger avec votre propre circuit d\'approbation.',
        'lede' => "Nous mettons en place chaque organisation avec son équipe, c'est pourquoi l'inscription en libre-service est suspendue. Demandez une démonstration : nous présenterons Flow Ledger à votre équipe financière, puis nous le configurerons avec vous.",
        'call' => 'Appeler le :phone',
        'whatsapp' => 'Écrire sur WhatsApp',
        'whatsapp_message' => 'Bonjour, je souhaite une démonstration de Flow Ledger.',
        'next_heading' => 'La suite',
        'next' => [
            'demo' => "Une démonstration pour votre équipe financière, construite autour de la manière dont votre organisation approuve les paiements aujourd'hui.",
            'workspace' => "Nous créons l'espace de votre organisation, avec une succursale « Head Office » et un compte administrateur.",
            'setup' => "Vous ajoutez vos succursales, vos rôles et vos workflows d'approbation, et vous importez votre personnel depuis un tableur.",
        ],
    ],

    'footer' => [
        'statement' => "Avances, dépenses et redditions de comptes du personnel, pour les organisations où plus d'une personne signe.",
        'copyright' => '© :year Flow Ledger',
    ],
];

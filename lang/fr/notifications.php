<?php

declare(strict_types=1);

return [
    'greeting' => 'Bonjour :name,',

    'low_cash_balance' => [
        'subject' => '⚠️ Alerte solde de caisse faible : :branch',
        'greeting' => 'Bonjour,',
        'balance_fallen' => 'Le solde de caisse de **:branch** est tombé en dessous du seuil configuré.',
        'current_balance' => '**Solde actuel :** :amount',
        'threshold' => '**Seuil :** :amount',
        'take_action' => 'Veuillez prendre les mesures nécessaires pour renflouer le solde de caisse.',
        'automated' => 'Ceci est une alerte automatique. Veuillez ne pas répondre à cet e-mail.',
    ],

    'request_approved' => [
        'subject' => 'Demande #:id entièrement approuvée',
        'approved' => 'Votre demande a été **entièrement approuvée** et est en attente de décaissement.',
        'amount' => '**Montant :** :amount',
        'finance' => 'Les finances traiteront le décaissement sous peu.',
        'action' => 'Voir la demande',
    ],

    'request_disbursed' => [
        'subject' => 'La demande #:id a été décaissée',
        'disbursed' => 'Votre demande approuvée a été **décaissée**.',
        'amount' => '**Montant :** :amount',
        'method' => '**Méthode :** :method',
        'reference' => '**Référence :** :reference',
        'action' => 'Voir la demande',
    ],

    'request_rejected' => [
        'subject' => 'Demande #:id rejetée',
        'rejected' => 'Malheureusement, votre demande a été **rejetée**.',
        'reason' => '**Motif :** :reason',
        'amount' => '**Montant :** :amount',
        'contact' => 'Veuillez contacter votre approbateur si vous avez des questions.',
        'action' => 'Voir la demande',
    ],

    'request_sent_back' => [
        'subject' => 'Demande #:id renvoyée pour révision',
        'sent_back' => 'Votre demande a été **renvoyée** pour révision.',
        'feedback' => '**Commentaire :** :feedback',
        'resubmit' => 'Veuillez apporter les modifications demandées et resoumettre votre demande.',
        'action' => 'Voir et resoumettre',
    ],

    'retirement_approved' => [
        'subject' => 'Retraite #:retirement_id approuvée',
        'approved' => 'Votre retraite pour l\'avance #:pr_id a été **entièrement approuvée**.',
        'expended' => '**Montant dépensé :** :amount',
        'settlement' => '**Règlement :** :type — :amount',
        'action' => 'Voir la retraite',
    ],

    'retirement_overdue' => [
        'submitter' => [
            'subject' => 'Action requise : l\'avance #:id est en retard de retraite',
            'line1' => 'Votre avance **#:id** est en retard. Vous devez soumettre une retraite (note de frais) pour justifier les fonds.',
            'amount' => '**Montant de l\'avance :** :amount',
            'reminder' => 'Veuillez soumettre votre retraite avec les reçus et les codes de coût dès que possible.',
            'action' => 'Soumettre la retraite',
        ],
        'approver' => [
            'subject' => 'Retraite en retard : l\'avance #:id que vous avez approuvée n\'a pas été retirée',
            'line1' => 'L\'avance **#:id** que vous avez approuvée est en retard de retraite. Le membre du personnel n\'a pas encore soumis de note de frais.',
            'amount' => '**Montant de l\'avance :** :amount',
            'reminder' => 'Ceci est un rappel automatique.',
            'action' => 'Voir l\'avance',
        ],
        'default' => [
            'subject' => 'Alerte retraite en retard : l\'avance #:id n\'a pas été retirée',
            'line1' => 'L\'avance **#:id** est en retard de retraite. Aucune note de frais n\'a été soumise pour ce décaissement.',
            'amount' => '**Montant de l\'avance :** :amount',
            'reminder' => 'Ceci est un rappel automatique.',
            'action' => 'Voir l\'avance',
        ],
    ],

    'retirement_required' => [
        'subject' => 'Action requise : retirer l\'avance #:id',
        'line1' => 'Vous avez reçu un décaissement d\'avance. Veuillez soumettre votre **retraite (note de frais)** pour justifier les fonds dépensés.',
        'amount' => '**Montant de l\'avance :** :amount',
        'reminder' => 'Veuillez soumettre votre retraite dès que possible avec les reçus et les codes de compte pour toutes les dépenses.',
        'action' => 'Soumettre la retraite',
    ],

    'stage_ready' => [
        'subject' => 'Action requise : :stage — Demande #:id',
        'waiting' => 'Une demande attend votre approbation à l\'étape **:stage**.',
        'request' => '**Demande :** #:id — :type',
        'amount' => '**Montant :** :amount',
        'login' => 'Veuillez vous connecter pour approuver, renvoyer ou rejeter cette demande.',
        'action' => 'Examiner la demande',
    ],
];

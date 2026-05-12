<?php

declare(strict_types=1);

return [
    'title' => 'Caisse',
    'subtitle' => 'Soldes de caisse par succursale',
    'index' => [
        'title' => 'Caisse — :branch',
        'subtitle' => ':currency (:symbol) livre de caisse',
        'add_receipt' => 'Ajouter un recu',
        'balance_card' => 'Solde actuel',
        'entries_card' => 'Ecritures',
    ],
    'branches' => [
        'title' => 'Caisse',
        'subtitle' => 'Soldes de caisse par succursale',
        'card_heading' => 'Succursales',
        'empty_heading' => 'Aucune succursale configuree',
        'empty_subtext' => 'Creez une succursale pour commencer le suivi.',
        'view_cashbook' => 'Voir la caisse',
    ],
    'create' => [
        'title' => 'Ajouter un recu — :branch',
        'subtitle' => 'Enregistrer les fonds recus de la banque ou d une source externe',
        'back' => 'Retour a la caisse',
        'card' => 'Details du recu',
        'save' => 'Enregistrer le recu',
    ],
    'fields' => [
        'amount' => 'Montant (:symbol)',
        'date' => 'Date',
        'reference' => 'Numero de reference',
        'reference_placeholder' => 'ex. reference virement, numero de cheque',
        'notes' => 'Notes',
        'notes_placeholder' => 'Details supplementaires sur ce recu',
    ],
    'columns' => [
        'balance' => 'Solde',
    ],
    'entry_types' => [
        'debit' => 'Debit',
        'credit' => 'Credit',
    ],
    'empty' => [
        'heading' => 'Aucune ecriture pour le moment',
        'subtext' => 'Les ecritures sont creees automatiquement lors des decaissements ou reglements.',
        'add_manual' => 'Ajouter un recu manuel',
    ],
    'confirm_delete' => 'Supprimer ce recu ?',
];

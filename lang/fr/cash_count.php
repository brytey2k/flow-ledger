<?php

declare(strict_types=1);

return [
    'title' => 'Comptage de Caisse',
    'history_title' => 'Historique des Comptages',
    'create_title' => 'Nouveau Comptage',
    'show_title' => 'Résultat du Comptage',

    'labels' => [
        'counted_at' => 'Compté le',
        'counted_by' => 'Compté par',
        'counted_total' => 'Total Compté',
        'balance_at_count' => 'Solde au Comptage',
        'difference' => 'Différence',
        'notes' => 'Notes',
        'denomination' => 'Dénomination',
        'quantity' => 'Quantité',
        'subtotal' => 'Sous-total',
    ],

    'status' => [
        'equal' => 'Équilibré',
        'surplus' => 'Excédent',
        'deficit' => 'Déficit',
    ],

    'empty' => [
        'title' => 'Aucun comptage de caisse',
        'description' => 'Effectuez un comptage pour vérifier la caisse physique par rapport au solde du livre.',
    ],

    'validation' => [
        'at_least_one_quantity' => 'Au moins une dénomination doit avoir une quantité supérieure à zéro.',
    ],

    'buttons' => [
        'count_cash' => 'Compter la Caisse',
        'view_history' => 'Historique',
        'new_count' => 'Nouveau Comptage',
        'back_to_history' => 'Retour à l\'historique',
        'submit' => 'Enregistrer le Comptage',
    ],

    'denominations' => [
        'title' => 'Dénominations',
        'add' => 'Ajouter une Dénomination',
        'empty_title' => 'Aucune dénomination',
        'empty_description' => 'Ajoutez des dénominations pour cette devise pour activer le comptage de caisse.',
        'labels' => [
            'value' => 'Valeur',
            'label' => 'Libellé',
            'sort_order' => 'Ordre',
        ],
    ],
];

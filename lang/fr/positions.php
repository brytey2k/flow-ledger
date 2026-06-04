<?php

declare(strict_types=1);

return [
    'title' => 'Postes',
    'subtitle' => 'Gerer les postes du personnel',
    'add_new' => 'Ajouter un poste',
    'import' => 'Importer des postes',
    'all' => 'Tous les postes',
    'create_title' => 'Creer un poste',
    'create_subtitle' => 'Ajouter un poste a votre organisation',
    'import_title' => 'Importer des postes',
    'import_subtitle' => 'Televersez un fichier CSV ou telechargez le modele exemple pour creer des postes en lot.',
    'edit_title' => 'Modifier le poste',
    'edit_subtitle' => 'Mettre a jour les details du poste',
    'back' => 'Retour aux postes',
    'details_card' => 'Details du poste',
    'import_card' => 'Fichier d importation',
    'sample_card' => 'Modele CSV',
    'fields' => [
        'name' => 'Nom du poste',
        'name_hint' => 'Saisissez un nom descriptif pour ce poste.',
        'file' => 'Fichier CSV',
        'file_hint' => 'Televersez un CSV avec une seule colonne name.',
    ],
    'buttons' => [
        'create' => 'Creer le poste',
        'update' => 'Mettre a jour le poste',
        'add' => 'Ajouter un poste',
        'import' => 'Importer des postes',
        'download_sample' => 'Telecharger le CSV exemple',
        'delete' => 'Supprimer le poste',
    ],
    'empty' => [
        'heading' => 'Aucun poste trouve',
        'subtext' => 'Commencez par creer votre premier poste',
    ],
    'confirm_delete' => 'Supprimer ce poste ? Cette action est irreversible.',
    'import_notes' => 'Le CSV doit contenir un en-tete unique nomme name. Des exemples de lignes sont inclus dans le fichier modele.',

    'import_errors' => [
        'unreadable' => 'Le CSV televerse n a pas pu etre lu.',
        'empty' => 'Le fichier CSV est vide.',
        'no_rows' => 'Le CSV doit contenir au moins une ligne de poste.',
        'invalid_headers' => 'Le CSV doit contenir un seul en-tete nomme name.',
        'name_required' => 'Ligne :row : le nom du poste est requis.',
        'name_too_long' => 'Ligne :row : ":name" depasse la limite de 100 caracteres.',
        'duplicate_in_file' => 'Ligne :row : ":name" apparait plus d une fois dans le CSV.',
        'duplicate_existing' => 'Ligne :row : ":name" existe deja.',
    ],
];

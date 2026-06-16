<?php

declare(strict_types=1);

return [
    'title' => 'Gestion des utilisateurs',
    'subtitle' => 'Gerer les utilisateurs et leurs roles',
    'add_new' => 'Ajouter un utilisateur',
    'all' => 'Tous les utilisateurs',
    'create_title' => 'Creer un utilisateur',
    'create_subtitle' => 'Ajouter un nouvel utilisateur',
    'edit_title' => 'Modifier l utilisateur',
    'edit_subtitle' => 'Mettre a jour les informations et les roles',
    'back' => 'Retour aux utilisateurs',
    'details_card' => 'Details de l utilisateur',
    'fields' => [
        'first_name' => 'Prenom',
        'last_name' => 'Nom',
        'email' => 'Email',
        'password' => 'Mot de passe',
        'password_hint' => 'Laissez vide pour conserver le mot de passe actuel.',
        'confirm_password' => 'Confirmer le mot de passe',
        'branch' => 'Agence',
        'select_branch' => 'Selectionner une agence',
        'operational_branch' => 'Agence operationnelle',
        'select_operational_branch' => 'Meme que l agence',
        'roles' => 'Roles',
        'no_roles' => 'Aucun role disponible.',
    ],
    'buttons' => [
        'create' => 'Creer l utilisateur',
        'update' => 'Mettre a jour l utilisateur',
        'add' => 'Ajouter un utilisateur',
        'delete' => 'Supprimer',
        'manage_perms' => 'Gerer les permissions',
    ],
    'permissions' => [
        'title' => 'Gerer les permissions utilisateur',
        'subtitle' => 'Attribuer des permissions directes a :name',
        'back' => 'Retour a la modification',
        'card' => 'Permissions directes',
        'description' => 'Permissions directes attribuees independamment des roles.',
        'none' => 'Aucune permission disponible.',
        'update' => 'Mettre a jour les permissions',
    ],
    'columns' => [
        'roles' => 'Roles',
        'no_roles' => 'Aucun role',
    ],
    'empty' => [
        'heading' => 'Aucun utilisateur trouve',
        'subtext' => 'Commencez par creer votre premier utilisateur',
    ],
    'confirm_delete' => 'Supprimer cet utilisateur ? Cette action est irreversible.',
    'confirm_delete_short' => 'Supprimer cet utilisateur ?',
];

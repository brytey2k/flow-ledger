<?php

declare(strict_types=1);

return [
    'title' => 'Gestion des roles',
    'subtitle' => 'Gerer les roles et leurs permissions',
    'add_new' => 'Ajouter un role',
    'all' => 'Tous les roles',
    'create_title' => 'Creer un role',
    'create_subtitle' => 'Ajouter un nouveau role',
    'edit_title' => 'Modifier le role',
    'edit_subtitle' => 'Mettre a jour les informations du role',
    'back' => 'Retour aux roles',
    'details_card' => 'Details du role',
    'fields' => [
        'name' => 'Nom du role',
        'name_hint' => 'Saisissez un nom descriptif pour ce role.',
        'guard' => 'Nom du guard',
        'guard_hint' => 'Guard d authentification pour ce role (defaut : web).',
    ],
    'buttons' => [
        'create' => 'Creer le role',
        'update' => 'Mettre a jour le role',
        'add' => 'Ajouter un role',
        'delete' => 'Supprimer le role',
        'manage_perms' => 'Gerer les permissions',
    ],
    'permissions' => [
        'title' => 'Gerer les permissions du role',
        'subtitle' => 'Attribuer des permissions au role :role',
        'back' => 'Retour a la modification du role',
        'card' => 'Permissions du role',
        'description' => 'Selectionnez les permissions a attribuer. Tous les utilisateurs avec ce role les auront.',
        'select_all' => 'Selectionner toutes les permissions',
        'none' => 'Aucune permission disponible.',
        'update' => 'Mettre a jour les permissions',
    ],
    'columns' => [
        'users' => 'Utilisateurs',
        'permissions' => 'Permissions',
    ],
    'empty' => [
        'heading' => 'Aucun role trouve',
        'subtext' => 'Commencez par creer votre premier role',
    ],
    'confirm_delete' => 'Supprimer ce role ?',
];

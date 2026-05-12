<?php

declare(strict_types=1);

return [
    'title' => 'Gestion des succursales',
    'subtitle' => 'Gerer les succursales et la hierarchie',
    'add_new' => 'Ajouter une succursale',
    'all' => 'Toutes les succursales',
    'create_title' => 'Creer une succursale',
    'create_subtitle' => 'Ajouter une succursale a votre structure',
    'edit_title' => 'Modifier la succursale',
    'edit_subtitle' => 'Mettre a jour les details et la hierarchie',
    'back' => 'Retour aux succursales',
    'details_card' => 'Details de la succursale',
    'fields' => [
        'name' => 'Nom de la succursale',
        'name_hint' => 'Saisissez un nom descriptif.',
        'name_placeholder' => 'ex. Bureau regional d Accra',
        'code' => 'Code de la succursale',
        'code_hint' => 'Identifiant unique optionnel.',
        'code_placeholder' => 'ex. ACC-REG',
        'position' => 'Position',
        'position_hint' => 'Ordre d affichage au meme niveau.',
        'level' => 'Niveau',
        'level_hint' => 'Niveau organisationnel pour cette succursale.',
        'select_level' => 'Selectionner un niveau...',
        'currency' => 'Devise de reporting',
        'currency_hint' => 'Devise utilisee pour le reporting dans cette succursale.',
        'select_currency' => 'Selectionner une devise...',
        'parent' => 'Succursale parente',
        'parent_hint' => 'Laissez vide pour la racine, ou selectionnez un parent.',
        'none_root' => 'Aucune (succursale racine)',
    ],
    'buttons' => [
        'create' => 'Creer la succursale',
        'update' => 'Mettre a jour la succursale',
        'add' => 'Ajouter une succursale',
        'delete' => 'Supprimer la succursale',
    ],
    'empty' => [
        'heading' => 'Aucune succursale trouvee',
        'subtext' => 'Commencez par creer votre premiere succursale',
    ],
    'descendants_warning' => 'La succursale a des descendants',
    'descendants_message' => 'Cette succursale a :count :child. Changer le parent peut affecter la hierarchie.',
    'confirm_delete' => 'Supprimer cette succursale ? Cette action est irreversible.',
    'confirm_delete_short' => 'Supprimer cette succursale ?',
    'columns' => [
        'branch_name' => 'Nom de la succursale',
    ],
];

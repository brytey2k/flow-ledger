<?php

declare(strict_types=1);

return [
    'title' => 'Parametres',
    'subtitle' => 'Gerez la marque et les parametres par defaut de votre organisation.',

    'branding_card' => 'Image de marque',
    'advance_defaults_card' => 'Parametres par defaut des avances',
    'expense_settings_card' => 'Parametres des depenses',
    'retirement_settings_card' => 'Parametres des redditions',

    'fields' => [
        'logo_light' => 'Logo mode clair',
        'logo_light_hint' => 'Affiche dans la barre laterale etendue en mode clair. PNG, JPG ou WebP (max 2 Mo). Recommande : format large, ex. 272×44px.',
        'logo_light_preview_alt' => 'Logo mode clair actuel',
        'remove_logo_light' => 'Supprimer le logo mode clair',
        'logo_dark' => 'Logo mode sombre',
        'logo_dark_hint' => 'Affiche dans la barre laterale etendue en mode sombre. PNG, JPG ou WebP (max 2 Mo). Recommande : format large, ex. 272×44px.',
        'logo_dark_preview_alt' => 'Logo mode sombre actuel',
        'remove_logo_dark' => 'Supprimer le logo mode sombre',
        'logo_small' => 'Petit logo (icone)',
        'logo_small_hint' => 'Affiche lorsque la barre laterale est reduite. Utilisez une image carree. PNG, JPG ou WebP (max 2 Mo). Recommande : 44×44px.',
        'logo_small_preview_alt' => 'Petit logo actuel',
        'remove_logo_small' => 'Supprimer le petit logo',
        'default_advance_cost_code' => 'Code de cout par defaut pour les avances',
        'default_advance_cost_code_hint' => 'Ce code de cout est applique automatiquement lors de la creation d\'une avance sans code selectionne.',
        'no_default_cost_code' => '— Aucun par defaut —',
        'require_expense_source_documents' => 'Exiger des documents justificatifs pour les depenses',
        'require_expense_source_documents_hint' => 'Lorsque cette option est activee, les demandes de depense ne peuvent pas etre soumises sans au moins un document justificatif (recu, facture, etc.) joint.',
        'require_retirement_source_documents' => 'Exiger des documents justificatifs pour les redditions',
        'require_retirement_source_documents_hint' => 'Lorsque cette option est activee, les demandes de reddition ne peuvent pas etre soumises sans au moins un document justificatif (recu, facture, etc.) joint.',
    ],
];

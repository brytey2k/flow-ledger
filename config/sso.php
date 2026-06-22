<?php

declare(strict_types=1);

return [
    // Browser-facing IDP URL (used in authorization redirects and iss claim validation)
    'idp_url' => env('SSO_IDP_URL', 'http://localhost'),

    // Internal IDP URL for server-side HTTP calls (token exchange, userinfo)
    // Use host.docker.internal when the IDP runs in a separate Docker network
    'idp_internal_url' => env('SSO_IDP_INTERNAL_URL', env('SSO_IDP_URL', 'http://localhost')),

    'client_id' => env('SSO_CLIENT_ID'),

    'client_secret' => env('SSO_CLIENT_SECRET'),

    'redirect_uri' => env('SSO_REDIRECT_URI', 'http://flow-ledger.test/auth/sso/callback'),

    'scopes' => ['openid', 'profile', 'email', 'tenant', 'products', 'roles'],

    'jwks_uri' => env('SSO_JWKS_URI', 'http://localhost/.well-known/jwks.json'),

    'product_slug' => env('SSO_PRODUCT_SLUG', 'flow-ledger'),

    // M2M client credentials for server-to-server IdP calls
    'm2m_client_id' => env('SSO_M2M_CLIENT_ID'),
    'm2m_client_secret' => env('SSO_M2M_CLIENT_SECRET'),
    'm2m_scope' => env('SSO_M2M_SCOPE', 'login:verify'),

    'verify_ssl' => (bool) env('SSO_VERIFY_SSL', true),
];

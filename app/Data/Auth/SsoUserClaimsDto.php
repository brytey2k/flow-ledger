<?php

declare(strict_types=1);

namespace App\Data\Auth;

class SsoUserClaimsDto
{
    /**
     * @param string $sub
     * @param string $email
     * @param string $name
     * @param bool $email_verified
     * @param string|null $tenant_id
     * @param list<string> $products
     */
    public function __construct(
        public readonly string $sub,
        public readonly string $email,
        public readonly string $name,
        public readonly bool $email_verified,
        /** Null for IDP admin users — they are routed to the landlord area. */
        public readonly string|null $tenant_id,
        /** @var list<string> Product slugs the user has access to. */
        public readonly array $products,
    ) {}

    public function isLandlordUser(): bool
    {
        return $this->tenant_id === null;
    }

    public function hasProductAccess(string $slug): bool
    {
        return in_array($slug, $this->products, true);
    }

    /** @return array{first_name: string, last_name: string|null} */
    public function splitName(): array
    {
        $parts = explode(' ', trim($this->name), 2);

        return [
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? null,
        ];
    }

    /** @return array{sub: string, email: string, name: string, email_verified: bool, tenant_id: string|null, products: list<string>} */
    public function toArray(): array
    {
        return [
            'sub' => $this->sub,
            'email' => $this->email,
            'name' => $this->name,
            'email_verified' => $this->email_verified,
            'tenant_id' => $this->tenant_id,
            'products' => $this->products,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        /** @var list<string> $products */
        $products = array_values(array_filter((array) ($data['products'] ?? []), 'is_string'));

        return new self(
            sub: is_string($data['sub'] ?? null) ? $data['sub'] : '',
            email: is_string($data['email'] ?? null) ? $data['email'] : '',
            name: is_string($data['name'] ?? null) ? $data['name'] : '',
            email_verified: (bool) ($data['email_verified'] ?? false),
            tenant_id: isset($data['tenant_id']) && (is_string($data['tenant_id']) || is_int($data['tenant_id'])) ? (string) $data['tenant_id'] : null,
            products: $products,
        );
    }
}

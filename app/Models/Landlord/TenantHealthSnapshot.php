<?php

declare(strict_types=1);

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * The last health reading taken for a customer, kept centrally so the platform overview does not
 * have to open every customer's database on each page load.
 *
 * @property int $id
 * @property string $tenant_id
 * @property array<string, mixed>|null $snapshot
 * @property list<string> $attention
 * @property bool $needs_attention
 * @property string|null $error
 * @property \Carbon\Carbon $captured_at
 */
class TenantHealthSnapshot extends Model
{
    use CentralConnection;

    protected $fillable = ['tenant_id', 'snapshot', 'attention', 'needs_attention', 'error', 'captured_at'];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'attention' => 'array',
            'needs_attention' => 'boolean',
            'captured_at' => 'datetime',
        ];
    }
}

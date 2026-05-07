<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Franzose\ClosureTable\Models\ClosureTable;

/**
 * @property int $closure_id
 * @property int $ancestor_id
 * @property int $descendant_id
 * @property int $depth
 */
class BranchClosure extends ClosureTable
{
    protected $table = 'branches_tree';

    protected $primaryKey = 'closure_id';

    public function getAncestorColumn(): string
    {
        return 'ancestor_id';
    }

    public function getDescendantColumn(): string
    {
        return 'descendant_id';
    }
}

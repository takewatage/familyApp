<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class FamilyUser extends Pivot
{
    use HasUuids;

    protected $table = 'family_user';

    public $incrementing = false;

    protected $keyType = 'string';
}

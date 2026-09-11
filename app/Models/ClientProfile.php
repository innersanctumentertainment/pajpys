<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_name',
        'display_name',
        'phone',
        'country_code',
        'timezone',
        'bio',
        'avatar_path',
        'billing_address_line1',
        'billing_address_line2',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country_code',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

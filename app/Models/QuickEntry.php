<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property string $family_id
 * @property string|null $member_type
 * @property string|null $member_id
 * @property string $name
 * @property string $category_id
 * @property string $payment_method_id
 * @property string|null $shop_id
 * @property string|null $default_amount
 * @property int $sort_order
 * @property int $usage_count
 * @property-read \App\Models\Family $family
 * @property-read \App\Models\User|\App\Models\VirtualUser|null $member
 * @property-read \App\Models\Category $category
 * @property-read \App\Models\PaymentMethod $paymentMethod
 * @property-read \App\Models\Shop|null $shop
 */
class QuickEntry extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'family_id',
        'member_type',
        'member_id',
        'name',
        'category_id',
        'payment_method_id',
        'shop_id',
        'default_amount',
        'sort_order',
        'usage_count',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'sort_order' => 'integer',
        'usage_count' => 'integer',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function member(): MorphTo
    {
        return $this->morphTo();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}

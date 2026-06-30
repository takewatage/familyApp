<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property string $family_id
 * @property string|null $member_type
 * @property string|null $member_id
 * @property string $category_id
 * @property string $payment_method_id
 * @property string|null $shop_id
 * @property string $name
 * @property string $amount
 * @property int $day_of_month
 * @property string $start_date
 * @property string|null $end_date
 * @property bool $is_active
 * @property string|null $last_generated_date
 * @property-read \App\Models\Family $family
 * @property-read \App\Models\User|\App\Models\VirtualUser|null $member
 * @property-read \App\Models\Category $category
 * @property-read \App\Models\PaymentMethod $paymentMethod
 * @property-read \App\Models\Shop|null $shop
 */
class RecurringExpense extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'family_id',
        'member_type',
        'member_id',
        'category_id',
        'payment_method_id',
        'shop_id',
        'name',
        'amount',
        'day_of_month',
        'start_date',
        'end_date',
        'is_active',
        'last_generated_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'day_of_month' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'last_generated_date' => 'date',
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

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}

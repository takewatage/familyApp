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
 * @property string $category_id
 * @property string $payment_method_id
 * @property string|null $shop_id
 * @property string $amount
 * @property string|null $shop_name
 * @property string $expense_date
 * @property string|null $memo
 * @property bool $is_recurring
 * @property string|null $recurring_expense_id
 * @property-read \App\Models\Family $family
 * @property-read \App\Models\User|\App\Models\VirtualUser|null $member
 * @property-read \App\Models\Category $category
 * @property-read \App\Models\PaymentMethod $paymentMethod
 * @property-read \App\Models\Shop|null $shop
 * @property-read \App\Models\RecurringExpense|null $recurringExpense
 */
class Expense extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'family_id',
        'member_type',
        'member_id',
        'category_id',
        'payment_method_id',
        'shop_id',
        'amount',
        'shop_name',
        'expense_date',
        'memo',
        'is_recurring',
        'recurring_expense_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'is_recurring' => 'boolean',
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

    public function recurringExpense(): BelongsTo
    {
        return $this->belongsTo(RecurringExpense::class);
    }
}

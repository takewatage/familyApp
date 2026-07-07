<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

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

    /**
     * 指定家族の有効な繰り返し支出（固定費マスター）。
     * 予算計算（固定費集計）・支払いリマインダーが同一の対象集合を共有するためのスコープ。
     *
     * @param  \Illuminate\Database\Eloquent\Builder<RecurringExpense>  $query
     * @return \Illuminate\Database\Eloquent\Builder<RecurringExpense>
     */
    public function scopeActiveForFamily($query, string $familyId)
    {
        return $query->where('family_id', $familyId)->where('is_active', true);
    }

    /**
     * 指定月における支払日を返す。day_of_month が当月の日数を超える場合は月末に丸める
     * （例: 31 日指定 & 2 月 → 28/29 日）。生成バッチと予算計算（固定費集計）で共通利用する。
     */
    public function paymentDateForMonth(CarbonInterface $month): Carbon
    {
        $monthStart = Carbon::parse($month)->startOfMonth();
        $day = min($this->day_of_month, $monthStart->daysInMonth);

        return $monthStart->day($day)->startOfDay();
    }

    /**
     * 指定月の支払日が開始日〜終了日の範囲内か（当月に支払いが発生する固定費か）。
     * 支払日到来（today 判定）は含まない純粋な期間判定で、生成バッチ・固定費集計の双方が使う。
     */
    public function isDueInMonth(CarbonInterface $month): bool
    {
        $paymentDate = $this->paymentDateForMonth($month);

        if ($this->start_date && $paymentDate->lessThan($this->start_date)) {
            return false;
        }

        if ($this->end_date !== null && $paymentDate->greaterThan($this->end_date)) {
            return false;
        }

        return true;
    }
}

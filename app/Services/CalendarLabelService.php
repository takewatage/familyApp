<?php

namespace App\Services;

use App\Dtos\Calendar\CalendarLabelInputData;
use App\Dtos\Calendar\CalendarLabelResult;
use App\Models\CalendarLabel;
use App\Models\Family;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * 予定のラベル（家族で共有）
 */
class CalendarLabelService
{
    /**
     * 家族の初期ラベル（TimeTree と同じくカラー名をそのままラベル名にする）。
     * カラーは resources/js/Constants/calendarColors.ts のパレットの先頭 10 色と同じ。
     */
    public const DEFAULT_LABELS = [
        ['エメラルド・グリーン', '#2BB673'],
        ['モダーン・サイアン', '#2CB5C8'],
        ['ディープ・スカイブルー', '#2E8FE0'],
        ['パステル・ブラウン', '#B08A6E'],
        ['ミッドナイト・ブラック', '#3A3F47'],
        ['アップル・レッド', '#E5463F'],
        ['フレンチ・ローズ', '#EC5C8A'],
        ['コーラル・ピンク', '#F4837D'],
        ['ブライト・オレンジ', '#F79A2E'],
        ['ソフト・バイオレット', '#9A7BD4'],
    ];

    /**
     * ラベルが 1 件もなければ初期ラベルを作る（家族ごとに初回アクセス時の 1 回だけ）
     */
    public function ensureDefaults(Family $family): void
    {
        if (CalendarLabel::where('family_id', $family->id)->exists()) {
            return;
        }

        DB::transaction(function () use ($family) {
            // 同時アクセスで二重に作らないよう、家族の行をロックしてから確認する
            Family::whereKey($family->id)->lockForUpdate()->first();

            if (CalendarLabel::where('family_id', $family->id)->exists()) {
                return;
            }

            foreach (self::DEFAULT_LABELS as $i => [$name, $color]) {
                CalendarLabel::create([
                    'family_id' => $family->id,
                    'name' => $name,
                    'color' => $color,
                    'sort' => $i,
                ]);
            }
        });
    }

    /**
     * @return CalendarLabelResult[]
     */
    public function list(Family $family): array
    {
        $this->ensureDefaults($family);

        return CalendarLabel::where('family_id', $family->id)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(fn (CalendarLabel $l) => new CalendarLabelResult($l->id, $l->name, $l->color))
            ->all();
    }

    /**
     * ラベル一覧を一括更新する（並び順は配列の順）。家族のラベルが全件そろっている必要がある
     *
     * @param  CalendarLabelInputData[]  $labels
     * @return CalendarLabelResult[]
     *
     * @throws ValidationException
     */
    public function update(Family $family, array $labels): array
    {
        $existingIds = CalendarLabel::where('family_id', $family->id)->pluck('id')->sort()->values()->all();
        $requestedIds = collect($labels)->map(fn (CalendarLabelInputData $l) => $l->id)->sort()->values()->all();

        if ($existingIds !== $requestedIds) {
            throw ValidationException::withMessages([
                'labels' => 'ラベルの一覧が最新ではありません。画面を開き直してください。',
            ]);
        }

        DB::transaction(function () use ($family, $labels) {
            foreach (array_values($labels) as $i => $label) {
                CalendarLabel::where('family_id', $family->id)
                    ->whereKey($label->id)
                    ->update([
                        'name' => trim($label->name),
                        'color' => $label->color,
                        'sort' => $i,
                    ]);
            }
        });

        return $this->list($family);
    }
}

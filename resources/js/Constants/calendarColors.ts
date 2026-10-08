/**
 * 予定のラベルカラー（テーマに左右されない固定パレット）。
 *
 * 1. TimeTree のラベル名に合わせた 10 色
 *    ※ 参照元の色は CSS 変数で、実際の色コードが取れなかったため名前から合わせた近似色。
 *       正確な値が分かったら value を差し替える
 * 2. 色コード指定の 15 色（参照元の値をそのまま使用）
 *
 * 明るい色（イエロー等）は予定チップの文字色を黒にする（Utils/calendarColor の eventTextColor）。
 */
export const CALENDAR_COLORS = [
    // 1. TimeTree のラベル（近似色）
    { name: 'エメラルド・グリーン', value: '#2BB673' },
    { name: 'モダーン・サイアン', value: '#2CB5C8' },
    { name: 'ディープ・スカイブルー', value: '#2E8FE0' },
    { name: 'パステル・ブラウン', value: '#B08A6E' },
    { name: 'ミッドナイト・ブラック', value: '#3A3F47' },
    { name: 'アップル・レッド', value: '#E5463F' },
    { name: 'フレンチ・ローズ', value: '#EC5C8A' },
    { name: 'コーラル・ピンク', value: '#F4837D' },
    { name: 'ブライト・オレンジ', value: '#F79A2E' },
    { name: 'ソフト・バイオレット', value: '#9A7BD4' },
    // 2. 色コード指定
    { name: 'ピンク', value: '#d81b60' },
    { name: 'パープル', value: '#8e24aa' },
    { name: 'ディープ・パープル', value: '#5e35b1' },
    { name: 'インディゴ', value: '#3949ab' },
    { name: 'ブルー', value: '#1e88e5' },
    { name: 'ライト・ブルー', value: '#039be5' },
    { name: 'シアン', value: '#00acc1' },
    { name: 'グリーン', value: '#43a047' },
    { name: 'ライト・グリーン', value: '#7cb342' },
    { name: 'ライム', value: '#c0ca33' },
    { name: 'イエロー', value: '#fdd835' },
    { name: 'アンバー', value: '#ffb300' },
    { name: 'オレンジ', value: '#fb8c00' },
    { name: 'ディープ・オレンジ', value: '#f4511e' },
    { name: 'ダーク・グレイ', value: '#757575' },
] as const

export type CalendarColorName = (typeof CALENDAR_COLORS)[number]['name']

/** 名前からカラーコードを引く */
export function calendarColor(name: CalendarColorName): string {
    return CALENDAR_COLORS.find((c) => c.name === name)?.value ?? DEFAULT_CALENDAR_COLOR
}

export const DEFAULT_CALENDAR_COLOR = '#2BB673'

/**
 * 初期のラベル（TimeTree と同じく、カラー名をそのままラベル名にする）。
 * ラベル名・カラー・並び順は「ラベル名やカラーを変更」で編集できる（モックアップのためページ内のみ）
 */
export const DEFAULT_CALENDAR_LABELS = CALENDAR_COLORS.slice(0, 10).map((c, i) => ({
    id: `label-${i + 1}`,
    name: c.name as string,
    color: c.value as string,
}))

// カレンダーコンポーネント全体で使う型定義

/**
 * 日付キー。'YYYY-MM-DD' 形式の文字列。
 * イベントを日付ごとに引くためのキーとして使う。
 */
export type DateKey = string

/**
 * カレンダーに表示する1件のイベント。
 * 利用側のイベント型をそのまま使いたい場合は、
 * この型を拡張するか、変換関数で CalendarEvent[] に変換して渡す。
 */
export interface CalendarEvent {
    /** 一意なID（v-forのkeyや更新・削除に使用） */
    id: string | number
    /** 表示タイトル */
    title: string
    /**
     * イベントの色。
     * Vuetifyのテーマカラー名（'primary' | 'success' など）か、
     * 任意のCSSカラー文字列（'#ff0000' 等）を許容する。
     */
    color?: string
    /** 表示用の時刻文字列（例: '10:00 - 11:30'）。未指定なら終日扱い。 */
    time?: string
    /** リストやアバターに表示するアイコン名（mdi-xxx） */
    icon?: string
    /** タイトルの前に表示する画像アイコンの URL（例: 誕生日のケーキ） */
    iconImage?: string
    /** 予定に関係する人（リストの右端にアイコンを表示する） */
    participants?: CalendarParticipant[]
    /** 任意の追加データ。利用側が自由に使ってよい。 */
    meta?: Record<string, unknown>
}

/**
 * 予定のラベル（名前とカラー）。予定の色はラベルのカラーで表示する。
 */
export interface CalendarLabel {
    id: string
    name: string
    /** CSS カラー（Constants/calendarColors のパレットから選ぶ） */
    color: string
}

/**
 * 予定の参加者（家族メンバー）。
 * アバター画像がない場合は名前の頭文字を表示する。
 */
export interface CalendarParticipant {
    id: string
    name: string
    avatarUrl?: string | null
}

/**
 * 日付キーごとにイベント配列を引けるマップ。
 * 利用側が `{ '2026-08-01': [ev1, ev2], ... }` の形で渡す。
 */
export type EventMap = Record<DateKey, CalendarEvent[]>

/**
 * 月グリッドの1セル分の情報。
 * useCalendar が生成し、MonthGrid / DayCell が描画に使う。
 */
export interface DayCellData {
    /** 日（1〜31） */
    day: number
    /** 'YYYY-MM-DD' */
    key: DateKey
    /** 曜日（0=日, 6=土） */
    dow: number
    /** 今日かどうか */
    isToday: boolean
    /** 当月外（前月・翌月の埋めセル）かどうか */
    isOtherMonth: boolean
    /** この日のイベント一覧 */
    events: CalendarEvent[]
}

/**
 * 年と月（0始まり）のペア。
 */
export interface YearMonth {
    year: number
    /** 0=1月, 11=12月 */
    month: number
}

/**
 * 予定追加フォームの入力値。
 */
export interface EventFormModel {
    title: string
    time: string
    color: string
}

/**
 * カラー選択肢（予定追加フォームで使用）。
 */
export interface ColorOption {
    title: string
    value: string
}

/**
 * 予定の編集フォームの入力値（フルスクリーンの EventEditForm で使用）。
 * 終日でない場合のみ startTime / endTime を使う。
 */
export interface EventEditModel {
    title: string
    /** 終日の予定か */
    allDay: boolean
    /** 開始日 'YYYY-MM-DD' */
    startDate: DateKey
    /** 終了日 'YYYY-MM-DD'（開始日以降） */
    endDate: DateKey
    /** 開始時刻 'HH:mm'（終日なら空文字） */
    startTime: string
    /** 終了時刻 'HH:mm'（任意。終日なら空文字） */
    endTime: string
    /** ラベルの ID */
    labelId: string
    /** 参加者の ID（複数選択） */
    participantIds: string[]
    /** メモ */
    memo: string
    /** 繰り返しのルール（RFC 5545 の RRULE。繰り返さないなら null） */
    rrule: string | null
}

/**
 * 予定の編集フォームの結果。保存か削除か
 */
export type EventEditResult = { type: 'save'; value: EventEditModel } | { type: 'delete' }

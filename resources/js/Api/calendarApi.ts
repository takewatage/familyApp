import client from './client'
import type {
    CalendarEventRequest,
    CalendarEventResult,
    CalendarLabelInputData,
    CalendarLabelResult,
    CalendarSettingsResult,
} from '@/Types/dto.generated'

/** 繰り返し予定の変更・削除の範囲 */
export type RecurrenceScope = 'this' | 'following' | 'all'

export const calendarApi = {
    /** 期間内（両端を含む）の予定。繰り返しは展開済み、誕生日を含む */
    events(from: string, to: string) {
        return client.get<{ events: CalendarEventResult[] }>('/calendar/events', { params: { from, to } })
    },
    store(data: CalendarEventRequest) {
        return client.post<{ id: string }>('/calendar/events', data)
    },
    update(id: string, data: CalendarEventRequest) {
        return client.put(`/calendar/events/${id}`, data)
    },
    destroy(id: string, scope?: RecurrenceScope, occurrenceDate?: string) {
        return client.delete(`/calendar/events/${id}`, { data: { scope, occurrenceDate } })
    },
    /**
     * カレンダー設定の更新（送った項目だけ更新する）。
     * null は「未設定に戻す」の意味で送るため、生成型（UpdateCalendarSettingsRequest は省略可のみ）ではなく明示する
     */
    updateSettings(data: { birthdayLabelId?: string | null }) {
        return client.put<{ settings: CalendarSettingsResult }>('/calendar/settings', data)
    },
    updateLabels(labels: CalendarLabelInputData[]) {
        return client.put<{ labels: CalendarLabelResult[] }>('/calendar/labels', { labels })
    },
}

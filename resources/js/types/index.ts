export interface User {
    id: number;
    name: string;
    email: string;
    role: 'user' | 'admin';
    email_verified_at: string | null;
    google_id: string | null;
    preferences: UserPreferences | null;
    is_active?: boolean;
    hourly_rate?: string | null;
    contract_type?: string | null;
    contract_start_date?: string | null;
    contract_end_date?: string | null;
    phone?: string | null;
    birth_date?: string | null;
    address?: string | null;
    city?: string | null;
    postal_code?: string | null;
    profile_completeness?: { percentage: number; missing: string[] };
}

export interface UserPreferences {
    language?: 'en' | 'nl';
}

export type TimeEntryStatus = 'draft' | 'submitted' | 'approved' | 'rejected';

export interface AtwWarning {
    type: string;
    message: string;
    hours?: number;
}

export interface WeeklyTotal {
    week: number;
    weekStart: string;
    weekEnd: string;
    totalHours: number;
    warnings: AtwWarning[];
}

export interface TimeEntry {
    id: number;
    user_id: number;
    date: string;
    shift_start: string;
    shift_end: string;
    break_minutes: number;
    total_hours: number;
    notes: string | null;
    status: TimeEntryStatus;
    rejection_reason: string | null;
    reviewed_by: number | null;
    reviewed_at: string | null;
    atw_warnings?: AtwWarning[];
    created_at: string;
    updated_at: string;
}

export interface Invitation {
    id: number;
    email: string;
    token: string;
    invited_by: number;
    inviter?: User;
    expires_at: string;
    accepted_at: string | null;
    created_at: string;
    updated_at: string;
}

export type DocumentType = 'id_front' | 'id_back' | 'contract_signed' | 'other';

export interface Document {
    id: number;
    type: DocumentType;
    original_filename: string;
    mime_type: string;
    file_size: number;
    uploaded_by: number | null;
    uploader_name?: string;
    created_at: string;
}

export type LeaveRequestType = 'vakantie' | 'bijzonder_verlof' | 'onbetaald_verlof';
export type LeaveRequestStatus = 'pending' | 'approved' | 'rejected';

export interface LeaveRequest {
    id: number;
    type: LeaveRequestType;
    start_date: string;
    end_date: string;
    days: number;
    reason: string | null;
    status: LeaveRequestStatus;
    rejection_reason: string | null;
    reviewer_name: string | null;
    reviewed_at: string | null;
    created_at: string;
    // admin-only fields
    user_id?: number;
    user_name?: string;
    user_email?: string;
}

export interface LeaveBalance {
    total: number;
    used: number;
    remaining: number;
}

export interface SickLeave {
    id: number;
    start_date: string;
    end_date: string | null;
    days: number;
    notes: string | null;
    is_active: boolean;
    registrar_name: string | null;
    created_at: string;
}

export interface ShiftPreset {
    id: number;
    name: string;
    short_name: string;
    start_time: string;
    end_time: string;
    color: string;
    sort_order: number;
    is_active: boolean;
}

export interface Shift {
    id: number;
    date: string;
    start_time: string;
    end_time: string;
    user_id: number | null;
    user_name: string | null;
    position: string | null;
    notes: string | null;
    published: boolean;
    planned_hours: number;
    shift_preset_id: number | null;
    preset_name: string | null;
    preset_short_name: string | null;
    preset_color: string | null;
}

export interface TeamMember {
    id: number;
    name: string;
}

export interface Team {
    id: number;
    name: string;
    description: string | null;
    members: TeamMember[];
    member_count: number;
    created_at: string;
}

export interface ScheduleEmployee {
    id: number;
    name: string;
}

export interface CalendarEvent {
    date: string;
    type: 'shift' | 'time_entry' | 'leave' | 'sick';
    label: string;
    detail: string | null;
    color: string;
    status: string | null;
    hours: number | null;
    id: number;
    source_type: string;
    source_id: number;
    start_time?: string;
    end_time?: string;
    position?: string;
    preset_short_name?: string;
}

export interface MonthTotals {
    planned_hours: number;
    worked_hours: number;
    leave_days: number;
    sick_days: number;
}

export interface PageProps {
    auth: {
        user: User;
    };
    flash: {
        success: string | null;
        error: string | null;
    };
    locale: string;
}

export interface StatusCounts {
    draft: number;
    submitted: number;
    approved: number;
    rejected: number;
}

export interface UserWithHours {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    total_hours: number;
    entries_count: number;
    hourly_rate: number;
    status_counts: StatusCounts;
}

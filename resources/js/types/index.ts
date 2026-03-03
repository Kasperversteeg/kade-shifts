export interface User {
    id: number;
    name: string;
    email: string;
    role: 'user' | 'admin';
    email_verified_at: string | null;
    google_id: string | null;
    preferences: UserPreferences | null;
}

export interface UserPreferences {
    language?: 'en' | 'nl';
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

export interface UserWithHours {
    id: number;
    name: string;
    email: string;
    total_hours: number;
}

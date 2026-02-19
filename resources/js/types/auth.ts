export type UserRole = 'admin' | 'moderator' | 'user';

export type User = {
    id: number;
    name: string;
    username: string;
    first_name: string | null;
    last_name: string | null;
    preferred_name: string | null;
    bio: string | null;
    avatar_path: string | null;
    email: string;
    email_verified_at: string | null;
    role: UserRole;
    is_deleted: boolean;
    is_suspended: boolean;
    display_name: string;
    show_real_name: boolean;
    show_email: boolean;
    show_in_directory: boolean;
    notify_replies: boolean;
    notify_messages: boolean;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};

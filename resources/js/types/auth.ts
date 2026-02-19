export type User = {
    id: number;
    name: string;
    username: string;
    email: string;
    first_name: string | null;
    last_name: string | null;
    preferred_name: string | null;
    bio: string | null;
    avatar?: string;
    avatar_path: string | null;
    role: 'admin' | 'moderator' | 'user';
    display_name: string;
    is_deleted: boolean;
    is_suspended: boolean;
    show_real_name: boolean;
    show_email: boolean;
    show_in_directory: boolean;
    email_verified_at: string | null;
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

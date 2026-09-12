export interface SsoApplication {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    icon?: string | null;
    base_url: string;
    callback_url: string;
    client_id: string;
    client_secret?: string;
    is_active: boolean;
    users_count?: number;
    launch_url?: string;
    created_at?: string;
    updated_at?: string;
}

export interface UserWithApps {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    is_admin: boolean;
    applications: Array<{
        id: number;
        name: string;
        slug: string;
        icon?: string | null;
        pivot?: {
            role_in_app?: string | null;
        };
    }>;
    created_at: string;
}

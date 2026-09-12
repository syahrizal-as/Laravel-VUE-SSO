<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    AppWindow,
    Check,
    Search,
    Shield,
    ShieldAlert,
    UserCheck,
    Users,
} from '@lucide/vue';
import { ref } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { SsoApplication, UserWithApps } from '@/types';

interface PaginatedUsers {
    data: UserWithApps[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

const props = defineProps<{
    users: PaginatedUsers;
    applications: SsoApplication[];
    filters: {
        search?: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Hak Akses Pengguna', href: '/admin/users' },
        ],
    },
});

const searchQuery = ref(props.filters.search || '');
const isModalOpen = ref(false);
const selectedUser = ref<UserWithApps | null>(null);

const accessForm = useForm<{
    application_ids: number[];
    is_admin: boolean;
}>({
    application_ids: [],
    is_admin: false,
});

function handleSearch() {
    router.get(
        '/admin/users',
        { search: searchQuery.value },
        { preserveState: true, replace: true },
    );
}

function openAccessModal(user: UserWithApps) {
    selectedUser.value = user;
    accessForm.application_ids = user.applications.map((app) => app.id);
    accessForm.is_admin = !!user.is_admin;
    isModalOpen.value = true;
}

function toggleAppAccess(appId: number) {
    const idx = accessForm.application_ids.indexOf(appId);
    if (idx > -1) {
        accessForm.application_ids.splice(idx, 1);
    } else {
        accessForm.application_ids.push(appId);
    }
}

function selectAllApps() {
    accessForm.application_ids = props.applications.map((app) => app.id);
}

function clearAllApps() {
    accessForm.application_ids = [];
}

function submitAccess() {
    if (!selectedUser.value) return;

    accessForm.put(`/admin/users/${selectedUser.value.id}/access`, {
        onSuccess: () => {
            isModalOpen.value = false;
        },
    });
}
</script>

<template>
    <Head title="Hak Akses Pengguna - Admin SSO" />

    <div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
        <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-border/60 pb-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Manajemen Akses Pengguna</h1>
                    <p class="text-sm text-muted-foreground mt-0.5">
                        Tentukan aplikasi apa saja yang dapat diakses oleh masing-masing pengguna (1 pengguna dapat mengakses banyak aplikasi).
                    </p>
                </div>

                <!-- Search Input -->
                <div class="flex items-center gap-2 max-w-sm w-full">
                    <div class="relative w-full">
                        <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Cari nama atau email..."
                            class="pl-9"
                            @keydown.enter="handleSearch"
                        />
                    </div>
                    <Button variant="outline" size="sm" @click="handleSearch">
                        Cari
                    </Button>
                </div>
            </div>

            <!-- Users Table Card -->
            <Card class="border-border/60 shadow-sm overflow-hidden">
                <CardHeader class="border-b border-border/40 bg-muted/20 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-base font-semibold">Daftar Pengguna SSO</CardTitle>
                            <CardDescription class="text-xs">
                                Total {{ users.total }} pengguna terdaftar
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs uppercase bg-muted/40 text-muted-foreground border-b border-border/60">
                                <tr>
                                    <th class="px-6 py-3.5">Pengguna</th>
                                    <th class="px-6 py-3.5">Peran</th>
                                    <th class="px-6 py-3.5">Aplikasi yang Diizinkan</th>
                                    <th class="px-6 py-3.5 text-right">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/40">
                                <tr
                                    v-for="user in users.data"
                                    :key="user.id"
                                    class="hover:bg-muted/30 transition-colors"
                                >
                                    <!-- User Info -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <Avatar class="size-9 border border-border/60">
                                                <AvatarImage v-if="user.avatar" :src="user.avatar" />
                                                <AvatarFallback class="font-medium text-xs">
                                                    {{ user.name.slice(0, 2).toUpperCase() }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <div class="font-medium text-foreground">{{ user.name }}</div>
                                                <div class="text-xs text-muted-foreground font-mono">{{ user.email }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Role -->
                                    <td class="px-6 py-4">
                                        <Badge
                                            v-if="user.is_admin"
                                            class="gap-1 bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30"
                                        >
                                            <Shield class="size-3" />
                                            Super Admin
                                        </Badge>
                                        <Badge v-else variant="secondary">
                                            Pengguna
                                        </Badge>
                                    </td>

                                    <!-- Allowed Apps -->
                                    <td class="px-6 py-4">
                                        <div v-if="user.is_admin" class="flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                                            <Check class="size-3.5" />
                                            <span>Semua Aplikasi (Hak Akses Super Admin)</span>
                                        </div>
                                        <div
                                            v-else-if="user.applications && user.applications.length > 0"
                                            class="flex flex-wrap gap-1.5"
                                        >
                                            <Badge
                                                v-for="app in user.applications"
                                                :key="app.id"
                                                variant="outline"
                                                class="text-xs gap-1 border-border/60 bg-muted/40 font-normal"
                                            >
                                                <AppWindow class="size-3" />
                                                {{ app.name }}
                                            </Badge>
                                        </div>
                                        <span v-else class="text-xs text-muted-foreground italic">
                                            Belum ada akses aplikasi
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4 text-right">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="h-8 gap-1.5 text-xs"
                                            @click="openAccessModal(user)"
                                        >
                                            <UserCheck class="size-3.5" />
                                            Atur Akses
                                        </Button>
                                    </td>
                                </tr>

                                <tr v-if="!users.data || users.data.length === 0">
                                    <td colspan="4" class="px-6 py-12 text-center text-muted-foreground">
                                        Tidak ada data pengguna ditemukan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

        <!-- Manage Access Dialog -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Atur Hak Akses Aplikasi</DialogTitle>
                    <DialogDescription v-if="selectedUser">
                        Pilih aplikasi yang dapat diakses oleh <strong class="text-foreground">{{ selectedUser.name }}</strong> ({{ selectedUser.email }}).
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitAccess" class="space-y-5">
                    <!-- Admin Privilege Toggle -->
                    <div class="flex items-center justify-between rounded-lg border border-border/60 p-3 bg-muted/20">
                        <div class="space-y-0.5">
                            <Label for="modal_is_admin" class="text-sm font-semibold cursor-pointer">
                                Status Super Admin
                            </Label>
                            <p class="text-xs text-muted-foreground">
                                Super Admin otomatis memiliki akses penuh ke semua aplikasi dan menu konfigurasi.
                            </p>
                        </div>
                        <input
                            type="checkbox"
                            id="modal_is_admin"
                            v-model="accessForm.is_admin"
                            class="size-5 rounded border-border text-primary focus:ring-primary"
                        />
                    </div>

                    <!-- Application Checklist -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <Label class="text-sm font-semibold">Daftar Aplikasi yang Diizinkan:</Label>
                            <div class="flex gap-2 text-xs">
                                <button
                                    type="button"
                                    class="text-primary hover:underline"
                                    @click="selectAllApps"
                                >
                                    Pilih Semua
                                </button>
                                <span class="text-muted-foreground">&bull;</span>
                                <button
                                    type="button"
                                    class="text-muted-foreground hover:underline"
                                    @click="clearAllApps"
                                >
                                    Hapus Semua
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="applications && applications.length > 0"
                            class="grid gap-2 max-h-64 overflow-y-auto pr-1"
                        >
                            <div
                                v-for="app in applications"
                                :key="app.id"
                                class="flex items-center justify-between rounded-lg border border-border/60 p-3 hover:bg-muted/40 cursor-pointer transition-colors"
                                :class="{ 'bg-primary/5 border-primary/40': accessForm.application_ids.includes(app.id) }"
                                @click="toggleAppAccess(app.id)"
                            >
                                <div class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        :checked="accessForm.application_ids.includes(app.id)"
                                        class="size-4 rounded border-border text-primary pointer-events-none"
                                    />
                                    <div>
                                        <div class="font-medium text-sm text-foreground">{{ app.name }}</div>
                                        <div class="text-xs text-muted-foreground font-mono">{{ app.slug }}</div>
                                    </div>
                                </div>
                                <Badge v-if="accessForm.application_ids.includes(app.id)" variant="default" class="text-[10px] h-5">
                                    Diberikan Akses
                                </Badge>
                            </div>
                        </div>

                        <div v-else class="text-xs text-muted-foreground py-4 text-center">
                            Belum ada aplikasi yang terdaftar di sistem.
                        </div>
                    </div>

                    <DialogFooter class="pt-3">
                        <Button type="button" variant="outline" @click="isModalOpen = false">
                            Batal
                        </Button>
                        <Button type="submit" :disabled="accessForm.processing">
                            Simpan Izin Akses
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>

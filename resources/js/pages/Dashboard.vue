<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AppWindow,
    ArrowUpRight,
    CheckCircle2,
    Layers,
    PlusCircle,
    Shield,
    Users,
} from '@lucide/vue';
import PendingInvitationsModal from '@/components/PendingInvitationsModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import type { DashboardInvitation, SsoApplication, Team } from '@/types';

interface Stats {
    total_apps: number;
    active_apps: number;
    total_users: number;
}

defineProps<{
    pendingInvitations?: DashboardInvitation[];
    applications: SsoApplication[];
    stats?: Stats | null;
}>();

defineOptions({
    layout: (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Aplikasi Saya',
                href: props.currentTeam
                    ? dashboard(props.currentTeam.slug)
                    : '/',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Aplikasi Saya - Portal SSO" />

    <PendingInvitationsModal
        v-if="pendingInvitations && pendingInvitations.length > 0"
        :invitations="pendingInvitations"
    />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Admin Stats Bar (If Admin) -->
        <div v-if="stats" class="grid gap-4 md:grid-cols-3">
            <Card class="border-border/60 bg-card/60 shadow-sm">
                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Total Aplikasi</CardTitle>
                    <Layers class="size-4 text-primary" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.total_apps }}</div>
                    <p class="text-xs text-muted-foreground mt-1">
                        {{ stats.active_apps }} aplikasi aktif & siap diakses
                    </p>
                </CardContent>
            </Card>

            <Card class="border-border/60 bg-card/60 shadow-sm">
                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Total Pengguna</CardTitle>
                    <Users class="size-4 text-primary" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.total_users }}</div>
                    <p class="text-xs text-muted-foreground mt-1">Pengguna terdaftar dalam SSO</p>
                </CardContent>
            </Card>

            <Card class="border-border/60 bg-gradient-to-br from-primary/10 via-background to-background shadow-sm flex flex-col justify-between">
                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                    <CardTitle class="text-sm font-medium text-primary">Menu Administrator</CardTitle>
                    <Shield class="size-4 text-primary" />
                </CardHeader>
                <CardContent class="pb-4">
                    <div class="flex gap-2">
                        <Link href="/admin/applications">
                            <Button size="sm" variant="default" class="h-8 gap-1.5 text-xs">
                                <PlusCircle class="size-3.5" />
                                Kelola App
                            </Button>
                        </Link>
                        <Link href="/admin/users">
                            <Button size="sm" variant="outline" class="h-8 gap-1.5 text-xs">
                                <Users class="size-3.5" />
                                Izin User
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Main Heading -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-border/60 pb-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Katalog Aplikasi</h1>
                <p class="text-sm text-muted-foreground mt-0.5">
                    Klik aplikasi yang ingin Anda buka untuk langsung masuk secara otomatis tanpa login lagi.
                </p>
            </div>
            <Badge variant="outline" class="w-fit gap-1 py-1 px-3 border-emerald-500/30 text-emerald-600 dark:text-emerald-400 bg-emerald-500/10">
                <CheckCircle2 class="size-3.5" />
                Single Sign-On Aktif
            </Badge>
        </div>

        <!-- Applications Grid -->
        <div v-if="applications && applications.length > 0" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <Card
                v-for="app in applications"
                :key="app.id"
                class="group relative flex flex-col justify-between border-border/60 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-md hover:border-primary/50"
            >
                <div>
                    <CardHeader class="flex flex-row items-start gap-4 space-y-0 pb-3">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary group-hover:bg-primary group-hover:text-primary-foreground transition-colors">
                            <img
                                v-if="app.icon && (app.icon.startsWith('http') || app.icon.startsWith('/'))"
                                :src="app.icon"
                                :alt="app.name"
                                class="size-7 rounded object-cover"
                            />
                            <AppWindow v-else class="size-6" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <CardTitle class="text-lg font-semibold truncate">{{ app.name }}</CardTitle>
                            <CardDescription class="text-xs truncate font-mono text-muted-foreground">
                                {{ app.base_url }}
                            </CardDescription>
                        </div>
                    </CardHeader>
                    <CardContent class="pt-0">
                        <p class="text-sm text-muted-foreground line-clamp-2 leading-relaxed min-h-[2.5rem]">
                            {{ app.description || 'Aplikasi terintegrasi dengan Single Sign-On.' }}
                        </p>
                    </CardContent>
                </div>

                <CardFooter class="pt-4 border-t border-border/40 bg-muted/20 flex items-center justify-between">
                    <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                        <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse" />
                        Akses Tersedia
                    </span>

                    <a :href="app.launch_url" class="inline-block">
                        <Button size="sm" class="gap-1.5 shadow-sm group-hover:bg-primary">
                            <span>Buka Aplikasi</span>
                            <ArrowUpRight class="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                        </Button>
                    </a>
                </CardFooter>
            </Card>
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="flex min-h-[340px] flex-col items-center justify-center rounded-xl border border-dashed border-border/80 p-8 text-center bg-card/40"
        >
            <div class="flex size-14 items-center justify-center rounded-full bg-muted text-muted-foreground mb-4">
                <Layers class="size-7" />
            </div>
            <h3 class="text-lg font-semibold">Belum Ada Aplikasi yang Diberikan Akses</h3>
            <p class="text-sm text-muted-foreground max-w-md mt-1 mb-6">
                Akun Anda belum memiliki izin untuk mengakses aplikasi internal apapun. Silakan hubungi Administrator untuk mendaftarkan hak akses Anda.
            </p>
            <div v-if="stats" class="flex gap-3">
                <Link href="/admin/applications">
                    <Button class="gap-2">
                        <PlusCircle class="size-4" />
                        Tambah Aplikasi Pertama
                    </Button>
                </Link>
                <Link href="/admin/users">
                    <Button variant="outline" class="gap-2">
                        <Users class="size-4" />
                        Atur Hak Akses User
                    </Button>
                </Link>
            </div>
        </div>
    </div>
</template>

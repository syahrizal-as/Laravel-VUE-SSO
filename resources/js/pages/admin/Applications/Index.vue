<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    AppWindow,
    Check,
    Copy,
    Edit2,
    Eye,
    EyeOff,
    Key,
    Plus,
    RefreshCw,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
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
import type { SsoApplication } from '@/types';

const props = defineProps<{
    applications: SsoApplication[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Kelola Aplikasi', href: '/admin/applications' },
        ],
    },
});

// Modal States
const isModalOpen = ref(false);
const editingApp = ref<SsoApplication | null>(null);
const showSecrets = ref<Record<number, boolean>>({});
const copiedKey = ref<string | null>(null);

const form = useForm({
    name: '',
    slug: '',
    description: '',
    icon: '',
    base_url: '',
    callback_url: '',
    is_active: true,
});

function openCreateModal() {
    editingApp.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    isModalOpen.value = true;
}

function openEditModal(app: SsoApplication) {
    editingApp.value = app;
    form.clearErrors();
    form.name = app.name;
    form.slug = app.slug;
    form.description = app.description || '';
    form.icon = app.icon || '';
    form.base_url = app.base_url;
    form.callback_url = app.callback_url;
    form.is_active = app.is_active;
    isModalOpen.value = true;
}

function submitForm() {
    if (editingApp.value) {
        form.put(`/admin/applications/${editingApp.value.id}`, {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post('/admin/applications', {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
}

function deleteApplication(app: SsoApplication) {
    if (confirm(`Yakin ingin menghapus aplikasi "${app.name}"? Akses semua pengguna ke aplikasi ini akan dicabut.`)) {
        router.delete(`/admin/applications/${app.id}`);
    }
}

function regenerateSecret(app: SsoApplication) {
    if (confirm(`Regenerate secret untuk "${app.name}"? Client Secret lama tidak akan bisa digunakan lagi.`)) {
        router.post(`/admin/applications/${app.id}/regenerate-secret`);
    }
}

function copyToClipboard(text: string, id: string) {
    navigator.clipboard.writeText(text);
    copiedKey.value = id;
    setTimeout(() => {
        copiedKey.value = null;
    }, 2000);
}

function toggleShowSecret(id: number) {
    showSecrets.value[id] = !showSecrets.value[id];
}
</script>

<template>
    <Head title="Kelola Aplikasi - Admin SSO" />

    <div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
        <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-border/60 pb-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Kelola Aplikasi Klien</h1>
                    <p class="text-sm text-muted-foreground mt-0.5">
                        Daftarkan aplikasi web yang dapat diakses oleh pengguna melalui Single Sign-On (SSO).
                    </p>
                </div>
                <Button @click="openCreateModal" class="gap-2 shadow-sm">
                    <Plus class="size-4" />
                    Tambah Aplikasi
                </Button>
            </div>

            <!-- Applications List -->
            <div v-if="applications && applications.length > 0" class="grid gap-6">
                <Card
                    v-for="app in applications"
                    :key="app.id"
                    class="border-border/60 shadow-sm"
                >
                    <CardHeader class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3">
                        <div class="flex items-center gap-3">
                            <div class="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <img
                                    v-if="app.icon && (app.icon.startsWith('http') || app.icon.startsWith('/'))"
                                    :src="app.icon"
                                    :alt="app.name"
                                    class="size-6 rounded object-cover"
                                />
                                <AppWindow v-else class="size-6" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <CardTitle class="text-lg font-semibold">{{ app.name }}</CardTitle>
                                    <Badge :variant="app.is_active ? 'default' : 'secondary'">
                                        {{ app.is_active ? 'Aktif' : 'Non-aktif' }}
                                    </Badge>
                                </div>
                                <CardDescription class="font-mono text-xs text-muted-foreground mt-0.5">
                                    slug: {{ app.slug }} &bull; {{ app.users_count || 0 }} pengguna diizinkan
                                </CardDescription>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                class="gap-1.5 h-8 text-xs"
                                @click="openEditModal(app)"
                            >
                                <Edit2 class="size-3.5" />
                                Edit
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                class="gap-1.5 h-8 text-xs text-destructive hover:bg-destructive/10"
                                @click="deleteApplication(app)"
                            >
                                <Trash2 class="size-3.5" />
                                Hapus
                            </Button>
                        </div>
                    </CardHeader>

                    <CardContent class="grid gap-4 pt-2">
                        <p v-if="app.description" class="text-sm text-muted-foreground">
                            {{ app.description }}
                        </p>

                        <!-- URLs & SSO Keys -->
                        <div class="grid gap-3 rounded-lg border border-border/50 bg-muted/30 p-4 text-xs">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <span class="font-semibold text-muted-foreground">Base URL:</span>
                                    <p class="font-mono text-foreground mt-0.5 break-all">{{ app.base_url }}</p>
                                </div>
                                <div>
                                    <span class="font-semibold text-muted-foreground">SSO Callback URL:</span>
                                    <p class="font-mono text-foreground mt-0.5 break-all">{{ app.callback_url }}</p>
                                </div>
                            </div>

                            <div class="border-t border-border/40 pt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <span class="font-semibold text-muted-foreground">Client ID:</span>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <code class="font-mono bg-background px-2 py-1 rounded border border-border/50 text-[11px] select-all">
                                            {{ app.client_id }}
                                        </code>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="size-6"
                                            @click="copyToClipboard(app.client_id, 'id_' + app.id)"
                                        >
                                            <Check v-if="copiedKey === 'id_' + app.id" class="size-3 text-emerald-500" />
                                            <Copy v-else class="size-3" />
                                        </Button>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold text-muted-foreground">Client Secret:</span>
                                        <button
                                            type="button"
                                            class="text-[11px] text-primary hover:underline flex items-center gap-1"
                                            @click="regenerateSecret(app)"
                                        >
                                            <RefreshCw class="size-3" />
                                            Regenerate
                                        </button>
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <code class="font-mono bg-background px-2 py-1 rounded border border-border/50 text-[11px]">
                                            {{ showSecrets[app.id] ? app.client_secret : '••••••••••••••••••••••••••••••••' }}
                                        </code>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="size-6"
                                            @click="toggleShowSecret(app.id)"
                                        >
                                            <EyeOff v-if="showSecrets[app.id]" class="size-3" />
                                            <Eye v-else class="size-3" />
                                        </Button>
                                        <Button
                                            v-if="app.client_secret"
                                            variant="ghost"
                                            size="icon"
                                            class="size-6"
                                            @click="copyToClipboard(app.client_secret, 'sec_' + app.id)"
                                        >
                                            <Check v-if="copiedKey === 'sec_' + app.id" class="size-3 text-emerald-500" />
                                            <Copy v-else class="size-3" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="flex min-h-[300px] flex-col items-center justify-center rounded-xl border border-dashed border-border/80 p-8 text-center"
            >
                <div class="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground mb-3">
                    <Key class="size-6" />
                </div>
                <h3 class="text-base font-semibold">Belum Ada Aplikasi Klien</h3>
                <p class="text-sm text-muted-foreground max-w-sm mt-1 mb-4">
                    Tambahkan aplikasi pertama Anda (misal Backend ERP atau VMS) untuk mulai menggunakan SSO.
                </p>
                <Button @click="openCreateModal" class="gap-2">
                    <Plus class="size-4" />
                    Tambah Aplikasi
                </Button>
            </div>

        <!-- Add / Edit Dialog -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ editingApp ? 'Edit Aplikasi' : 'Tambah Aplikasi Klien' }}</DialogTitle>
                    <DialogDescription>
                        Konfigurasi informasi aplikasi dan URL callback untuk verifikasi SSO.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="name">Nama Aplikasi *</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="Contoh: Backend ERP, Vendor Management System"
                            required
                        />
                        <span v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="slug">Slug (URL identifier)</Label>
                            <Input
                                id="slug"
                                v-model="form.slug"
                                placeholder="backend-erp"
                            />
                            <span v-if="form.errors.slug" class="text-xs text-destructive">{{ form.errors.slug }}</span>
                        </div>
                        <div class="space-y-2">
                            <Label for="icon">Icon / Logo (URL atau emoji)</Label>
                            <Input
                                id="icon"
                                v-model="form.icon"
                                placeholder="https://... atau /logo.png"
                            />
                            <span v-if="form.errors.icon" class="text-xs text-destructive">{{ form.errors.icon }}</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="description">Deskripsi Singkat</Label>
                        <Input
                            id="description"
                            v-model="form.description"
                            placeholder="Sistem informasi manajemen logistik & keuangan"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="base_url">Base URL Aplikasi *</Label>
                        <Input
                            id="base_url"
                            v-model="form.base_url"
                            placeholder="https://erp.perusahaan.com atau http://localhost:8001"
                            required
                        />
                        <span v-if="form.errors.base_url" class="text-xs text-destructive">{{ form.errors.base_url }}</span>
                    </div>

                    <div class="space-y-2">
                        <Label for="callback_url">SSO Callback URL *</Label>
                        <Input
                            id="callback_url"
                            v-model="form.callback_url"
                            placeholder="https://erp.perusahaan.com/sso/callback"
                            required
                        />
                        <p class="text-[11px] text-muted-foreground">
                            URL aplikasi tujuan yang menerima tiket SSO untuk otomatis login.
                        </p>
                        <span v-if="form.errors.callback_url" class="text-xs text-destructive">{{ form.errors.callback_url }}</span>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input
                            type="checkbox"
                            id="is_active"
                            v-model="form.is_active"
                            class="size-4 rounded border-border text-primary focus:ring-primary"
                        />
                        <Label for="is_active" class="cursor-pointer text-sm font-medium">
                            Aplikasi Aktif (Siap Diakses)
                        </Label>
                    </div>

                    <DialogFooter class="pt-4">
                        <Button type="button" variant="outline" @click="isModalOpen = false">
                            Batal
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ editingApp ? 'Simpan Perubahan' : 'Simpan Aplikasi' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>

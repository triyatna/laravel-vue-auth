<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem } from '@/types';

interface AuthUser {
    email?: string;
    two_factor_confirmed_at?: string | null;
    two_factor_recovery_codes?: string[] | null;
}

interface Props {
    auth: { user: AuthUser | null };
    flash?: { status?: string; message?: string };
}
const page = defineProps<Props>();
const user = computed(() => page.auth.user ?? {});

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Security settings', href: '/settings/security' }];

// QR handling
const qrSrc = ref<string | null>(null);
function loadQr() {
    qrSrc.value = route('settings.security.totp.setup') + '?t=' + Date.now();
}

// Enable 2FA (now includes password)
const enableForm = useForm({ code: '', password: '' });
const showConfirm = ref(false);

function openConfirm() {
    enableForm.clearErrors('password');
    enableForm.password = '';
    showConfirm.value = true;
}
function cancelConfirm() {
    showConfirm.value = false;
}
function submitEnable() {
    enableForm.post(route('settings.security.totp.enable'), {
        preserveScroll: true,
        onSuccess: () => {
            enableForm.reset('code', 'password');
            showConfirm.value = false;
            router.reload({ only: ['auth'] });
        },
        // keep modal open if error so user can correct password
    });
}

// Disable 2FA (still asks password)
const disableForm = useForm({ password: '' });
function submitDisable() {
    disableForm.post(route('settings.security.totp.disable'), {
        preserveScroll: true,
        onSuccess: () => {
            disableForm.reset('password');
            router.reload({ only: ['auth'] });
            qrSrc.value = null;
        },
    });
}

function copyCodes(codes: string[]) {
    navigator.clipboard?.writeText(codes.join('\n'));
}
function downloadCodes(codes: string[]) {
    const blob = new Blob([codes.join('\n')], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'topupin-2fa-recovery-codes.txt';
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Security settings" />

        <SettingsLayout>
            <div class="space-y-8">
                <HeadingSmall
                    title="Two-Factor Authentication"
                    description="Protect your account with a one-time code from your authenticator app."
                />

                <!-- 2FA Card -->
                <section class="rounded-2xl border border-neutral-200 bg-white/90 p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/60">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold">2FA (Authenticator App)</h2>
                            <div class="mt-1">
                                <span
                                    v-if="user?.two_factor_confirmed_at"
                                    class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300"
                                    >Active</span
                                >

                                <span
                                    v-else
                                    class="inline-flex items-center rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-medium text-neutral-700 ring-1 ring-neutral-200 dark:bg-neutral-800 dark:text-neutral-300"
                                    >Inactive</span
                                >
                            </div>
                        </div>

                        <div v-if="!user?.two_factor_confirmed_at" class="mt-2 sm:mt-0">
                            <Button @click="loadQr">Start setup</Button>
                        </div>
                    </div>

                    <!-- Setup -->
                    <div v-if="qrSrc && !user?.two_factor_confirmed_at" class="mt-6 grid gap-6 md:grid-cols-2">
                        <div class="space-y-3">
                            <p class="text-sm text-neutral-600 dark:text-neutral-300">
                                1) Scan this QR with your authenticator app (Google/Microsoft/1Password).<br />
                                2) Enter the 6-digit code to enable 2FA.
                            </p>
                            <div
                                class="flex items-center justify-center rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900"
                            >
                                <img :src="qrSrc" alt="TOTP QR" class="h-64 w-64" loading="eager" />
                            </div>
                        </div>

                        <form @submit.prevent="openConfirm" class="space-y-3">
                            <Label for="code">Enter 6-digit code</Label>
                            <Input
                                id="code"
                                type="text"
                                inputmode="numeric"
                                maxlength="6"
                                v-model.trim="enableForm.code"
                                placeholder="123456"
                                autocomplete="one-time-code"
                                required
                            />
                            <InputError :message="enableForm.errors.code" />
                            <Button type="submit" class="w-full" :disabled="!enableForm.code || enableForm.processing"> Continue </Button>
                            <p class="text-xs text-neutral-500">You’ll be asked to confirm your password in the next step.</p>
                        </form>
                    </div>

                    <!-- Disable -->
                    <div v-if="user?.two_factor_confirmed_at" class="mt-6">
                        <details class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800">
                            <summary class="cursor-pointer text-sm font-medium select-none">Turn off 2FA</summary>
                            <form @submit.prevent="submitDisable" class="mt-3 grid gap-3 sm:max-w-md">
                                <Label for="pwd">Confirm with password</Label>
                                <Input id="pwd" type="password" v-model="disableForm.password" required autocomplete="current-password" />
                                <InputError :message="disableForm.errors.password" />
                                <div class="flex gap-2">
                                    <Button type="submit" :disabled="disableForm.processing">
                                        {{ disableForm.processing ? 'Processing…' : 'Disable 2FA' }}
                                    </Button>
                                </div>
                            </form>
                        </details>
                    </div>
                </section>

                <!-- Recovery Codes -->
                <section class="rounded-2xl border border-neutral-200 bg-white/90 p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/60">
                    <h2 class="text-lg font-semibold">Recovery Codes</h2>
                    <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                        Store these codes safely. Use them if you lose access to your authenticator app.
                    </p>

                    <div v-if="user?.two_factor_confirmed_at && user?.two_factor_recovery_codes?.length" class="mt-4">
                        <div class="grid gap-2 sm:grid-cols-2 md:grid-cols-3">
                            <code
                                v-for="c in user.two_factor_recovery_codes"
                                :key="c"
                                class="rounded-lg bg-neutral-100 px-3 py-2 text-sm dark:bg-neutral-800"
                                >{{ c }}</code
                            >
                        </div>
                        <div class="mt-4 flex gap-2">
                            <Button variant="outline" @click="copyCodes(user.two_factor_recovery_codes!)">Copy</Button>
                            <Button @click="downloadCodes(user.two_factor_recovery_codes!)">Download</Button>
                        </div>
                    </div>

                    <div
                        v-else
                        class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-900/30 dark:bg-amber-900/20 dark:text-amber-300"
                    >
                        Recovery codes will appear after 2FA is enabled.
                    </div>
                </section>
            </div>

            <!-- Password Confirmation Modal -->
            <transition
                enter-active-class="transition ease-out duration-150"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" aria-modal="true" role="dialog">
                    <div
                        class="w-full max-w-md rounded-2xl border border-neutral-200 bg-white p-6 shadow-xl dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <h3 class="text-base font-semibold">Confirm your password</h3>
                        <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            For your security, please confirm your current password to enable 2FA.
                        </p>

                        <form @submit.prevent="submitEnable" class="mt-4 space-y-3">
                            <Label for="enable-pwd">Current password</Label>
                            <Input
                                id="enable-pwd"
                                type="password"
                                v-model="enableForm.password"
                                required
                                autocomplete="current-password"
                                :aria-invalid="!!enableForm.errors.password || undefined"
                            />
                            <InputError :message="enableForm.errors.password" />

                            <div class="mt-2 flex items-center justify-end gap-2">
                                <Button type="button" variant="secondary" @click="cancelConfirm" :disabled="enableForm.processing">Cancel</Button>
                                <Button type="submit" :disabled="enableForm.processing">
                                    {{ enableForm.processing ? 'Enabling…' : 'Confirm & Enable' }}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </transition>
        </SettingsLayout>
    </AppLayout>
</template>

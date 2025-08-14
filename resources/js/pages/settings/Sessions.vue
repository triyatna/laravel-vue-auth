<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem } from '@/types';
import { Bot, LogOut, Monitor, Smartphone, Tablet } from 'lucide-vue-next';

interface SessionItem {
    id: string;
    ip: string | null;
    agent: string;
    is_current: boolean;
    last_seen: string;
    device: 'desktop' | 'mobile' | 'tablet' | 'bot';
    active: boolean;
    last_activity_unix?: number;
}

interface Props {
    sessions: SessionItem[];
    flash?: { status?: string; message?: string };
}
const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Sessions', href: '/settings/sessions' }];

/** Password confirm modal state */
const showConfirm = ref(false);

/** Form to log out all OTHER sessions (requires password) */
const form = useForm({ password: '' });

function openConfirm() {
    form.clearErrors('password');
    form.password = '';
    showConfirm.value = true;
}
function cancelConfirm() {
    if (!form.processing) showConfirm.value = false;
}
function logoutOthers() {
    form.delete(route('security.sessions.others'), {
        onSuccess: () => {
            form.reset('password');
            showConfirm.value = false;
        },
        preserveScroll: true,
    });
}

const iconMap = {
    desktop: Monitor,
    mobile: Smartphone,
    tablet: Tablet,
    bot: Bot,
} as const;
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Sessions" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Browser Sessions" description="Review devices signed in to your account and sign out from other browsers." />

                <section class="rounded-2xl border border-neutral-200 bg-white/90 p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/60">
                    <!-- Sessions grid -->
                    <template v-if="props.sessions.length">
                        <ul class="grid gap-4">
                            <li
                                v-for="s in props.sessions"
                                :key="s.id"
                                class="group rounded-xl border border-neutral-200 p-4 transition-shadow hover:shadow-md dark:border-neutral-800"
                                :class="s.is_current ? 'ring-1 ring-emerald-300/60 dark:ring-emerald-800/60' : ''"
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900"
                                    >
                                        <component :is="iconMap[s.device] || Monitor" class="h-5 w-5" aria-hidden="true" />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="truncate font-medium" :title="s.agent">{{ s.agent }}</span>
                                            <span
                                                v-if="s.is_current"
                                                class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300"
                                            >
                                                This device
                                            </span>
                                        </div>

                                        <div class="mt-1 flex items-center gap-2 text-sm">
                                            <span
                                                v-if="s.active"
                                                class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400"
                                                title="Active within last 90 seconds"
                                            >
                                                <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                                                Active
                                            </span>
                                            <span v-else class="text-neutral-500">Last active · {{ s.last_seen }}</span>
                                        </div>

                                        <div class="text-xs text-neutral-500">IP {{ s.ip ?? '—' }}</div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </template>

                    <p v-else class="text-sm text-neutral-500">No active sessions found for your account.</p>

                    <!-- CTA -->
                    <div class="mt-6 flex items-center justify-between rounded-xl bg-neutral-50 p-4 dark:bg-neutral-800/40">
                        <div class="text-sm text-neutral-700 dark:text-neutral-300">
                            Sign out from all other browsers and devices except this one.
                        </div>
                        <Button @click="openConfirm"> <LogOut class="mr-2 h-4 w-4" /> Log out other sessions </Button>
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
                            For your security, please confirm your current password to sign out from other sessions.
                        </p>

                        <form @submit.prevent="logoutOthers" class="mt-4 space-y-3">
                            <Label for="pwd">Current password</Label>
                            <Input
                                id="pwd"
                                type="password"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                                :aria-invalid="!!form.errors.password || undefined"
                            />
                            <InputError :message="form.errors.password" />

                            <div class="mt-2 flex items-center justify-end gap-2">
                                <Button type="button" variant="secondary" @click="cancelConfirm" :disabled="form.processing">Cancel</Button>
                                <Button type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Signing out…' : 'Confirm & Sign out' }}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </transition>
        </SettingsLayout>
    </AppLayout>
</template>

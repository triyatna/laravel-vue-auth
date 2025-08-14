<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';

import AvatarEditor from '@/components/AvatarEditor.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import PhoneInput from '@/components/PhoneInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type User } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

const page = usePage();
const user = page.props.auth.user as User;

const form = useForm({
    name: user.name,
    email: user.email,
    username: user.username,
    phone: user?.phone ?? '',
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
        replace: true, // avoid pushing a new history entry
        onSuccess: () => refreshAuth(),
    });
};

const refreshAuth = () =>
    router.reload({
        only: ['auth'],
    });
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <div class="space-y-4">
                    <HeadingSmall title="Avatar" description="Change your profile picture." />
                    <AvatarEditor
                        :current-url="user.avatar_url"
                        :fallback-initials="user.name || user.email || 'U'"
                        :upload-route="route('profile.avatar.store')"
                        :delete-route="route('profile.avatar.destroy')"
                        @updated="refreshAuth"
                    />
                </div>
                <div class="space-y-6">
                    <HeadingSmall title="Profile information" description="Update your information" />

                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="username">Username</Label>
                            <Input
                                id="username"
                                class="mt-1 block w-full bg-muted"
                                v-model="form.username"
                                required
                                autocomplete="username"
                                placeholder="Username"
                                readonly
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                class="mt-1 block w-full bg-muted"
                                v-model="form.email"
                                required
                                autocomplete="username"
                                placeholder="Email address"
                                readonly
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>
                        <div v-if="mustVerifyEmail && !user.email_verified_at">
                            <p class="-mt-4 text-sm text-muted-foreground">
                                Your email address is unverified.
                                <Link
                                    :href="route('verification.send')"
                                    method="post"
                                    as="button"
                                    class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                >
                                    Click here to resend the verification email.
                                </Link>
                            </p>

                            <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                                A new verification link has been sent to your email address.
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input id="name" class="mt-1 block w-full" v-model="form.name" required autocomplete="name" placeholder="Full name" />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="phone">Phone (WhatsApp)</Label>
                            <PhoneInput
                                id="phone"
                                v-model="form.phone"
                                :tabindex="3"
                                aria-describedby="phone-help"
                                :emoji="true"
                                placeholder="81234567890"
                                defaultIso="ID"
                            />
                            <InputError class="mt-2" :message="form.errors.phone" />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button :disabled="form.processing">Save</Button>

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </div>

            <!-- <DeleteUser /> -->
        </SettingsLayout>
    </AppLayout>
</template>

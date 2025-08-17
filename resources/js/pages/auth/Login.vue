<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LoaderCircle, Lock, Mail, User } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const showPass = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthBase
        title="Log in to your account"
        description="Enter your credentials to continue"
        variant="cover"
        coverImage="https://images.unsplash.com/photo-1529101091764-c3526daf38fe?q=80&w=1600&auto=format&fit=crop"
    >
        <Head title="Log in" />

        <div
            v-if="status"
            class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-center text-sm font-medium text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-900/15 dark:text-emerald-300"
        >
            {{ status }}
        </div>

        <form method="POST" @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-4">
                <div class="grid gap-2">
                    <Label for="login">Email or Username</Label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 grid w-10 place-items-center text-muted-foreground">
                            <Mail v-if="/@/.test(form.login)" class="h-4 w-4" />
                            <User v-else class="h-4 w-4" />
                        </span>
                        <Input
                            id="login"
                            type="text"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="username"
                            autocapitalize="off"
                            spellcheck="false"
                            inputmode="text"
                            v-model.trim="form.login"
                            placeholder="you@example.com or username"
                            :aria-invalid="!!form.errors.login || undefined"
                            class="pl-10"
                        />
                    </div>
                    <InputError :message="form.errors.login" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">Password</Label>
                        <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm" :tabindex="5">
                            Forgot password?
                        </TextLink>
                    </div>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 grid w-10 place-items-center text-muted-foreground">
                            <Lock class="h-4 w-4" />
                        </span>
                        <Input
                            id="password"
                            :type="showPass ? 'text' : 'password'"
                            required
                            :tabindex="2"
                            autocomplete="current-password"
                            v-model="form.password"
                            placeholder="Your password"
                            class="pr-10 pl-10"
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 grid w-10 place-items-center text-muted-foreground hover:text-foreground"
                            @click="showPass = !showPass"
                            :aria-label="showPass ? 'Hide password' : 'Show password'"
                            :tabindex="6"
                        >
                            <Eye v-if="!showPass" class="h-4 w-4" />
                            <EyeOff v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center gap-3">
                        <Checkbox id="remember" v-model="form.remember" :tabindex="3" />
                        <span>Remember me</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="bg-primary-gradient mt-2 w-full cursor-pointer transition-all hover:-translate-y-0.5 hover:shadow-lg hover:saturate-125 active:translate-y-0 active:shadow-md disabled:cursor-not-allowed"
                    :tabindex="4"
                    :disabled="form.processing"
                >
                    <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Log in
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Don’t have an account?
                <TextLink :href="route('register')" :tabindex="7">Sign up</TextLink>
            </div>
        </form>

        <template #footer> © {{ new Date().getFullYear() }} Topupin. All rights reserved. </template>
    </AuthBase>
</template>

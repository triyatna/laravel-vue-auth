<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { onMounted } from 'vue';

const form = useForm({
    name: '',
    username: '',
    phone: '',
    email: '',
    password: '',
    password_confirmation: '',
    referral_code: '', // optional
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

// Autofill referral dari query (?ref=XXXX atau ?referral=XXXX)
onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const ref = params.get('ref') || params.get('referral');
    if (ref) form.referral_code = ref;
});
</script>

<template>
    <AuthBase title="Create an account" description="Enter your details below to create your account">
        <Head title="Register" />

        <form method="POST" @submit.prevent="submit" class="flex flex-col gap-6" autocomplete="off">
            <div class="grid gap-4">
                <div class="grid gap-2">
                    <Label for="name">Full name</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        v-model.trim="form.name"
                        placeholder="Full name"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="username">Username</Label>
                    <Input
                        id="username"
                        type="text"
                        required
                        :tabindex="2"
                        autocomplete="username"
                        v-model.trim="form.username"
                        placeholder="e.g. johndoe"
                        inputmode="latin"
                    />
                    <p class="text-xs text-muted-foreground">Huruf kecil, angka, titik atau underscore. Min 3, max 30.</p>
                    <InputError :message="form.errors.username" />
                </div>

                <div class="grid gap-2">
                    <Label for="phone">Phone (WhatsApp)</Label>
                    <Input
                        id="phone"
                        type="tel"
                        :tabindex="3"
                        autocomplete="tel"
                        v-model.trim="form.phone"
                        placeholder="+6281234567890"
                        inputmode="tel"
                    />
                    <p class="text-xs text-muted-foreground">Format internasional disarankan. Contoh: +62812xxxxxxx</p>
                    <InputError :message="form.errors.phone" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="4"
                        autocomplete="email"
                        v-model.trim="form.email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="5"
                        autocomplete="new-password"
                        v-model="form.password"
                        placeholder="Password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="6"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        placeholder="Confirm password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <div class="grid gap-2">
                    <Label for="referral">Referral code (optional)</Label>
                    <Input id="referral" type="text" :tabindex="7" v-model.trim="form.referral_code" placeholder="REFCODE (optional)" />
                    <InputError :message="form.errors.referral_code" />
                </div>

                <Button type="submit" class="mt-2 w-full" tabindex="8" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Create account
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="route('login')" class="underline-offset-4" :tabindex="9">Log in</TextLink>
            </div>
        </form>
    </AuthBase>
</template>

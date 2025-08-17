<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    status?: string;
}
const page = defineProps<Props>();
const status = computed(() => page.status ?? '');

const form = useForm<{ code: string }>({ code: '' });

const submit = () => {
    form.post(route('auth.otp.verify'), {
        onFinish: () => form.reset('code'),
    });
};
</script>

<template>
    <AuthBase title="Verify OTP" description="Enter the 6-digit code we sent to your email (or phone)">
        <Head title="Verify OTP" />

        <div v-if="status" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
            {{ status }}
        </div>

        <form method="POST" @submit.prevent="submit" class="flex flex-col gap-6" autocomplete="off">
            <div class="grid gap-2">
                <Label for="code">OTP Code</Label>
                <Input
                    id="code"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="6"
                    placeholder="123456"
                    v-model.trim="form.code"
                    autofocus
                    required
                    :aria-invalid="!!form.errors.code || undefined"
                />
                <InputError :message="form.errors.code" />
                <p class="text-xs text-muted-foreground">OTP valid for a few minutes. Do not share with anyone.</p>
            </div>

            <Button
                type="submit"
                class="mt-2 w-full cursor-pointer bg-primary transition-all hover:-translate-y-0.5 hover:bg-violet-500 hover:shadow-lg hover:saturate-125 active:translate-y-0 active:shadow-md disabled:cursor-not-allowed"
                :disabled="form.processing"
            >
                <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                Verify
            </Button>

            <div class="text-center text-sm text-muted-foreground">
                Entered wrong account?
                <Link :href="route('login')" class="underline underline-offset-4">Back to login</Link>
            </div>
        </form>
    </AuthBase>
</template>

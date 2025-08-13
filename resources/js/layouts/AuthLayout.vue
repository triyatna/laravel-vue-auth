<script setup lang="ts">
const props = defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="grid min-h-screen bg-slate-50 text-slate-900 lg:grid-cols-2 dark:bg-slate-950 dark:text-slate-100">
        <!-- Aside (brand/ilustrasi) -->
        <aside class="relative hidden items-center justify-center overflow-hidden p-12 lg:flex">
            <div
                class="absolute inset-0 -z-10 opacity-70 blur-3xl dark:opacity-60"
                aria-hidden="true"
                style="
                    background:
                        radial-gradient(1000px 600px at 20% 20%, rgba(99, 102, 241, 0.25), transparent 60%),
                        radial-gradient(800px 500px at 80% 60%, rgba(236, 72, 153, 0.25), transparent 60%);
                "
            ></div>
            <div class="max-w-md">
                <slot name="aside">
                    <h2 class="text-3xl font-semibold tracking-tight">Selamat datang di Topupin</h2>
                    <p class="mt-3 text-slate-600 dark:text-slate-300">
                        Platform topup pulsa & game yang cepat, aman, dan bersih. Masuk untuk melanjutkan.
                    </p>
                </slot>
            </div>
        </aside>

        <!-- Main (form auth) -->
        <main class="flex min-h-screen items-center justify-center px-4 py-10">
            <div class="w-full max-w-md">
                <!-- Kartu form -->
                <section class="rounded-2xl bg-white/90 p-6 shadow-md backdrop-blur dark:bg-slate-900/60">
                    <header class="mb-5">
                        <h1 class="text-2xl font-semibold">{{ props.title ?? 'Masuk' }}</h1>
                        <p v-if="props.description" class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                            {{ props.description }}
                        </p>
                    </header>

                    <!-- Konten form dari halaman -->
                    <div class="space-y-4">
                        <slot />
                    </div>
                </section>

                <p class="mt-6 text-center text-xs text-slate-500">
                    By signing up, you agree to our
                    <a href="/terms" class="underline" rel="noopener noreferrer">Terms</a>
                    &amp;
                    <a href="/privacy" class="underline" rel="noopener noreferrer">Privacy Policy</a>.
                </p>
            </div>
        </main>
    </div>
</template>

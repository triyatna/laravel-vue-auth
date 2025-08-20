<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { computed, ref } from 'vue';
const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        variant?: 'cover' | 'boxed';
        coverImage?: string;
        showAside?: boolean;
        showLogo?: boolean;
    }>(),
    {
        variant: 'boxed',
        showAside: true,
        showLogo: false,
    },
);

const isCover = computed(() => props.variant === 'cover');
const imgFailed = ref(false);
</script>

<template>
    <div class="relative min-h-svh overflow-hidden bg-background text-foreground" :class="isCover ? 'grid lg:grid-cols-2' : 'grid'">
        <aside v-if="isCover && showAside" class="relative hidden overflow-hidden lg:block">
            <img
                v-if="coverImage && !imgFailed"
                :src="coverImage"
                class="absolute inset-0 h-full w-full object-cover"
                alt="Auth cover"
                crossorigin="anonymous"
                referrerpolicy="no-referrer"
                @error="imgFailed = true"
            />
            <div
                class="absolute inset-0"
                style="
                    background:
                        linear-gradient(135deg, rgba(37, 99, 235, 0.25), rgba(139, 92, 246, 0.25)),
                        radial-gradient(1000px 600px at 20% 20%, rgba(99, 102, 241, 0.25), transparent 60%),
                        radial-gradient(800px 500px at 80% 60%, rgba(236, 72, 153, 0.25), transparent 60%);
                "
            ></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-black/10 to-transparent"></div>

            <div class="relative z-10 flex h-full items-end p-12">
                <div class="max-w-xl text-white">
                    <slot name="aside">
                        <h2 class="text-3xl font-semibold drop-shadow-sm">Welcome back 👋</h2>
                        <p class="mt-3 text-white/80">Seamless top-ups for phone &amp; games. Fast, secure, reliable.</p>
                    </slot>
                </div>
            </div>
        </aside>

        <main class="relative flex min-h-svh items-center justify-center px-4 py-10">
            <div class="w-full max-w-md">
                <AppLogoIcon v-if="showLogo" class="mx-auto my-4 block size-12 fill-current text-black dark:text-white" />
                <section class="rounded-2xl border border-border/60 bg-card/90 p-6 shadow-xl backdrop-blur supports-[backdrop-filter]:bg-card/70">
                    <header class="mb-6">
                        <h1 class="text-2xl font-semibold">{{ title ?? 'Sign in' }}</h1>
                        <p v-if="description" class="mt-1 text-sm text-muted-foreground">{{ description }}</p>
                    </header>

                    <div class="space-y-4">
                        <slot />
                    </div>
                </section>

                <footer class="mt-6 text-center text-xs text-muted-foreground">
                    <slot name="footer" />
                </footer>
            </div>
        </main>
    </div>
</template>

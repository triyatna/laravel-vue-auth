<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { router, useForm } from '@inertiajs/vue3';
import { Eye, Trash2, Upload, X, ZoomIn, ZoomOut } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface Props {
    currentUrl?: string | null;
    fallbackInitials?: string;
    uploadRoute: string;
    deleteRoute: string;
}
const props = defineProps<Props>();
const emit = defineEmits<{ (e: 'updated'): void }>();

/* UI state */
const showSheet = ref(false);
const showCrop = ref(false);
function openSheet() {
    showSheet.value = true;
}
function closeSheet() {
    showSheet.value = false;
}
const isSmallScreen = ref<boolean>(typeof window !== 'undefined' ? window.matchMedia('(max-width: 600px)').matches : false);
onMounted(() => {
    const mql = window.matchMedia('(max-width: 600px)');
    const handler = (e: MediaQueryListEvent | MediaQueryList) => {
        isSmallScreen.value = 'matches' in e ? e.matches : (e as MediaQueryList).matches;
    };

    handler(mql);
    if (mql.addEventListener) mql.addEventListener('change', handler as (ev: MediaQueryListEvent) => void);
    else mql.addListener(handler as any);

    cleanupFns.push(() => {
        if (mql.removeEventListener) mql.removeEventListener('change', handler as (ev: MediaQueryListEvent) => void);
        else mql.removeListener(handler as any);
    });
});
/* Sumber & preview lokal (agar langsung berubah) */
const runtimeUrl = ref<string | null>(props.currentUrl ?? null);
watch(
    () => props.currentUrl,
    (v) => {
        runtimeUrl.value = v ?? null;
    },
);

const hasAvatar = computed(() => !!runtimeUrl.value);
const isAdding = ref(false); // untuk title modal: Add vs Edit

const previewUrl = ref<string | null>(null); // objectURL file terpilih
const imgEl = new Image();
imgEl.decoding = 'async';

/* Canvas refs */
const viewCanvas = ref<HTMLCanvasElement | null>(null);
const prevCanvas = ref<HTMLCanvasElement | null>(null);

/* Transform */
const scale = ref(1);
let minScale = 1;
const maxScaleFactor = 4;
const offsetX = ref(0);
const offsetY = ref(0);

let dragging = false;
let lastX = 0,
    lastY = 0;

/* Form */
const form = useForm<{ avatar: File | null }>({ avatar: null });
const removing = ref(false);

/* Event handlers */
function openPreview() {
    if (runtimeUrl.value) window.open(runtimeUrl.value, '_blank');
}

function onPickFile(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    closeSheet();
    if (isSmallScreen.value) {
        autoCropAndUpload(file);
        return;
    }
    isAdding.value = !hasAvatar.value; // tandai mode saat mulai upload
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = URL.createObjectURL(file);
    imgEl.onload = () => nextTick(setupViewport);
    imgEl.src = previewUrl.value;
}
function autoCropAndUpload(file: File) {
    const img = new Image();
    const url = URL.createObjectURL(file);

    img.onload = () => {
        const size = 512;
        const cvs = document.createElement('canvas');
        const ctx = cvs.getContext('2d')!;
        cvs.width = size;
        cvs.height = size;

        const iw = img.naturalWidth;
        const ih = img.naturalHeight;

        // object-fit: cover (isi penuh, crop tengah)
        const scale = Math.max(size / iw, size / ih);
        const sw = iw * scale;
        const sh = ih * scale;
        const dx = (size - sw) / 2;
        const dy = (size - sh) / 2;

        ctx.drawImage(img, dx, dy, sw, sh);

        cvs.toBlob(
            (blob) => {
                if (!blob) {
                    URL.revokeObjectURL(url);
                    return;
                }
                const out = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });

                // Preview lokal cepat
                if (runtimeUrl.value?.startsWith('blob:')) URL.revokeObjectURL(runtimeUrl.value);
                runtimeUrl.value = URL.createObjectURL(out);

                // Upload ke server
                form.avatar = out;
                form.post(props.uploadRoute, {
                    forceFormData: true,
                    onSuccess: () => {
                        router.reload({ only: ['auth'] }); // refresh avatar global
                        emit('updated');
                    },
                    onFinish: () => URL.revokeObjectURL(url),
                });
            },
            'image/jpeg',
            0.92,
        );
    };

    img.onerror = () => URL.revokeObjectURL(url);
    img.src = url;
}
function setupViewport() {
    showCrop.value = true;
    nextTick(() => {
        const cvs = viewCanvas.value;
        if (!cvs) return;

        const cssSize = 360;
        const dpr = window.devicePixelRatio || 1;
        cvs.style.width = `${cssSize}px`;
        cvs.style.height = `${cssSize}px`;
        cvs.width = Math.round(cssSize * dpr);
        cvs.height = Math.round(cssSize * dpr);

        const vw = cssSize,
            vh = cssSize;
        const sx = vw / imgEl.naturalWidth;
        const sy = vh / imgEl.naturalHeight;
        minScale = Math.max(sx, sy);
        scale.value = minScale;

        const sw = imgEl.naturalWidth * scale.value;
        const sh = imgEl.naturalHeight * scale.value;
        offsetX.value = (vw - sw) / 2;
        offsetY.value = (vh - sh) / 2;
        if (!isSmallScreen.value) {
            attachPointerHandlers();
        }
        renderAll();
    });
}

function attachPointerHandlers() {
    const cvs = viewCanvas.value;
    if (!cvs) return;

    const onDown = (ev: PointerEvent) => {
        dragging = true;
        lastX = ev.clientX;
        lastY = ev.clientY;
        cvs.setPointerCapture?.(ev.pointerId);
    };
    const onMove = (ev: PointerEvent) => {
        if (!dragging) return;
        const dx = ev.clientX - lastX;
        const dy = ev.clientY - lastY;
        lastX = ev.clientX;
        lastY = ev.clientY;
        offsetX.value += dx;
        offsetY.value += dy;
        clampOffsets();
        renderAll();
    };
    const onUp = (ev: PointerEvent) => {
        dragging = false;
        cvs.releasePointerCapture?.(ev.pointerId);
    };

    cvs.onpointerdown = onDown;
    cvs.onpointermove = onMove;
    window.addEventListener('pointerup', onUp, { passive: true });
    cleanupFns.push(() => {
        cvs.onpointerdown = null;
        cvs.onpointermove = null;
        window.removeEventListener('pointerup', onUp);
    });
}

function clampOffsets() {
    const cssSize = parseFloat(viewCanvas.value!.style.width) || 360;
    const vw = cssSize,
        vh = cssSize;
    const sw = imgEl.naturalWidth * scale.value;
    const sh = imgEl.naturalHeight * scale.value;
    const minX = Math.min(0, vw - sw);
    const minY = Math.min(0, vh - sh);
    if (offsetX.value < minX) offsetX.value = minX;
    if (offsetX.value > 0) offsetX.value = 0;
    if (offsetY.value < minY) offsetY.value = minY;
    if (offsetY.value > 0) offsetY.value = 0;
}

const zoomPct = ref(0);
function onZoom(delta: number) {
    if (isSmallScreen.value) return;
    const cssSize = parseFloat(viewCanvas.value!.style.width) || 360;
    const vw = cssSize,
        vh = cssSize;
    const prev = scale.value;
    const next = Math.min(minScale * maxScaleFactor, Math.max(minScale, prev + delta));
    if (next === prev) return;

    const cx = vw / 2,
        cy = vh / 2;
    const gx = (cx - offsetX.value) / prev;
    const gy = (cy - offsetY.value) / prev;
    scale.value = next;
    offsetX.value = cx - gx * next;
    offsetY.value = cy - gy * next;

    clampOffsets();
    zoomPct.value = Math.round(((next - minScale) / (minScale * (maxScaleFactor - 1))) * 100);
    renderAll();
}
function onZoomSlider(input: number) {
    if (isSmallScreen.value) return;
    const target = minScale + minScale * (maxScaleFactor - 1) * (input / 100);
    onZoom(target - scale.value);
}

/* Rendering */
function renderAll() {
    drawToCanvas(viewCanvas.value);
    drawToPreview(prevCanvas.value, 96);
}

function drawToCanvas(cvs: HTMLCanvasElement | null) {
    if (!cvs) return;
    const ctx = cvs.getContext('2d')!;
    const dpr = window.devicePixelRatio || 1;

    ctx.save();
    ctx.clearRect(0, 0, cvs.width, cvs.height);
    ctx.scale(dpr, dpr);
    ctx.setTransform(scale.value, 0, 0, scale.value, offsetX.value, offsetY.value);
    ctx.drawImage(imgEl, 0, 0);
    ctx.restore();
}

function drawToPreview(cvs: HTMLCanvasElement | null, size = 96) {
    if (!cvs) return;
    const dpr = window.devicePixelRatio || 1;
    cvs.style.width = `${size}px`;
    cvs.style.height = `${size}px`;
    cvs.width = Math.round(size * dpr);
    cvs.height = Math.round(size * dpr);

    const ctx = cvs.getContext('2d')!;
    ctx.save();
    ctx.clearRect(0, 0, cvs.width, cvs.height);
    ctx.scale(dpr, dpr);

    ctx.beginPath();
    ctx.arc(size / 2, size / 2, size / 2, 0, Math.PI * 2);
    ctx.closePath();
    ctx.clip();

    const mainCss = parseFloat(viewCanvas.value!.style.width) || 360;
    const ratio = size / mainCss;
    ctx.setTransform(scale.value * ratio, 0, 0, scale.value * ratio, offsetX.value * ratio, offsetY.value * ratio);
    ctx.drawImage(imgEl, 0, 0);
    ctx.restore();
}

/* Save 512x512 + auto-refresh global */
function saveCropped() {
    const size = 512;
    const cvs = document.createElement('canvas');
    const ctx = cvs.getContext('2d')!;
    cvs.width = size;
    cvs.height = size;

    ctx.save();
    const mainCss = parseFloat(viewCanvas.value!.style.width) || 360;
    const ratio = size / mainCss;
    ctx.setTransform(scale.value * ratio, 0, 0, scale.value * ratio, offsetX.value * ratio, offsetY.value * ratio);
    ctx.drawImage(imgEl, 0, 0);
    ctx.restore();

    cvs.toBlob(
        (blob) => {
            if (!blob) return;
            const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });

            // 1) Update preview lokal segera
            if (runtimeUrl.value?.startsWith('blob:')) URL.revokeObjectURL(runtimeUrl.value);
            runtimeUrl.value = URL.createObjectURL(file);

            // 2) Kirim ke server
            form.avatar = file;
            form.post(props.uploadRoute, {
                forceFormData: true,
                onSuccess: () => {
                    router.reload({ only: ['auth'] });
                    cancelCrop();
                    emit('updated');
                },
            });
        },
        'image/jpeg',
        0.92,
    );
}

/* Remove + auto-refresh global */
function removeAvatar() {
    removing.value = true;
    form.delete(props.deleteRoute, {
        onFinish: () => (removing.value = false),
        onSuccess: () => {
            if (runtimeUrl.value?.startsWith('blob:')) URL.revokeObjectURL(runtimeUrl.value);
            runtimeUrl.value = null;
            router.reload({ only: ['auth'] });
            emit('updated');
        },
    });
}

/* Cleanup */
const cleanupFns: Array<() => void> = [];
onBeforeUnmount(() => {
    cleanupFns.forEach((fn) => fn());
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
});

function cancelCrop() {
    showCrop.value = false;
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
}
</script>

<template>
    <div class="flex items-center gap-5">
        <!-- Avatar -->
        <div class="flex flex-col items-center md:flex-row">
            <div class="relative m-4 h-24 w-24">
                <img
                    v-if="runtimeUrl"
                    :src="runtimeUrl"
                    alt="Avatar"
                    class="h-24 w-24 rounded-full object-cover ring-1 ring-neutral-200 dark:ring-neutral-700"
                />
                <div
                    v-else
                    class="flex h-24 w-24 items-center justify-center rounded-full bg-neutral-200 text-xl font-semibold text-neutral-700 ring-1 ring-neutral-300 dark:bg-neutral-800 dark:text-neutral-300 dark:ring-neutral-700"
                    aria-label="Avatar placeholder"
                >
                    {{ (fallbackInitials || 'U').slice(0, 2).toUpperCase() }}
                </div>

                <!-- Overlay button: Add (no avatar) / Edit (has avatar) -->
                <button
                    type="button"
                    class="group absolute inset-0 grid place-items-center rounded-full bg-gradient-to-t from-black/40 via-black/0 to-black/0 opacity-0 transition-opacity hover:opacity-100 focus-visible:opacity-100"
                    @click="openSheet"
                    :aria-label="hasAvatar ? 'Edit avatar' : 'Add avatar'"
                    :title="hasAvatar ? 'Edit avatar' : 'Add avatar'"
                >
                    <span
                        class="inline-flex items-center gap-2 rounded-full bg-white/90 px-3 py-1.5 text-sm font-medium text-neutral-900 ring-1 ring-neutral-200 backdrop-blur dark:bg-neutral-900/90 dark:text-neutral-100 dark:ring-neutral-700"
                    >
                        <Upload class="h-4 w-4" /> {{ hasAvatar ? 'Edit' : 'Add' }}
                    </span>
                </button>
            </div>

            <div class="text-sm text-neutral-600 dark:text-neutral-300">JPG, PNG, WEBP up to 4MB. Square images look best.</div>
        </div>
    </div>

    <!-- Action sheet -->
    <transition
        enter-active-class="transition ease-out duration-150"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="showSheet" class="fixed inset-0 z-50 grid place-items-end bg-black/40 p-4 sm:place-items-center">
            <div
                class="w-full max-w-md overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-xl dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div class="divide-y divide-neutral-200 dark:divide-neutral-800">
                    <!-- Upload / Change (selalu ada) -->
                    <label class="flex cursor-pointer items-center gap-2 px-4 py-3 hover:bg-neutral-50 dark:hover:bg-neutral-800/60">
                        <Upload class="h-4 w-4" />
                        <span>{{ hasAvatar ? 'Change photo' : 'Upload photo' }}</span>
                        <input class="sr-only" type="file" accept="image/png,image/jpeg,image/webp" @change="onPickFile" />
                    </label>

                    <!-- Hanya tampil jika sudah ada avatar -->
                    <template v-if="hasAvatar">
                        <button
                            class="flex w-full items-center gap-2 px-4 py-3 text-left hover:bg-neutral-50 dark:hover:bg-neutral-800/60"
                            @click="openPreview"
                        >
                            <Eye class="h-4 w-4" />
                            <span>Preview photo</span>
                        </button>

                        <button
                            class="flex w-full items-center gap-2 px-4 py-3 text-left text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                            @click="removeAvatar"
                            :disabled="removing"
                        >
                            <Trash2 class="h-4 w-4" />
                            <span>{{ removing ? 'Removing…' : 'Remove photo' }}</span>
                        </button>
                    </template>
                </div>

                <div class="flex items-center justify-end gap-2 px-4 py-3">
                    <Button variant="secondary" @click="closeSheet"><X class="mr-2 h-4 w-4" /> Close</Button>
                </div>
            </div>
        </div>
    </transition>

    <!-- Crop modal -->
    <transition
        enter-active-class="transition ease-out duration-150"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="showCrop && !isSmallScreen" class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" aria-modal="true" role="dialog">
            <div
                class="w-full max-w-3xl overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-2xl dark:border-neutral-800 dark:bg-neutral-900"
            >
                <div class="flex items-center justify-between border-b border-neutral-200 p-4 dark:border-neutral-800">
                    <h3 class="text-base font-semibold">{{ isAdding ? 'Add avatar' : 'Edit avatar' }}</h3>
                    <button class="rounded-md p-2 hover:bg-neutral-50 dark:hover:bg-neutral-800" @click="cancelCrop"><X class="h-4 w-4" /></button>
                </div>

                <div class="grid gap-6 p-4 md:grid-cols-[1fr_280px]">
                    <div>
                        <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-2 dark:border-neutral-800 dark:bg-neutral-900">
                            <canvas ref="viewCanvas" class="block aspect-square w-full rounded-lg bg-white dark:bg-neutral-950"></canvas>
                        </div>
                        <div v-if="!isSmallScreen" class="mt-4 flex items-center gap-3">
                            <button
                                class="rounded-md border border-neutral-200 p-2 hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800"
                                @click="onZoom(-0.1)"
                            >
                                <ZoomOut class="h-4 w-4" />
                            </button>
                            <input
                                type="range"
                                min="0"
                                max="100"
                                v-model.number="zoomPct"
                                @input="onZoomSlider(zoomPct)"
                                class="h-2 w-full cursor-pointer appearance-none rounded-full bg-neutral-200 accent-neutral-900 dark:bg-neutral-700"
                            />
                            <button
                                class="rounded-md border border-neutral-200 p-2 hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800"
                                @click="onZoom(0.1)"
                            >
                                <ZoomIn class="h-4 w-4" />
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-neutral-500">
                            <span v-if="!isSmallScreen">Drag to reposition. Zoom to adjust the crop.</span>
                            <span v-else>Auto-fit crop is applied on mobile. Just tap Save.</span>
                        </p>
                    </div>

                    <div>
                        <div class="space-y-2">
                            <div class="text-sm font-medium">Preview</div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid place-items-center gap-2">
                                    <div class="h-24 w-24 overflow-hidden rounded-full ring-1 ring-neutral-200 dark:ring-neutral-800">
                                        <canvas ref="prevCanvas" class="h-full w-full"></canvas>
                                    </div>
                                    <div class="text-xs text-neutral-500">Preview</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-end gap-2">
                            <Button variant="secondary" @click="cancelCrop"><X class="mr-2 h-4 w-4" /> Cancel</Button>
                            <Button @click="saveCropped">Save</Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

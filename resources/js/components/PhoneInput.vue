<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface Props {
    modelValue: string;
    id?: string;
    tabindex?: number;
    required?: boolean;
    disabled?: boolean;
    placeholder?: string;
    ariaDescribedby?: string;
    emoji?: boolean;
    defaultIso?: string;
    emitPlus?: boolean;
}
const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    placeholder: '81234567890',
    emoji: true,
    defaultIso: 'ID',
    emitPlus: false,
});
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

type Country = { iso: string; name: string; dial: string; flag: string; example?: string };
const COUNTRIES: Country[] = [
    { iso: 'ID', name: 'Indonesia', dial: '62', flag: '🇮🇩', example: '81234567890' },
    { iso: 'SG', name: 'Singapore', dial: '65', flag: '🇸🇬', example: '81234567' },
    { iso: 'MY', name: 'Malaysia', dial: '60', flag: '🇲🇾', example: '123456789' },
    { iso: 'PH', name: 'Philippines', dial: '63', flag: '🇵🇭', example: '9123456789' },
    { iso: 'TH', name: 'Thailand', dial: '66', flag: '🇹🇭', example: '812345678' },
    { iso: 'VN', name: 'Vietnam', dial: '84', flag: '🇻🇳', example: '912345678' },
    { iso: 'JP', name: 'Japan', dial: '81', flag: '🇯🇵', example: '9012345678' },
    { iso: 'AU', name: 'Australia', dial: '61', flag: '🇦🇺', example: '412345678' },
];

const defaultCountry = COUNTRIES.find((c) => c.iso === (props.defaultIso || 'ID').toUpperCase()) || COUNTRIES[0];
const selected = ref<Country>(defaultCountry);
const localNumber = ref('');

/* Dropdown & search */
const open = ref(false);
const query = ref('');
const filtered = computed(() => COUNTRIES.filter((c) => (c.name + c.iso + c.dial).toLowerCase().includes(query.value.toLowerCase())));
function toggle() {
    open.value = !open.value;
    if (open.value) query.value = '';
}
function choose(c: Country) {
    selected.value = c;
    open.value = false;
}

/* Close saat klik di luar / Esc */
function onDocClick(e: MouseEvent) {
    const root = containerRef.value;
    if (!root) return;
    if (!root.contains(e.target as Node)) open.value = false;
}
function onEsc(e: KeyboardEvent) {
    if (e.key === 'Escape') open.value = false;
}
onMounted(() => {
    document.addEventListener('click', onDocClick, { passive: true });
    document.addEventListener('keydown', onEsc);
});
onBeforeUnmount(() => {
    document.removeEventListener('click', onDocClick);
    document.removeEventListener('keydown', onEsc);
});
const containerRef = ref<HTMLDivElement | null>(null);

const countryBadge = computed(() => (props.emoji ? selected.value.flag : selected.value.iso));
const displayPlaceholder = computed(() => props.placeholder || selected.value.example || '81234567890');

function mergeToOutput(): string {
    const cc = selected.value.dial;
    const digits = localNumber.value.replace(/\D+/g, '');
    const normalizedLocal = digits.replace(/^0+/, '');
    const merged = cc + normalizedLocal;
    return props.emitPlus ? `+${merged}` : merged;
}

const isValid = computed(() => {
    const out = mergeToOutput();
    return /^\+?\d{8,15}$/.test(out);
});

function hydrateFromModel(val: string) {
    const raw = (val || '').toString();
    const digits = raw.replace(/\D+/g, '');
    if (!digits) {
        selected.value = defaultCountry;
        localNumber.value = '';
        return;
    }
    const match = [...COUNTRIES].sort((a, b) => b.dial.length - a.dial.length).find((c) => digits.startsWith(c.dial));
    if (match) {
        selected.value = match;
        localNumber.value = digits.slice(match.dial.length).replace(/^0+/, '');
    } else {
        selected.value = defaultCountry;
        localNumber.value = digits.startsWith('0') ? digits.slice(1) : digits;
    }
}

/* Sinkronisasi dua arah */
watch(
    () => props.modelValue,
    (v) => {
        const currentOut = mergeToOutput();
        const incomingDigits = (v || '').replace(/\D+/g, '');
        const currentDigits = currentOut.replace(/\D+/g, '');
        if (incomingDigits !== currentDigits) hydrateFromModel(v || '');
    },
    { immediate: true },
);

watch([selected, () => localNumber.value], () => {
    emit('update:modelValue', mergeToOutput());
});
</script>

<template>
    <div class="relative" ref="containerRef">
        <div
            class="flex items-stretch overflow-hidden rounded-xl bg-white/80 ring-1 ring-slate-200 ring-inset focus-within:ring-2 focus-within:ring-indigo-500 dark:bg-slate-900/60 dark:ring-slate-700"
            :class="!isValid && localNumber ? 'ring-rose-300 focus-within:ring-rose-500 dark:ring-rose-800' : ''"
        >
            <!-- Prefix: FLAG/ISO + +CODE -->
            <button
                type="button"
                class="flex min-w-[90px] items-center gap-2 px-3 text-sm hover:bg-slate-50 dark:hover:bg-slate-800"
                @click="toggle"
                :tabindex="tabindex"
                :aria-expanded="open"
                :aria-controls="id ? `${id}-listbox` : undefined"
                :aria-label="`Change country (${selected.name})`"
                title="Change country code"
            >
                <span
                    class="inline-flex h-5 min-w-5 items-center justify-center rounded bg-white/70 px-1 text-base leading-none ring-1 ring-slate-200 ring-inset dark:bg-slate-900/70 dark:ring-slate-700"
                >
                    {{ countryBadge }}
                </span>
                <span class="text-slate-600 dark:text-slate-300">+{{ selected.dial }}</span>
            </button>

            <div class="my-2 w-px bg-slate-200 dark:bg-slate-700" aria-hidden="true" />

            <input
                :id="id"
                type="tel"
                inputmode="tel"
                autocomplete="tel"
                class="min-w-0 flex-1 bg-transparent px-3 py-2 text-sm outline-none placeholder:text-slate-400 dark:text-slate-100"
                :placeholder="displayPlaceholder"
                :required="required"
                :disabled="disabled"
                :tabindex="(tabindex ?? 0) + 1"
                :aria-describedby="ariaDescribedby"
                v-model="localNumber"
                @input="
                    (e: any) => {
                        const raw = e.target.value || '';
                        const digits = raw.replace(/\D+/g, '');
                        localNumber = digits.replace(/^0+/, ''); //
                    }
                "
                @paste.prevent="
                    (e: any) => {
                        const txt = e.clipboardData?.getData('text') || '';
                        const digits = txt.replace(/\D+/g, '').replace(/^0+/, '');
                        localNumber = digits;
                    }
                "
                @blur="
                    () => {
                        localNumber = (localNumber || '').replace(/\D+/g, '').replace(/^0+/, '');
                    }
                "
            />
        </div>

        <!-- Country list -->
        <div
            v-if="open"
            class="absolute z-50 mt-2 w-full rounded-xl border border-slate-200 bg-white p-2 shadow-md dark:border-slate-800 dark:bg-slate-900"
            :id="id ? `${id}-listbox` : undefined"
            role="listbox"
        >
            <input
                type="text"
                v-model="query"
                placeholder="Search country"
                class="mb-2 w-full rounded-lg border-0 bg-slate-50 px-3 py-2 text-sm ring-1 ring-slate-200 ring-inset placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500 dark:bg-slate-900 dark:ring-slate-700"
            />
            <ul class="max-h-60 overflow-auto">
                <li
                    v-for="c in filtered"
                    :key="c.iso"
                    role="option"
                    @click="choose(c)"
                    class="flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-800"
                    :aria-selected="c.iso === selected.iso"
                >
                    <span
                        class="inline-flex h-5 min-w-5 items-center justify-center rounded bg-white/70 px-1 text-base leading-none ring-1 ring-slate-200 ring-inset dark:bg-slate-900/70 dark:ring-slate-700"
                    >
                        {{ emoji ? c.flag : c.iso }}
                    </span>
                    <span class="flex-1 truncate">{{ c.name }}</span>
                    <span class="text-slate-500 dark:text-slate-400">+{{ c.dial }}</span>
                </li>
                <li v-if="!filtered.length" class="px-3 py-2 text-sm text-slate-500">No results</li>
            </ul>
            <p v-if="selected.example" class="mt-2 px-1 text-xs text-slate-500">Example: +{{ selected.dial }}{{ selected.example }}</p>
        </div>

        <p v-if="!isValid && localNumber" class="mt-1 text-xs text-rose-600">
            Invalid phone format. Use international format without spaces or dashes.
        </p>
    </div>
</template>

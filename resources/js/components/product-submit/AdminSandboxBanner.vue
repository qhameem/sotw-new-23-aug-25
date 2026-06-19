<template>
  <div
    class="rounded-xl border p-4 shadow-sm transition-colors"
    :class="modelValue.sandbox_mode ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-slate-50/80'"
  >
    <div class="flex items-start gap-3">
      <input
        id="sandbox-mode-top"
        type="checkbox"
        :checked="!!modelValue.sandbox_mode"
        :disabled="isLoading"
        @change="toggleSandboxMode($event.target.checked)"
        class="mt-1 h-4 w-4 rounded border-amber-300 text-amber-600 focus:ring-amber-500 disabled:cursor-not-allowed"
      >
      <div class="min-w-0 flex-1">
        <label for="sandbox-mode-top" class="flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-900">
          Sandbox Mode
          <span class="inline-flex items-center rounded-full bg-slate-900 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-white">Admin only</span>
          <span
            v-if="modelValue.sandbox_mode"
            class="inline-flex items-center rounded-full bg-amber-200 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-amber-900"
          >
            Active
          </span>
        </label>
        <p class="mt-1 text-sm text-slate-600">
          Test submit flows here without writing anything to the database. The AI Auto-fill section stays available below.
        </p>
        <div
          v-if="modelValue.sandbox_mode"
          class="mt-3 rounded-xl border border-amber-300 bg-white px-4 py-3 text-sm text-amber-900"
        >
          <p class="font-semibold">Sandbox is active.</p>
          <p class="mt-1">Validation is skipped, and no product will be inserted or updated while this mode is on.</p>
        </div>
        <div
          v-if="modelValue.sandbox_mode && sandboxNotice"
          class="mt-3 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
        >
          {{ sandboxNotice }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  sandboxNotice: {
    type: String,
    default: '',
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue']);

const toggleSandboxMode = (checked) => {
  emit('update:modelValue', {
    ...props.modelValue,
    sandbox_mode: checked,
  });
};
</script>

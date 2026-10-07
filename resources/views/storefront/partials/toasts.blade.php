<div class="pointer-events-none fixed inset-x-0 bottom-20 z-[60] flex flex-col items-center gap-2 px-4 lg:top-24 lg:right-6 lg:bottom-auto lg:left-auto lg:items-end" aria-live="polite">
    <template x-for="toast in $store.toast.items" :key="toast.id">
        <div class="pointer-events-auto flex animate-fade-up items-center gap-2.5 rounded-2xl bg-ink px-4 py-3 text-sm font-medium text-canvas shadow-pop">
            <span x-show="toast.type === 'error'" class="text-rose-400 dark:text-rose-600"><x-icon name="alert-circle" class="size-5" /></span>
            <span x-show="toast.type !== 'error'" :class="toast.type === 'success' ? 'text-emerald-400 dark:text-emerald-600' : 'text-brand-300 dark:text-brand-600'"><x-icon name="circle-check" class="size-5" /></span>
            <span x-text="toast.message"></span>
        </div>
    </template>
</div>

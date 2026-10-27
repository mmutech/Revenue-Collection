<div
    x-data="{
        toasts: [],
        add(detail) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, message: detail.message ?? detail, type: detail.type ?? 'success' });
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 4500);
        }
    }"
    @toast.window="add($event.detail)"
    class="pointer-events-none fixed right-4 top-4 z-[100] flex w-full max-w-sm flex-col gap-2"
>
    @if (session('status'))
        <div class="pointer-events-auto flex items-start gap-3 rounded-xl border border-emerald-200 bg-white p-4 shadow-lg shadow-emerald-900/5">
            <x-icon name="check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
            <p class="flex-1 text-sm text-slate-700">{{ session('status') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="pointer-events-auto flex items-start gap-3 rounded-xl border border-rose-200 bg-white p-4 shadow-lg shadow-rose-900/5">
            <x-icon name="x-circle" class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
            <p class="flex-1 text-sm text-slate-700">{{ session('error') }}</p>
        </div>
    @endif
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-2 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            class="pointer-events-auto flex items-start gap-3 rounded-xl border bg-white p-4 shadow-lg"
            :class="toast.type === 'error' ? 'border-rose-200' : 'border-emerald-200'"
        >
            <x-icon name="check-circle" x-show="toast.type !== 'error'" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
            <x-icon name="x-circle" x-show="toast.type === 'error'" class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
            <p class="flex-1 text-sm text-slate-700" x-text="toast.message"></p>
        </div>
    </template>
</div>

<div
    x-data="{ show: false, message: '' }"
    x-on:notify.window="message = $event.detail.message; show = true; setTimeout(() => show = false, 3500)"
    x-show="show"
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    style="display: none;"
    class="fixed inset-x-4 bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-[60] flex items-center gap-2 rounded-lg bg-gray-900 dark:bg-gray-700 px-4 py-3 text-sm font-medium text-white shadow-lg sm:inset-x-auto sm:right-5 sm:bottom-5"
>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 text-emerald-400">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
    </svg>
    <span x-text="message"></span>
</div>

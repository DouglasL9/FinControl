<button
    type="button"
    x-data="{}"
    @click="$store.theme.toggle()"
    :aria-label="$store.theme.dark ? 'Ativar modo claro' : 'Ativar modo noturno'"
    :aria-pressed="$store.theme.dark.toString()"
    title="Alternar modo de cor"
    class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2"
>
    <svg x-cloak x-show="$store.theme.dark" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
        <circle cx="12" cy="12" r="4" stroke-width="1.8" />
        <path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" stroke-width="1.8" stroke-linecap="round" />
    </svg>
    <svg x-cloak x-show="!$store.theme.dark" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
        <path d="M20.2 15.1A8.5 8.5 0 0 1 8.9 3.8 8.5 8.5 0 1 0 20.2 15.1Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
    <span class="sr-only" x-text="$store.theme.dark ? 'Modo noturno ativado' : 'Modo claro ativado'"></span>
</button>

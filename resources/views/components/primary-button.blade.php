<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-xl border border-transparent bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:brightness-95 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2 active:brightness-90 disabled:opacity-50']) }}>
    {{ $slot }}
</button>

<x-app-layout>
    <div
        class="mx-auto max-w-screen-xl space-y-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-8"
        x-data="{
            query: '',
            active: '',
            init() {
                const headings = [...this.$refs.article.querySelectorAll('h2[id], h3[id]')];
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            this.active = entry.target.id;
                        }
                    });
                }, { rootMargin: '-80px 0px -70% 0px' });
                headings.forEach((heading) => observer.observe(heading));
            },
            matches(title) {
                return this.query.trim() === '' || title.toLowerCase().includes(this.query.trim().toLowerCase());
            },
        }"
    >
        <header class="relative isolate overflow-hidden rounded-[2rem] bg-brand-gradient px-6 py-7 text-white shadow-xl shadow-slate-900/10 sm:px-8 sm:py-9">
            <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/75">Perfil Dev</p>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Documentação do sistema</h1>
                    <p class="mt-2 text-sm leading-6 text-white/80 sm:text-base">Conteúdo de referência para a equipe técnica. Para o passo a passo de uso, consulte o guia para usuários.</p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs font-medium text-white/90">
                    <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1.5">{{ count($tableOfContents) }} seções</span>
                    <a href="{{ route('help.index') }}" class="rounded-full border border-white/20 bg-white/20 px-3 py-1.5 font-semibold hover:bg-white/30">Guia para usuários →</a>
                    <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1.5">Atualizada em {{ $updatedAt->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        </header>

        <div class="doc-layout">
            <aside class="doc-aside">
                <nav aria-label="Índice da documentação" class="doc-toc">
                    <label class="sr-only" for="doc-search">Filtrar seções</label>
                    <input
                        id="doc-search"
                        type="search"
                        x-model="query"
                        placeholder="Filtrar seções…"
                        class="doc-search"
                    >
                    <ul class="doc-toc-list">
                        @foreach ($tableOfContents as $item)
                            <li x-show="matches(@js($item['title']))">
                                <a
                                    href="#{{ $item['id'] }}"
                                    class="doc-toc-link doc-toc-level-{{ $item['level'] }}"
                                    :class="{ 'is-active': active === '{{ $item['id'] }}' }"
                                >
                                    {{ $item['title'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </aside>

            <article
                x-ref="article"
                class="doc-content"
            >
                {!! $content !!}
            </article>
        </div>
    </div>

    <style>
        html { scroll-behavior: smooth; }
        .doc-layout { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; align-items: start; }
        @media (min-width: 1024px) { .doc-layout { grid-template-columns: 18rem minmax(0, 1fr); } .doc-aside { position: sticky; top: 1.5rem; } }
        .doc-toc { padding: 1rem; border: 1px solid rgb(226 232 240); border-radius: 1.5rem; background: #fff; box-shadow: 0 1px 2px rgb(15 23 42 / 0.05); }
        .doc-search { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid rgb(226 232 240); border-radius: 0.75rem; background: rgb(248 250 252); font-size: 0.875rem; color: rgb(51 65 85); }
        .doc-search:focus { outline: 2px solid var(--company-primary, #4f46e5); outline-offset: 0; border-color: transparent; }
        .doc-toc-list { margin: 0.75rem 0 0; padding: 0 0.25rem 0 0; list-style: none; max-height: calc(100vh - 14rem); overflow-y: auto; font-size: 0.875rem; }
        .doc-toc-link { display: block; margin: 1px 0; padding: 0.375rem 0.5rem; border-radius: 0.5rem; color: rgb(71 85 105); text-decoration: none; transition: background-color 0.15s, color 0.15s; }
        .doc-toc-link:hover { background: rgb(248 250 252); color: rgb(15 23 42); }
        .doc-toc-level-2 { padding-left: 0.75rem; font-weight: 600; }
        .doc-toc-level-3 { padding-left: 1.75rem; font-size: 0.8125rem; }
        .doc-toc-link.is-active { background: color-mix(in srgb, var(--company-primary, #4f46e5) 10%, white); color: var(--company-primary, #4f46e5); }
        .doc-content { min-width: 0; padding: 1.5rem; border: 1px solid rgb(226 232 240); border-radius: 1.5rem; background: #fff; box-shadow: 0 1px 2px rgb(15 23 42 / 0.05); }
        @media (min-width: 640px) { .doc-content { padding: 2.5rem; } }
        .dark .doc-toc, .dark .doc-content { border-color: rgb(51 65 85); background: rgb(30 41 59); }
        .dark .doc-search { border-color: rgb(51 65 85); background: rgb(15 23 42); color: rgb(226 232 240); }
        .dark .doc-toc-link { color: rgb(148 163 184); }
        .dark .doc-toc-link:hover { background: rgb(51 65 85 / 0.5); color: rgb(241 245 249); }
        .dark .doc-toc-link.is-active { background: color-mix(in srgb, var(--company-primary, #4f46e5) 18%, #0f172a); color: color-mix(in srgb, var(--company-primary, #4f46e5) 42%, white); }
        .doc-content { font-size: 0.9375rem; line-height: 1.75; color: rgb(51 65 85); }
        .doc-content > :first-child { margin-top: 0; }
        .doc-content h1 { margin: 0 0 1rem; font-size: 1.875rem; line-height: 1.2; font-weight: 700; letter-spacing: -0.02em; color: rgb(15 23 42); }
        .doc-content h2 { scroll-margin-top: 1.5rem; margin: 3rem 0 1rem; padding-top: 1.5rem; border-top: 1px solid rgb(226 232 240); font-size: 1.375rem; line-height: 1.3; font-weight: 650; letter-spacing: -0.01em; color: rgb(15 23 42); }
        .doc-content h3 { scroll-margin-top: 1.5rem; margin: 2rem 0 0.5rem; font-size: 1.0625rem; font-weight: 650; color: rgb(15 23 42); }
        .doc-content h4 { margin: 1.5rem 0 0.5rem; font-size: 0.9375rem; font-weight: 650; color: rgb(30 41 59); }
        .doc-content p, .doc-content ul, .doc-content ol { margin: 0.875rem 0; }
        .doc-content ul { list-style: disc; padding-left: 1.5rem; }
        .doc-content ol { list-style: decimal; padding-left: 1.5rem; }
        .doc-content li { margin: 0.25rem 0; }
        .doc-content li::marker { color: rgb(148 163 184); }
        .doc-content a { color: var(--company-primary, #4f46e5); text-decoration: underline; text-underline-offset: 3px; }
        .doc-content strong { color: rgb(15 23 42); font-weight: 650; }
        .doc-content hr { margin: 2.5rem 0; border: 0; border-top: 1px solid rgb(226 232 240); }
        .doc-content blockquote { margin: 1.25rem 0; padding: 0.75rem 1rem; border-left: 4px solid var(--company-primary, #4f46e5); border-radius: 0 0.75rem 0.75rem 0; background: rgb(248 250 252); color: rgb(71 85 105); }
        .doc-content blockquote > :first-child { margin-top: 0; }
        .doc-content blockquote > :last-child { margin-bottom: 0; }
        .doc-content code { padding: 0.125rem 0.375rem; border-radius: 0.375rem; background: rgb(241 245 249); color: rgb(15 23 42); font-size: 0.8125em; }
        .doc-content pre { margin: 1.25rem 0; padding: 1rem 1.25rem; overflow-x: auto; border-radius: 1rem; background: rgb(15 23 42); color: rgb(226 232 240); font-size: 0.8125rem; line-height: 1.65; }
        .doc-content pre code { padding: 0; background: transparent; color: inherit; font-size: inherit; }
        .doc-table { margin: 1.25rem 0; overflow-x: auto; border: 1px solid rgb(226 232 240); border-radius: 1rem; }
        .doc-content table { width: 100%; border-collapse: collapse; font-size: 0.875rem; text-align: left; }
        .doc-content thead th { background: rgb(248 250 252); font-weight: 650; color: rgb(15 23 42); white-space: nowrap; }
        .doc-content th, .doc-content td { padding: 0.625rem 0.875rem; vertical-align: top; border-bottom: 1px solid rgb(241 245 249); }
        .doc-content tbody tr:last-child td { border-bottom: 0; }
        .doc-content tbody tr:hover { background: rgb(248 250 252); }
        .doc-content img { max-width: 100%; border-radius: 0.75rem; }
        .dark .doc-content { color: rgb(203 213 225); }
        .dark .doc-content h1, .dark .doc-content h2, .dark .doc-content h3, .dark .doc-content strong, .dark .doc-content thead th { color: rgb(241 245 249); }
        .dark .doc-content h4 { color: rgb(226 232 240); }
        .dark .doc-content h2, .dark .doc-content hr, .dark .doc-table { border-color: rgb(51 65 85); }
        .dark .doc-content a { color: color-mix(in srgb, var(--company-primary, #4f46e5) 42%, white); }
        .dark .doc-content blockquote { background: rgb(30 41 59); color: rgb(203 213 225); }
        .dark .doc-content code { background: rgb(30 41 59); color: rgb(226 232 240); }
        .dark .doc-content pre { background: rgb(2 6 23); }
        .dark .doc-content pre code { background: transparent; }
        .dark .doc-content thead th { background: rgb(30 41 59); }
        .dark .doc-content th, .dark .doc-content td { border-bottom-color: rgb(51 65 85); }
        .dark .doc-content tbody tr:hover { background: rgb(30 41 59); }
    </style>
</x-app-layout>

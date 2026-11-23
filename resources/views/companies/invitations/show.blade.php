<x-app-layout>
    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        <section class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white text-center shadow-xl shadow-slate-900/5">
            <div class="relative isolate overflow-hidden bg-brand-gradient px-6 py-8 text-white sm:px-10 sm:py-10">
                <div class="pointer-events-none absolute -right-10 -top-20 h-56 w-56 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
                <div class="relative">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-white/15 bg-white/10">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m16 0v-2a4 4 0 0 0-3-3.87M14 3.13a4 4 0 0 1 0 7.75M14 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <p class="mt-5 text-xs font-semibold uppercase tracking-[0.2em] text-white/75">Convite de equipe</p>
                    <h1 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">Acesse {{ $invitation->company->name }}</h1>
                    <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-white/80">
                        {{ $invitation->invitedBy?->name ?? 'Um administrador' }} convidou você para acompanhar e gerenciar as contas a pagar desta empresa.
                    </p>
                </div>
            </div>

            <div class="p-6 sm:p-9">
                <p class="text-sm text-slate-500">O convite foi enviado para</p>
                <p class="mt-1 break-all font-semibold text-slate-800">{{ $invitation->email }}</p>
                <form method="POST" action="{{ route('company-invitations.accept', ['token' => $token]) }}" class="mt-6">
                    @csrf
                    <x-primary-button class="w-full justify-center sm:w-auto">Aceitar convite</x-primary-button>
                </form>
            </div>
        </section>
    </div>
</x-app-layout>

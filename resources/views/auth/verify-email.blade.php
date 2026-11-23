<x-guest-layout>
    <x-slot name="heading">Verifique seu e-mail</x-slot>
    <x-slot name="description">Acesse o link enviado para seu e-mail para concluir a configuração da conta.</x-slot>

    <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button class="w-full justify-center sm:w-auto">
                    Reenviar e-mail de verificação
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="rounded-md text-sm font-medium text-slate-500 underline decoration-slate-300 underline-offset-4 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2">
                Sair
            </button>
        </form>
    </div>
</x-guest-layout>

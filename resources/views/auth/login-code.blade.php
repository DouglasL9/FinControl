<x-guest-layout>
    <x-slot name="heading">Confirme seu acesso</x-slot>
    <x-slot name="description">Enviamos um código de verificação de seis dígitos para seu e-mail.</x-slot>

    <div class="mb-4 text-sm leading-6 text-slate-600">
        Digite o código abaixo para concluir o acesso. Ele expira em 10 minutos.
    </div>

    <form method="POST" action="{{ route('login.verify') }}">
        @csrf

        <div>
            <x-input-label for="code" value="Código de verificação" />
            <x-text-input
                id="code"
                class="block mt-1 w-full"
                type="text"
                name="code"
                inputmode="numeric"
                autocomplete="one-time-code"
                pattern="[0-9]{6}"
                maxlength="6"
                required
                autofocus
            />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="mt-5 flex flex-col-reverse items-start justify-between gap-4 sm:flex-row sm:items-center">
            <a class="rounded-md text-sm font-medium text-brand-primary underline decoration-brand-primary underline-offset-4 hover:brightness-90 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2" href="{{ route('login') }}">
                Voltar para o login
            </a>

            <x-primary-button class="w-full sm:w-auto">
                Verificar e entrar
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

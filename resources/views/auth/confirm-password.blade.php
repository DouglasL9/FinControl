<x-guest-layout>
    <x-slot name="heading">Confirme sua senha</x-slot>
    <x-slot name="description">Por segurança, confirme sua senha antes de continuar.</x-slot>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Senha" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-5 flex justify-end">
            <x-primary-button class="w-full sm:w-auto">
                Confirmar
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

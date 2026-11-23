<x-guest-layout>
    <x-slot name="heading">Crie sua conta</x-slot>
    <x-slot name="description">Comece a ter mais clareza sobre as finanças da sua empresa.</x-slot>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" value="Nome" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" value="Senha" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Confirme a senha" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-5 flex flex-col-reverse items-start justify-between gap-4 sm:flex-row sm:items-center">
            <a class="rounded-md text-sm font-medium text-brand-primary underline decoration-brand-primary underline-offset-4 hover:brightness-90 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2" href="{{ route('login') }}">
                Já tem uma conta? Entrar
            </a>

            <x-primary-button class="w-full sm:w-auto">
                Cadastrar
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

<x-guest-layout>
    <x-slot name="heading">Bem-vindo de volta</x-slot>
    <x-slot name="description">Entre para acompanhar as finanças da sua empresa.</x-slot>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" value="Senha" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-brand-primary shadow-sm focus:ring-brand-primary" name="remember">
                <span class="ms-2 text-sm text-slate-600">Lembrar de mim</span>
            </label>
        </div>

        <div class="mt-5 flex flex-col-reverse items-start justify-between gap-4 sm:flex-row sm:items-center">
            @if (Route::has('password.request'))
                <a class="rounded-md text-sm font-medium text-brand-primary underline decoration-brand-primary underline-offset-4 hover:brightness-90 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2" href="{{ route('password.request') }}">
                    Esqueceu sua senha?
                </a>
            @endif

            <x-primary-button class="w-full sm:w-auto">
                Entrar
            </x-primary-button>
        </div>
    </form>

    @if (Route::has('register'))
        <p class="mt-6 border-t border-slate-100 pt-5 text-center text-sm text-slate-600">
            Ainda não tem conta?
            <a class="rounded-md font-semibold text-brand-primary underline decoration-brand-primary underline-offset-4 hover:brightness-90 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2" href="{{ route('register') }}">
                Registre-se
            </a>
        </p>
    @endif
</x-guest-layout>

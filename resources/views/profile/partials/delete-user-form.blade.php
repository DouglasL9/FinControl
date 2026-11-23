<section class="space-y-6 sm:flex sm:items-center sm:justify-between sm:gap-6 sm:space-y-0">
    <header class="max-w-2xl">
        <h2 class="text-lg font-semibold text-rose-800">
            Excluir conta
        </h2>

        <p class="mt-1 text-sm leading-6 text-slate-600">
            Esta ação é permanente. Ao excluir sua conta, seus dados serão removidos e não poderão ser recuperados.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Excluir minha conta</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-semibold text-slate-900">
                Tem certeza de que deseja excluir sua conta?
            </h2>

            <p class="mt-1 text-sm leading-6 text-slate-600">
                Para confirmar a exclusão permanente, informe sua senha.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Senha" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Senha"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Excluir conta
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>

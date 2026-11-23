

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.store('theme', {
    dark: document.documentElement.classList.contains('dark'),

    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        localStorage.setItem('fincontrol-theme', this.dark ? 'dark' : 'light');
    },
});

Alpine.data('unitAddressLookup', () => ({
    lookupLoading: false,
    lookupMessage: '',
    lookupState: '',
    requestId: 0,

    async lookupCep() {
        const requestId = ++this.requestId;
        const cep = this.$refs.postalCode.value.replace(/\D/g, '');

        this.lookupMessage = '';
        this.lookupState = '';

        if (cep.length !== 8) {
            this.lookupLoading = false;

            return;
        }

        this.lookupLoading = true;
        this.lookupMessage = 'Buscando endereço...';
        this.lookupState = 'loading';

        let response;

        try {
            response = await fetch(`https://viacep.com.br/ws/${cep}/json/`, {
                headers: { Accept: 'application/json' },
            });
        } catch {
            this.reportLookupError(requestId, cep);

            return;
        }

        if (!response.ok) {
            this.reportLookupError(requestId, cep);

            return;
        }

        let address;

        try {
            address = await response.json();
        } catch {
            this.reportLookupError(requestId, cep);

            return;
        }

        if (!this.isCurrentLookup(requestId, cep)) {
            return;
        }

        this.lookupLoading = false;

        if (address.erro) {
            this.lookupMessage = 'CEP não encontrado. Confira os números.';
            this.lookupState = 'error';

            return;
        }

        if (address.logradouro) {
            this.$refs.street.value = address.logradouro;
        }

        if (address.bairro) {
            this.$refs.neighborhood.value = address.bairro;
        }

        if (address.localidade) {
            this.$refs.city.value = address.localidade;
        }

        if (address.uf) {
            this.$refs.state.value = address.uf;
        }

        this.lookupMessage = 'Endereço localizado. Confira os dados antes de salvar.';
        this.lookupState = 'success';
    },

    reportLookupError(requestId, cep) {
        if (!this.isCurrentLookup(requestId, cep)) {
            return;
        }

        this.lookupLoading = false;
        this.lookupMessage = 'Não foi possível consultar o CEP agora. Preencha o endereço manualmente.';
        this.lookupState = 'error';
    },

    isCurrentLookup(requestId, cep) {
        return requestId === this.requestId
            && this.$refs.postalCode.value.replace(/\D/g, '') === cep;
    },
}));

Alpine.data('companyUserPicker', (searchUrl) => ({
    query: '',
    email: '',
    selectedNotice: '',
    results: [],
    loading: false,
    error: '',
    requestId: 0,

    async searchUsers() {
        const search = this.query.trim();
        const requestId = ++this.requestId;

        this.email = '';
        this.selectedNotice = '';
        this.error = '';
        this.results = [];

        if (search.length < 2) {
            this.loading = false;

            return;
        }

        this.loading = true;

        try {
            const response = await fetch(`${searchUrl}?search=${encodeURIComponent(search)}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                if (requestId === this.requestId) {
                    this.error = 'Não foi possível buscar usuários agora.';
                }

                return;
            }

            const results = await response.json();

            if (requestId === this.requestId) {
                this.results = results;
            }
        } catch {
            if (requestId === this.requestId) {
                this.error = 'Não foi possível buscar usuários agora.';
            }
        } finally {
            if (requestId === this.requestId) {
                this.loading = false;
            }
        }
    },

    selectUser(user) {
        this.requestId++;
        this.email = user.email;
        this.selectedNotice = `${user.name} selecionado.`;
        this.query = '';
        this.results = [];
        this.loading = false;
    },
}));

Alpine.start();

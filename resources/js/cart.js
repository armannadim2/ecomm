/*
 * Header mini cart and "add to cart" without leaving the page.
 * The header button opens a short summary (fetched from /cesta/resumen, so cached pages work too);
 * add-to-cart forms post in the background, update the header and open the summary.
 * Without JavaScript both still work as plain links/forms.
 */
const json = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };

export function cartStore(Alpine) {
    Alpine.store('cart', {
        open: false,
        loading: false,
        loaded: false,
        count: 0,
        subtotal: '',
        label: '',
        lines: [],
        message: '',
        error: '',
        summaryUrl: '',

        set(data) {
            this.count = data.count;
            this.subtotal = data.subtotal;
            this.label = data.label;
            this.lines = data.lines ?? [];
            this.loaded = true;
        },

        async load() {
            this.loading = true;
            try {
                const res = await fetch(this.summaryUrl, { headers: json, credentials: 'same-origin' });
                if (res.ok) this.set(await res.json());
            } finally {
                this.loading = false;
            }
        },

        show(message = '', error = '') {
            this.message = message;
            this.error = error;
            this.open = true;
            if (!this.loaded && !error) this.load();
        },

        toggle() {
            if (this.open) {
                this.open = false;
                return;
            }
            this.message = '';
            this.error = '';
            this.open = true;
            this.load();
        },
    });
}

/** Header button: seeds the store with the server-rendered count, opens the popup. */
export function miniCart(initial) {
    return {
        init() {
            const cart = this.$store.cart;
            cart.summaryUrl = initial.summaryUrl;
            cart.count = initial.count;
            cart.subtotal = initial.subtotal;
            cart.label = initial.label;
        },
    };
}

/** Add-to-cart form: posts in the background; falls back to a normal submit if that fails. */
export function addToCart() {
    return {
        busy: false,
        added: false,

        async submit() {
            const form = this.$el.closest('form') ?? this.$el;
            if (this.busy) return;
            this.busy = true;

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: json,
                    credentials: 'same-origin',
                });

                if (res.status === 422) {
                    const data = await res.json();
                    this.$store.cart.show('', data.message ?? '');
                    return;
                }
                if (!res.ok) throw new Error(String(res.status));

                const data = await res.json();
                this.$store.cart.set(data);
                this.$store.cart.show(data.message);
                this.added = true;
                setTimeout(() => (this.added = false), 2000);
            } catch {
                // Expired session (419), rate limit, network…: let the server handle it the classic way.
                form.submit();
            } finally {
                this.busy = false;
            }
        },
    };
}

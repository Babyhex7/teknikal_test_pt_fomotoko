<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Online Store</title>
    <style>
        :root {
            color-scheme: light dark;
            --accent: #4f46e5;
            --accent-hover: #4338ca;
            --danger: #dc2626;
            --success: #16a34a;
            --flash: #f43f5e;
            --border: color-mix(in srgb, currentColor 12%, transparent);
            --surface: color-mix(in srgb, currentColor 4%, transparent);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, Roboto, sans-serif;
            max-width: 1080px;
            margin: 0 auto;
            padding: 0 1.5rem 4rem;
            line-height: 1.5;
        }
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 2rem 0 1.5rem;
            flex-wrap: wrap;
        }
        header h1 {
            margin: 0;
            font-size: 1.6rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        header p {
            margin: .2rem 0 0;
            color: color-mix(in srgb, currentColor 55%, transparent);
            font-size: .9rem;
        }
        .pill {
            font-size: .75rem;
            font-weight: 600;
            padding: .3rem .7rem;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--surface);
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }
        .pill .dot {
            width: .45rem;
            height: .45rem;
            border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--success) 25%, transparent);
        }
        .layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 2rem;
            align-items: start;
        }
        @media (max-width: 800px) {
            .layout { grid-template-columns: 1fr; }
        }
        .products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 1rem;
        }
        .card {
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.1rem;
            background: var(--surface);
            transition: transform .15s ease, box-shadow .15s ease;
            display: flex;
            flex-direction: column;
            gap: .5rem;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px -12px rgba(0,0,0,.25);
        }
        .badge-row { display: flex; gap: .4rem; min-height: 1.4rem; }
        .badge {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .03em;
            padding: .15rem .55rem;
            border-radius: 999px;
            background: color-mix(in srgb, var(--flash) 15%, transparent);
            color: var(--flash);
        }
        .badge.low {
            background: color-mix(in srgb, orange 18%, transparent);
            color: #b45309;
        }
        .card h3 { margin: 0; font-size: 1.02rem; }
        .sku { font-size: .72rem; color: color-mix(in srgb, currentColor 45%, transparent); }
        .price-row { display: flex; align-items: baseline; gap: .5rem; margin-top: .1rem; }
        .price-old { text-decoration: line-through; color: color-mix(in srgb, currentColor 40%, transparent); font-size: .85rem; }
        .price-new { font-weight: 700; font-size: 1.15rem; }
        .stock { font-size: .8rem; color: color-mix(in srgb, currentColor 55%, transparent); }
        .buy-btn {
            margin-top: .4rem;
            padding: .5rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--accent);
            font-weight: 600;
            font-size: .85rem;
            cursor: pointer;
        }
        .buy-btn:hover { background: color-mix(in srgb, var(--accent) 10%, transparent); }
        .buy-btn:disabled { opacity: .4; cursor: not-allowed; }

        .panel {
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.25rem;
            background: var(--surface);
            position: sticky;
            top: 1.5rem;
        }
        .panel h2 { margin: 0 0 1rem; font-size: 1.05rem; }
        .field { margin-bottom: .9rem; }
        label { font-size: .78rem; font-weight: 600; display: block; margin-bottom: .3rem; color: color-mix(in srgb, currentColor 65%, transparent); }
        input, select {
            width: 100%;
            font: inherit;
            padding: .55rem .65rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: color-mix(in srgb, currentColor 2%, transparent);
            color: inherit;
        }
        input:focus, select:focus { outline: 2px solid var(--accent); outline-offset: 1px; }
        button[type="submit"] {
            width: 100%;
            cursor: pointer;
            background: var(--accent);
            color: white;
            border: none;
            font-weight: 600;
            font-size: .95rem;
            padding: .65rem;
            border-radius: 8px;
            transition: background .15s ease;
        }
        button[type="submit"]:hover { background: var(--accent-hover); }
        button[type="submit"]:disabled { opacity: .6; cursor: wait; }

        #toast {
            margin-top: 1rem;
            padding: .7rem .85rem;
            border-radius: 10px;
            font-size: .85rem;
            display: none;
        }
        #toast.ok { display: block; background: color-mix(in srgb, var(--success) 15%, transparent); border: 1px solid color-mix(in srgb, var(--success) 40%, transparent); }
        #toast.err { display: block; background: color-mix(in srgb, var(--danger) 15%, transparent); border: 1px solid color-mix(in srgb, var(--danger) 40%, transparent); }

        .empty { color: color-mix(in srgb, currentColor 45%, transparent); padding: 2rem 0; text-align: center; grid-column: 1 / -1; }
        footer { margin-top: 3rem; font-size: .78rem; color: color-mix(in srgb, currentColor 40%, transparent); }
        footer code { background: var(--surface); padding: .1rem .35rem; border-radius: 4px; }
    </style>
</head>
<body>
    <header>
        <div>
            <h1>🛒 Online Store</h1>
            <p>Live demo UI over the <code>/api/*</code> endpoints.</p>
        </div>
        <span class="pill"><span class="dot"></span> API connected</span>
    </header>

    <div class="layout">
        <div id="products" class="products">
            <p class="empty">Loading products…</p>
        </div>

        <div class="panel">
            <h2>Place an order</h2>
            <form id="orderForm">
                <div class="field">
                    <label for="email">Customer email</label>
                    <input type="email" id="email" required value="demo@example.com">
                </div>
                <div class="field">
                    <label for="product">Product</label>
                    <select id="product" required></select>
                </div>
                <div class="field">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" min="1" value="1" required>
                </div>
                <button type="submit" id="submitBtn">Place order</button>
            </form>
            <div id="toast"></div>
        </div>
    </div>

    <footer>
        Endpoints: <code>GET /api/products</code>, <code>POST /api/orders</code>,
        <code>GET /api/flash-sales</code>. This page is a thin demo shell — the
        API works the same with curl or Postman.
    </footer>

    <script>
        const productsEl = document.getElementById('products');
        const productSelect = document.getElementById('product');
        const toast = document.getElementById('toast');

        function money(n) {
            return '$' + Number(n).toFixed(2);
        }

        async function loadProducts() {
            const res = await fetch('/api/products');
            const { data } = await res.json();

            if (data.length === 0) {
                productsEl.innerHTML = '<p class="empty">No products yet.</p>';
                productSelect.innerHTML = '';
                return;
            }

            productsEl.innerHTML = data.map(p => {
                const outOfStock = p.in_stock_quantity <= 0;
                const lowStock = !outOfStock && p.in_stock_quantity <= 5;

                return `
                    <div class="card">
                        <div class="badge-row">
                            ${p.flash_sale ? '<span class="badge">⚡ FLASH SALE</span>' : ''}
                            ${lowStock ? '<span class="badge low">LOW STOCK</span>' : ''}
                        </div>
                        <h3>${p.name}</h3>
                        <div class="sku">SKU ${p.sku}</div>
                        <div class="price-row">
                            ${p.flash_sale ? `<span class="price-old">${money(p.price)}</span>` : ''}
                            <span class="price-new">${money(p.current_price)}</span>
                        </div>
                        <div class="stock">${outOfStock ? 'Out of stock' : `${p.in_stock_quantity} in stock`}</div>
                        <button class="buy-btn" data-id="${p.id}" ${outOfStock ? 'disabled' : ''}>
                            ${outOfStock ? 'Unavailable' : 'Select for order →'}
                        </button>
                    </div>
                `;
            }).join('');

            productSelect.innerHTML = data.map(p =>
                `<option value="${p.id}" ${p.in_stock_quantity <= 0 ? 'disabled' : ''}>${p.name} — ${money(p.current_price)} (${p.in_stock_quantity} left)</option>`
            ).join('');

            productsEl.querySelectorAll('.buy-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    productSelect.value = btn.dataset.id;
                    productSelect.closest('form').scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            });
        }

        document.getElementById('orderForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            toast.className = '';

            try {
                const res = await fetch('/api/orders', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        customer_email: document.getElementById('email').value,
                        items: [{
                            product_id: Number(productSelect.value),
                            quantity: Number(document.getElementById('quantity').value),
                        }],
                    }),
                });
                const body = await res.json();

                if (res.ok) {
                    toast.className = 'ok';
                    toast.textContent = `✅ Order #${body.data.id} placed — total ${money(body.data.total)}`;
                    await loadProducts();
                } else {
                    toast.className = 'err';
                    toast.textContent = '❌ ' + (body.message || 'Something went wrong.');
                }
            } catch {
                toast.className = 'err';
                toast.textContent = '❌ Network error — is the API reachable?';
            } finally {
                submitBtn.disabled = false;
            }
        });

        loadProducts();
    </script>
</body>
</html>

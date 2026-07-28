<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Online Store — Demo UI</title>
    <style>
        :root { color-scheme: light dark; }
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1.25rem;
            line-height: 1.5;
        }
        h1 { margin-bottom: .25rem; }
        p.sub { color: #888; margin-top: 0; }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }
        .card {
            border: 1px solid #8883;
            border-radius: 10px;
            padding: 1rem;
        }
        .card h3 { margin: 0 0 .25rem; }
        .badge {
            display: inline-block;
            font-size: .75rem;
            font-weight: 600;
            padding: .15rem .5rem;
            border-radius: 999px;
            background: #ff5b5b22;
            color: #ff5b5b;
            margin-bottom: .5rem;
        }
        .price-old { text-decoration: line-through; color: #888; margin-right: .4rem; }
        .price-new { font-weight: 700; }
        .stock { color: #888; font-size: .85rem; }
        form.order {
            border: 1px solid #8883;
            border-radius: 10px;
            padding: 1.25rem;
            display: grid;
            gap: .75rem;
            max-width: 420px;
        }
        label { font-size: .85rem; font-weight: 600; }
        input, select, button {
            font: inherit;
            padding: .5rem;
            border-radius: 6px;
            border: 1px solid #8886;
        }
        button {
            cursor: pointer;
            background: #2563eb;
            color: white;
            border: none;
            font-weight: 600;
        }
        button:disabled { opacity: .6; cursor: wait; }
        #result { margin-top: 1rem; padding: .75rem; border-radius: 8px; display: none; white-space: pre-wrap; font-size: .9rem; }
        #result.ok { display: block; background: #16a34a22; border: 1px solid #16a34a55; }
        #result.err { display: block; background: #dc262622; border: 1px solid #dc262655; }
    </style>
</head>
<body>
    <h1>Online Store</h1>
    <p class="sub">Minimal demo UI on top of the <code>/api/*</code> endpoints. Refresh to see live stock/flash-sale state.</p>

    <div id="products" class="grid">Loading products…</div>

    <h2>Place an order</h2>
    <form class="order" id="orderForm">
        <div>
            <label for="email">Customer email</label>
            <input type="email" id="email" required value="demo@example.com">
        </div>
        <div>
            <label for="product">Product</label>
            <select id="product" required></select>
        </div>
        <div>
            <label for="quantity">Quantity</label>
            <input type="number" id="quantity" min="1" value="1" required>
        </div>
        <button type="submit" id="submitBtn">Place order</button>
    </form>
    <div id="result"></div>

    <script>
        const productsEl = document.getElementById('products');
        const productSelect = document.getElementById('product');
        const resultEl = document.getElementById('result');

        async function loadProducts() {
            const res = await fetch('/api/products');
            const { data } = await res.json();

            productsEl.innerHTML = data.map(p => `
                <div class="card">
                    ${p.flash_sale ? '<div class="badge">FLASH SALE</div>' : ''}
                    <h3>${p.name}</h3>
                    <div>
                        ${p.flash_sale ? `<span class="price-old">$${p.price.toFixed(2)}</span>` : ''}
                        <span class="price-new">$${p.current_price.toFixed(2)}</span>
                    </div>
                    <div class="stock">${p.in_stock_quantity} in stock &middot; SKU ${p.sku}</div>
                </div>
            `).join('');

            productSelect.innerHTML = data.map(p =>
                `<option value="${p.id}">${p.name} — $${p.current_price.toFixed(2)} (${p.in_stock_quantity} left)</option>`
            ).join('');
        }

        document.getElementById('orderForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            resultEl.className = '';

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
                    resultEl.className = 'ok';
                    resultEl.textContent = `Order #${body.data.id} placed! Total: $${body.data.total.toFixed(2)}`;
                    await loadProducts();
                } else {
                    resultEl.className = 'err';
                    resultEl.textContent = body.message || 'Something went wrong.';
                }
            } finally {
                submitBtn.disabled = false;
            }
        });

        loadProducts();
    </script>
</body>
</html>

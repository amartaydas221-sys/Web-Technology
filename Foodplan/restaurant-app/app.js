'use strict';

const api = async (action, data = {}, method = 'GET', params = {}) => {
  const query = new URLSearchParams({ action, ...params });
  const response = await fetch(`api.php?${query}`, {
    method,
    credentials: 'same-origin',
    headers: method === 'GET' ? {} : { 'Content-Type': 'application/json' },
    body: method === 'GET' ? undefined : JSON.stringify(data),
  });
  const contentType = response.headers.get('content-type') || '';
  if (!contentType.includes('application/json')) {
    const body = (await response.text()).trim();
    throw new Error(body || `The Foodplan server returned HTTP ${response.status}. Check PHP and MySQL.`);
  }
  let result;
  try {
    result = await response.json();
  } catch {
    throw new Error(`Foodplan returned invalid JSON (HTTP ${response.status}). Check the PHP error log.`);
  }
  if (!response.ok) throw new Error(result.error || `Request failed (HTTP ${response.status}).`);
  return result;
};

const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
  '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
}[char]));
const money = (value) => `৳${Number(value || 0).toLocaleString('en-BD', { maximumFractionDigits: 2 })}`;
const imageFallback = 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=85';
const cartKey = 'foodplan-php-cart';
const deliveryAreaKey = 'foodplan-delivery-area';
const getDeliveryArea = () => localStorage.getItem(deliveryAreaKey)?.trim() || '';
const getCart = () => {
  try {
    const value = JSON.parse(localStorage.getItem(cartKey) || '[]');
    return Array.isArray(value) ? value : [];
  } catch {
    return [];
  }
};
const setCart = (items) => {
  localStorage.setItem(cartKey, JSON.stringify(items));
  updateCartCount();
};
const toast = (message) => {
  const node = document.querySelector('.toast');
  if (!node) return;
  node.textContent = message;
  node.hidden = false;
  clearTimeout(toast.timer);
  toast.timer = setTimeout(() => { node.hidden = true; }, 3500);
};
const showMessage = (form, message, error = false) => {
  const node = form.querySelector('[data-message]');
  if (node) {
    node.textContent = message;
    node.classList.toggle('is-error', error);
    node.classList.toggle('is-success', !error && Boolean(message));
  } else if (message) {
    toast(message);
  }
};
const updateCartCount = () => {
  const count = getCart().reduce((total, item) => total + Number(item.quantity || 0), 0);
  const link = document.querySelector('.header-actions .button');
  if (link && document.body.dataset.page === 'cart') link.textContent = `Cart (${count})`;
};
const productCard = (product) => {
  const price = Number(product.price) * (1 - Number(product.discount_percent || 0) / 100);
  return `<article class="product-card">
    <a class="product-image" href="?page=menu&product=${encodeURIComponent(product.id)}"><img src="${esc(product.image_url || imageFallback)}" alt="${esc(product.name)}" loading="lazy" onerror="this.src='${imageFallback}'">${Number(product.discount_percent) > 0 ? `<span class="discount-pill">${esc(product.discount_percent)}% off</span>` : ''}<span class="rating-pill">★ ${Number(product.rating || 0).toFixed(1)}</span></a>
    <div class="product-card-body"><span class="eyebrow">${esc(product.category_name || product.category_slug || 'Fresh favorite')}</span><h3><a href="?page=menu&product=${encodeURIComponent(product.id)}">${esc(product.name)}</a></h3><p>${esc(product.description)}</p><div class="product-card-bottom"><strong>${money(price)}</strong>${Number(product.discount_percent) > 0 ? `<del>${money(product.price)}</del>` : ''}<button class="button button-small" type="button" data-add="${esc(product.id)}">+ Add</button></div></div>
  </article>`;
};
const loadProducts = async (container, options = {}) => {
  container.innerHTML = '<div class="loading">Loading fresh dishes…</div>';
  try {
    const products = await api('products', {}, 'GET', options);
    const limit = Number(container.dataset.limit || 0);
    const visible = limit ? products.slice(0, limit) : products;
    container.innerHTML = visible.length ? visible.map(productCard).join('') : '<div class="empty-state">No dishes match these filters.</div>';
  } catch (error) {
    container.innerHTML = `<div class="error-banner">${esc(error.message)}</div>`;
  }
};
const loadCategories = async () => {
  try {
    const categories = await api('categories');
    const tiles = document.querySelector('[data-categories]');
    if (tiles) tiles.innerHTML = categories.map((item, index) => `<a class="category-tile" href="?page=menu&category=${encodeURIComponent(item.slug)}"><span>${['🍛', '🍔', '🍕', '🍗', '🍰', '🥤', '🥘', '🍟'][index % 8]}</span>${esc(item.name)}<b>↗</b></a>`).join('');
    const select = document.querySelector('#menu-filters [name="category"]');
    if (select) {
      const chosen = new URLSearchParams(location.search).get('category') || 'all';
      select.innerHTML = '<option value="all">All dishes</option>' + categories.map((item) => `<option value="${esc(item.slug)}" ${item.slug === chosen ? 'selected' : ''}>${esc(item.name)}</option>`).join('');
    }
    const adminSelect = document.querySelector('[data-category-options]');
    if (adminSelect) {
      const form = adminSelect.closest('form');
      if (categories.length) {
        adminSelect.innerHTML = categories.map((item) => `<option value="${esc(item.id)}">${esc(item.name)}</option>`).join('');
        adminSelect.disabled = false;
        showMessage(form, '');
      } else {
        adminSelect.innerHTML = '<option value="">No categories available</option>';
        adminSelect.disabled = true;
        showMessage(form, 'No product categories are available.', true);
      }
    }
  } catch (error) {
    const tiles = document.querySelector('[data-categories]');
    if (tiles) tiles.innerHTML = `<div class="error-banner">${esc(error.message)}</div>`;
    const adminSelect = document.querySelector('[data-category-options]');
    if (adminSelect) {
      adminSelect.innerHTML = '<option value="">Categories could not be loaded</option>';
      adminSelect.disabled = true;
      showMessage(adminSelect.closest('form'), error.message, true);
    }
  }
};
const table = (headers, rows) => {
  if (!rows.length) return '<p class="empty-state">Nothing to show yet.</p>';
  return `<div class="table-wrap"><table><thead><tr>${headers.map((header) => `<th>${esc(header)}</th>`).join('')}</tr></thead><tbody>${rows.join('')}</tbody></table></div>`;
};
const statusBadge = (status) => {
  const labels = { processing: 'preparing', packaging: 'packing', out_for_delivery: 'rider assigned' };
  return `<span class="status-badge">${esc(labels[status] || String(status || '').replaceAll('_', ' '))}</span>`;
};

async function initHome() {
  const areaForm = document.querySelector('[data-delivery-area]');
  const areaInput = areaForm?.elements.namedItem('area');
  const areaStatus = document.querySelector('[data-delivery-area-status]');
  const currentArea = getDeliveryArea();
  if (areaInput) areaInput.value = currentArea;
  if (areaStatus && currentArea) areaStatus.textContent = `Saved area: ${currentArea}. Add your full street address at checkout.`;
  areaForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    const area = areaInput.value.trim();
    if (!area) return;
    localStorage.setItem(deliveryAreaKey, area);
    updateDeliveryArea();
    if (areaStatus) areaStatus.textContent = `Saved area: ${area}. Add your full street address at checkout.`;
    toast('Delivery area saved for checkout.');
  });
  const categoryTarget = document.querySelector('[data-categories]');
  if (categoryTarget) loadCategories();
  const products = document.querySelector('[data-products]');
  if (products) loadProducts(products);
}

function updateDeliveryArea() {
  const target = document.querySelector('[data-delivery-location]');
  if (target) target.textContent = getDeliveryArea() || 'Choose an area';
}

async function initMenu() {
  await loadCategories();
  const container = document.querySelector('[data-menu-results]');
  const form = document.querySelector('#menu-filters');
  const query = new URLSearchParams(location.search);
  if (form) {
    ['search', 'category', 'maxPrice', 'sort'].forEach((key) => {
      const field = form.elements.namedItem(key);
      if (field && query.has(key)) field.value = query.get(key);
    });
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      const params = new URLSearchParams(new FormData(form));
      params.set('page', 'menu');
      location.href = `?${params}`;
    });
  }
  const search = form?.elements.namedItem('search')?.value || '';
  await loadProducts(container, {
    category: form?.elements.namedItem('category')?.value || query.get('category') || 'all',
    search,
    maxPrice: form?.elements.namedItem('maxPrice')?.value || '',
    sort: form?.elements.namedItem('sort')?.value || 'popular',
  });
  const productId = query.get('product');
  if (productId) {
    try {
      const product = await api('product', {}, 'GET', { id: productId });
      container.innerHTML = `<article class="product-detail"><img src="${esc(product.image_url || imageFallback)}" alt="${esc(product.name)}"><div><span class="eyebrow">${esc(product.category_name)}</span><h2>${esc(product.name)}</h2><p>${esc(product.description)}</p><p>★ ${Number(product.rating).toFixed(1)} · ${Number(product.stock) > 0 ? `${esc(product.stock)} portions available` : 'Sold out'}</p><strong>${money(Number(product.price) * (1 - Number(product.discount_percent || 0) / 100))}</strong><p>${esc(product.ingredients || '')}</p><button class="button" data-add="${esc(product.id)}" ${!Number(product.stock) ? 'disabled' : ''}>Add to cart</button></div></article>`;
    } catch (error) {
      container.innerHTML = `<div class="error-banner">${esc(error.message)}</div>`;
    }
  }
}

async function initOffers() {
  const target = document.querySelector('[data-coupons]');
  try {
    const coupons = await api('coupons');
    target.innerHTML = coupons.length ? coupons.map((coupon) => `<article class="coupon-card"><span class="eyebrow">AVAILABLE OFFER</span><h2>${esc(coupon.code)}</h2><p>${esc(coupon.description)}</p><small>Minimum order ${money(coupon.minimum_order)}</small></article>`).join('') : '<p>No active offers right now.</p>';
  } catch (error) { target.innerHTML = `<div class="error-banner">${esc(error.message)}</div>`; }
}

async function initCart() {
  const cartBox = document.querySelector('[data-cart]');
  const summary = document.querySelector('[data-cart-summary]');
  const render = () => {
    const items = getCart();
    const subtotal = items.reduce((sum, item) => sum + Number(item.price) * Number(item.quantity), 0);
    cartBox.innerHTML = items.length ? items.map((item) => `<article class="cart-item"><img src="${esc(item.image_url || imageFallback)}" alt=""><div><strong>${esc(item.name)}</strong><small>${money(item.price)} each</small></div><div class="quantity-control"><button type="button" data-quantity="${esc(item.id)}" data-change="-1">−</button><span>${esc(item.quantity)}</span><button type="button" data-quantity="${esc(item.id)}" data-change="1">+</button></div><strong>${money(Number(item.price) * Number(item.quantity))}</strong><button class="text-button" data-remove="${esc(item.id)}">Remove</button></article>`).join('') : '<div class="empty-state"><h2>Your cart is empty</h2><a class="button" href="?page=menu">Browse the menu</a></div>';
    summary.innerHTML = `<h2>Order summary</h2><div class="summary-row"><span>Subtotal</span><strong>${money(subtotal)}</strong></div><div class="summary-row"><span>Delivery</span><strong>${money(items.length ? 60 : 0)}</strong></div><div class="summary-row"><span>Estimated tax</span><strong>${money(subtotal * .05)}</strong></div><div class="summary-row summary-total"><span>Total</span><strong>${money(items.length ? subtotal + 60 + subtotal * .05 : 0)}</strong></div>${items.length ? '<a class="button button-full" href="?page=checkout">Continue to checkout</a>' : ''}`;
  };
  document.addEventListener('click', (event) => {
    const quantity = event.target.closest('[data-quantity]');
    const remove = event.target.closest('[data-remove]');
    if (quantity || remove) {
      const id = Number((quantity || remove).dataset.quantity || (quantity || remove).dataset.remove);
      const items = getCart();
      const found = items.find((item) => Number(item.id) === id);
      if (found && quantity) found.quantity = Math.min(20, found.quantity + Number(quantity.dataset.change));
      const updated = items.filter((item) => item.quantity > 0 && (!remove || Number(item.id) !== id));
      setCart(updated);
      render();
    }
  });
  render();
}

async function initCheckout() {
  const form = document.querySelector('[data-checkout]');
  const summary = document.querySelector('[data-checkout-summary]');
  const items = getCart();
  if (!items.length) {
    summary.innerHTML = '<p>Your cart is empty.</p><a class="button" href="?page=menu">Browse menu</a>';
    return;
  }
  try {
    const user = await api('me');
    form.elements.namedItem('name').value = user.name || '';
    form.elements.namedItem('phone').value = user.phone || '';
  } catch {
    location.href = '?page=login&next=checkout';
    return;
  }
  const savedArea = getDeliveryArea();
  const addressField = form.elements.namedItem('address');
  const areaNote = document.querySelector('[data-checkout-area]');
  if (savedArea && addressField && !addressField.value.trim()) addressField.value = savedArea;
  if (areaNote) {
    areaNote.innerHTML = savedArea
      ? `Saved delivery area: <strong>${esc(savedArea)}</strong>. Please add your house or building, street, and a nearby landmark above. <a href="?page=home#delivery-area">Change area</a>`
      : 'Choose your delivery area on the <a href="?page=home#delivery-area">home page</a>, then add your full street address here.';
  }
  const renderSummary = () => {
    const subtotal = items.reduce((sum, item) => sum + Number(item.price) * item.quantity, 0);
    summary.innerHTML = `<h2>Your order</h2>${items.map((item) => `<div class="summary-row"><span>${item.quantity} × ${esc(item.name)}</span><strong>${money(item.price * item.quantity)}</strong></div>`).join('')}<div class="summary-row"><span>Delivery</span><strong>${money(60)}</strong></div><div class="summary-row"><span>Tax</span><strong>${money(subtotal * .05)}</strong></div><div class="summary-row summary-total"><span>Subtotal</span><strong>${money(subtotal)}</strong></div>`;
  };
  renderSummary();
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = form.querySelector('button[type="submit"],button:not([type])');
    button.disabled = true;
    try {
      const values = Object.fromEntries(new FormData(form));
      const result = await api('orders', { ...values, items }, 'POST');
      setCart([]);
      location.href = `?page=track&order=${encodeURIComponent(result.order_code)}`;
    } catch (error) { showMessage(form, error.message, true); }
    finally { button.disabled = false; }
  });
}

async function loadTrack(code, target, form) {
  if (!code) return;
  try {
    const order = await api('order', {}, 'GET', { code });
    const flow = ['placed', 'confirmed', 'processing', 'packaging', 'ready', 'out_for_delivery', 'delivered'];
    const labels = ['Order placed', 'Confirmed', 'Preparing', 'Packing', 'Ready for pickup', 'On the way', 'Delivered'];
    const currentStatus = ['picked_up', 'on_the_way', 'arrived'].includes(order.status) ? 'out_for_delivery' : order.status;
    const current = flow.indexOf(currentStatus);
    const hasLocation = order.rider_latitude !== null && order.rider_latitude !== undefined && order.rider_longitude !== null && order.rider_longitude !== undefined;
    const lat = Number(order.rider_latitude);
    const lon = Number(order.rider_longitude);
    const timeline = labels.map((label, index) => `<div class="timeline-step ${index <= current ? 'done' : ''} ${index === current ? 'now' : ''}"><span class="timeline-dot">${index < current ? '✓' : index + 1}</span><strong>${label}</strong></div>`).join('');
    const map = hasLocation && ['out_for_delivery', 'picked_up', 'on_the_way', 'arrived'].includes(order.status)
      ? `<iframe class="live-map-frame" title="Live rider location" loading="lazy" src="https://www.openstreetmap.org/export/embed.html?bbox=${lon - .015}%2C${lat - .01}%2C${lon + .015}%2C${lat + .01}&layer=mapnik&marker=${lat}%2C${lon}"></iframe><small>Updated ${esc(order.rider_location_updated_at || 'just now')} · refreshes every 10 seconds · <a target="_blank" rel="noreferrer" href="https://www.openstreetmap.org/?mlat=${lat}&mlon=${lon}#map=16/${lat}/${lon}">Open map ↗</a></small>`
      : '<div class="map-placeholder"><strong>Live map appears after your rider accepts the delivery and shares GPS.</strong></div>';
    target.innerHTML = `<article><div class="tracking-top"><div><span class="eyebrow">ORDER REFERENCE</span><h2>${esc(order.order_code)}</h2><p>${new Date(order.created_at).toLocaleString()}</p></div>${statusBadge(order.status)}</div>${order.status === 'cancelled' ? '<p>This order was cancelled.</p>' : `<div class="timeline">${timeline}</div>`}<div class="tracking-details"><div><span class="eyebrow">DELIVERY ADDRESS</span><p>${esc(order.delivery_address)}</p></div><div><span class="eyebrow">PAYMENT</span><p>${esc(order.payment_method)} · ${esc(order.payment_status)}</p></div><div><span class="eyebrow">TOTAL</span><p>${money(order.total)}</p></div><div><span class="eyebrow">RIDER</span><p>${esc(order.rider_name || 'Waiting for rider')}${order.rider_phone ? ` · <a href="tel:${esc(order.rider_phone)}">Call rider</a>` : ''}</p></div></div>${order.delivery_otp ? `<div class="delivery-code-box"><span>DELIVERY VERIFICATION CODE</span><strong>${esc(order.delivery_otp)}</strong><small>Share this code with your rider at delivery.</small></div>` : ''}<section class="live-map-section"><h3>${hasLocation ? 'Your rider’s latest location' : 'Delivery map'}</h3>${map}</section><h3>Items</h3>${(order.items || []).map((item) => `<div class="summary-row"><span>${item.quantity} × ${esc(item.product_name)}</span><strong>${money(item.unit_price * item.quantity)}</strong></div>`).join('')}</article>`;
    if (form) form.elements.namedItem('code').value = code;
    return order;
  } catch (error) {
    showMessage(form, error.message, true);
    target.innerHTML = '';
    return null;
  }
}

async function initTrack() {
  const form = document.querySelector('[data-track-form]');
  const result = document.querySelector('[data-track-result]');
  const params = new URLSearchParams(location.search);
  let currentCode = params.get('order') || form.elements.namedItem('code').value;
  try { await api('me'); } catch {
    if (currentCode) showMessage(form, 'Sign in to securely see your order and delivery location.', true);
  }
  const load = async () => {
    if (currentCode) await loadTrack(currentCode, result, form);
  };
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    currentCode = form.elements.namedItem('code').value.trim();
    const order = await loadTrack(currentCode, result, form);
    if (order) history.replaceState(null, '', `?page=track&order=${encodeURIComponent(currentCode)}`);
  });
  await load();
  if (currentCode) window.foodplanTrackTimer = setInterval(load, 10000);
}

async function initAccount() {
  const target = document.querySelector('[data-account]');
  try {
    const [user, orders, addresses, notifications] = await Promise.all([api('me'), api('orders'), api('addresses'), api('notifications')]);
    target.innerHTML = `<div class="account-grid"><article class="panel"><h2>Account details</h2><p><strong>${esc(user.name)}</strong><br>${esc(user.email)}<br>${esc(user.phone)}</p><button class="button button-outline" data-logout>Sign out</button><h3>Saved addresses</h3>${addresses.map((item) => `<p>${esc(item.label)} · ${esc(item.address)}</p>`).join('') || '<p>No saved addresses yet.</p>'}</article><article class="panel"><h2>Your orders</h2>${orders.map((order) => `<a class="order-row" href="?page=track&order=${encodeURIComponent(order.order_code)}"><strong>${esc(order.order_code)}</strong>${statusBadge(order.status)}<b>${money(order.total)}</b></a>`).join('') || '<p>No orders yet.</p>'}<h3>Notifications</h3>${notifications.slice(0, 8).map((note) => `<p class="notification"><strong>${esc(note.title)}</strong><br>${esc(note.message)}</p>`).join('')}</article></div>`;
  } catch (error) { target.innerHTML = `<div class="error-banner">${esc(error.message)} <a href="?page=login">Sign in</a></div>`; }
}

async function initReviews() {
  const listing = document.querySelector('[data-reviews]');
  const productsSelect = document.querySelector('[data-review-products]');
  try {
    const [reviews, products] = await Promise.all([api('reviews'), api('products')]);
    listing.innerHTML = reviews.map((item) => `<article class="review-item"><strong>${esc(item.product)} · ${'★'.repeat(Number(item.rating))}</strong><p>${esc(item.review)}</p><small>${esc(item.customer)}</small></article>`).join('') || '<p>No reviews yet.</p>';
    productsSelect.innerHTML = products.map((product) => `<option value="${esc(product.id)}">${esc(product.name)}</option>`).join('');
  } catch (error) { listing.innerHTML = `<div class="error-banner">${esc(error.message)}</div>`; }
}

async function initSupport() {
  const form = document.querySelector('[data-support-form]');
  const list = document.querySelector('[data-support-list]');
  try {
    const tickets = await api('support');
    list.innerHTML = `<h3>Your recent requests</h3>${tickets.map((ticket) => `<p>${esc(ticket.subject)} · ${statusBadge(ticket.status)}</p>`).join('')}`;
  } catch (error) {
    list.innerHTML = `<p class="muted">${esc(error.message)} Sign in to view your requests.</p>`;
  }
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    try { const result = await api('support', Object.fromEntries(new FormData(form)), 'POST'); showMessage(form, result.message); form.reset(); }
    catch (error) { showMessage(form, error.message, true); }
  });
}

async function initAdmin() {
  try {
    const user = await api('me');
    if (user.role !== 'admin') throw new Error('Sign in with an administrator account.');
  } catch (error) {
    document.querySelector('.admin-content').innerHTML = `<div class="error-banner">${esc(error.message)} <a href="?page=login&role=admin">Admin sign in</a> · <a href="?page=setup-admin">Set up first admin</a></div>`;
    return;
  }
  await loadCategories();
  const get = async (action, target, draw) => {
    try { draw(await api(action), target); } catch (error) { target.innerHTML = `<div class="error-banner">${esc(error.message)}</div>`; }
  };
  await Promise.all([
    get('admin-overview', document.querySelector('[data-admin-overview]'), (data, node) => {
      node.innerHTML = `<h2>Overview</h2><div class="metric-grid">${[['Sales', money(data.stats.sales)], ['Today', money(data.stats.today_sales)], ['Orders', data.stats.orders], ['To fulfill', data.stats.pending_orders], ['Customers', data.stats.customers], ['Riders', data.stats.riders]].map(([label, value]) => `<article><small>${label}</small><strong>${value}</strong></article>`).join('')}</div><h3>Recent orders</h3>${table(['Order', 'Customer', 'Status', 'Total'], data.recent.map((item) => `<tr><td>${esc(item.order_code)}</td><td>${esc(item.customer_name)}</td><td>${statusBadge(item.status)}</td><td>${money(item.total)}</td></tr>`))}`;
    }),
    get('orders', document.querySelector('[data-admin-orders]'), (orders, node) => {
      const pending = orders.filter((order) => order.status === 'placed').length;
      node.innerHTML = pending ? `<p class="form-notice">${pending} order${pending === 1 ? '' : 's'} awaiting confirmation.</p>` : '';
      node.innerHTML += table(['Order', 'Customer', 'Total', 'Payment', 'Status', 'Rider', 'Action'], orders.map((order) => {
        const moves = { confirmed: ['processing', 'cancelled'], processing: ['packaging', 'cancelled'], packaging: ['ready', 'cancelled'], ready: ['cancelled'] }[order.status] || [];
        const state = order.status === 'processing' ? 'Preparing' : order.status === 'packaging' ? 'Packing' : order.status.replaceAll('_', ' ');
        const status = moves.length ? `<select data-order-status="${esc(order.order_code)}"><option value="${esc(order.status)}">${esc(state)}</option>${moves.map((move) => `<option value="${move}">${move === 'processing' ? 'Preparing' : move === 'packaging' ? 'Packing' : move}</option>`).join('')}</select>` : statusBadge(order.status);
        const confirm = order.status === 'placed' ? `<button class="button button-small" data-confirm-order="${esc(order.order_code)}">Confirm order</button>` : '';
        const assign = order.status === 'ready' && !order.rider_name ? `<select data-assign-order="${esc(order.order_code)}"><option value="">Assign rider…</option></select>` : esc(order.rider_name || '—');
        return `<tr><td>${esc(order.order_code)}<small>${new Date(order.created_at).toLocaleString()}</small></td><td>${esc(order.customer_name)}<small>${esc(order.delivery_address)}</small></td><td>${money(order.total)}</td><td>${esc(order.payment_method)} · ${esc(order.payment_status)}</td><td>${status}</td><td>${assign}</td><td>${confirm}<a href="?page=track&order=${encodeURIComponent(order.order_code)}">View</a></td></tr>`;
      }));
      api('admin-riders').then((riders) => {
        node.querySelectorAll('[data-assign-order]').forEach((select) => {
          select.innerHTML += riders.filter((rider) => Number(rider.active) && rider.rider_status === 'available').map((rider) => `<option value="${esc(rider.id)}">${esc(rider.full_name)}</option>`).join('');
        });
      }).catch((error) => {
        toast(`Could not load delivery riders: ${error.message}`);
      });
    }),
    get('admin-rider-requests', document.querySelector('[data-admin-rider-requests]'), (requests, node) => {
      const items = Array.isArray(requests) ? requests : [];
      node.innerHTML = items.length
        ? table(['Order', 'Rider', 'Customer / delivery address', 'Requested', 'Decision'], items.map((request) => `<tr><td><strong>${esc(request.order_code)}</strong><small>${money(request.total)}</small></td><td>${esc(request.rider_name)}<small>${esc(request.rider_email)} · ${esc(request.rider_phone || '')}</small></td><td>${esc(request.customer_name)}<small>${esc(request.delivery_address)}</small></td><td>${new Date(request.created_at).toLocaleString()}</td><td><button class="button button-small" data-rider-request="${esc(request.id)}" data-decision="approve">Approve</button> <button class="button button-small button-outline" data-rider-request="${esc(request.id)}" data-decision="reject">Decline</button></td></tr>`))
        : '<p class="empty-state">No rider pickup requests are waiting for approval.</p>';
    }),
    get('admin-products', document.querySelector('[data-admin-products]'), (rows, node) => { node.innerHTML = table(['Dish', 'Category', 'Price', 'Stock', 'Status', 'Action'], rows.map((item) => `<tr><td>${esc(item.name)}</td><td>${esc(item.category_name)}</td><td>${money(item.price)}</td><td>${esc(item.stock)}</td><td>${item.available ? 'Available' : 'Hidden'}</td><td><button data-hide-product="${esc(item.id)}">${item.available ? 'Deactivate' : 'Activate'}</button></td></tr>`)); }),
    get('admin-reservations', document.querySelector('[data-admin-reservations]'), (rows, node) => { node.innerHTML = table(['Guest', 'Date', 'Guests', 'Phone', 'Status'], rows.map((item) => `<tr><td>${esc(item.customer_name)}</td><td>${esc(item.reservation_date)}</td><td>${esc(item.guests)}</td><td>${esc(item.customer_phone)}</td><td><select data-reservation="${esc(item.id)}"><option ${item.status === 'requested' ? 'selected' : ''}>requested</option><option ${item.status === 'confirmed' ? 'selected' : ''}>confirmed</option><option ${item.status === 'cancelled' ? 'selected' : ''}>cancelled</option></select></td></tr>`)); }),
    get('admin-riders', document.querySelector('[data-admin-riders]'), (rows, node) => { node.innerHTML = table(['Name', 'Email', 'Phone', 'Active orders', 'Completed', 'Availability'], rows.map((item) => `<tr><td>${esc(item.full_name)}</td><td>${esc(item.email)}</td><td>${esc(item.phone)}</td><td>${esc(item.active_orders || 0)}</td><td>${esc(item.completed || 0)}</td><td>${esc(item.rider_status)}</td></tr>`)); }),
    get('admin-users', document.querySelector('[data-admin-customers]'), (rows, node) => { node.innerHTML = table(['Customer', 'Email', 'Phone', 'Orders', 'Joined'], rows.map((item) => `<tr><td>${esc(item.full_name)}</td><td>${esc(item.email)}</td><td>${esc(item.phone)}</td><td>${esc(item.order_count)}</td><td>${esc(item.created_at)}</td></tr>`)); }),
    get('admin-payments', document.querySelector('[data-admin-payments]'), (rows, node) => { node.innerHTML = table(['Reference', 'Order', 'Customer', 'Method', 'Amount', 'Status'], rows.map((item) => `<tr><td>${esc(item.transaction_ref)}</td><td>${esc(item.order_code)}</td><td>${esc(item.customer_name)}</td><td>${esc(item.method)}</td><td>${money(item.amount)}</td><td>${esc(item.status)}</td></tr>`)); }),
    get('admin-coupons', document.querySelector('[data-admin-coupons]'), (rows, node) => { node.innerHTML = table(['Code', 'Description', 'Type', 'Discount', 'Minimum'], rows.map((item) => `<tr><td>${esc(item.code)}</td><td>${esc(item.description)}</td><td>${esc(item.discount_type)}</td><td>${esc(item.discount_value)}</td><td>${money(item.minimum_order)}</td></tr>`)); }),
    get('admin-tickets', document.querySelector('[data-admin-tickets]'), (rows, node) => { node.innerHTML = table(['Ticket', 'Customer', 'Order', 'Message', 'Status'], rows.map((item) => `<tr><td>${esc(item.subject)}</td><td>${esc(item.customer)}</td><td>${esc(item.order_code || '—')}</td><td>${esc(item.message)}</td><td>${statusBadge(item.status)}</td></tr>`)); }),
  ]);
  document.querySelectorAll('[data-toggle-form]').forEach((button) => button.addEventListener('click', () => document.getElementById(button.dataset.toggleForm).classList.toggle('hidden')));
  document.querySelector('[data-admin-product]')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const button = form.querySelector('button[type="submit"],button:not([type])');
    button.disabled = true;
    try {
      const result = await api('admin-products', Object.fromEntries(new FormData(form)), 'POST');
      toast(result.message || 'Product saved.');
      location.reload();
    } catch (error) {
      showMessage(form, error.message, true);
    } finally {
      button.disabled = false;
    }
  });
  document.querySelector('[data-admin-rider]')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    try { await api('admin-rider-create', Object.fromEntries(new FormData(event.currentTarget)), 'POST'); toast('Rider created.'); location.reload(); } catch (error) { showMessage(event.currentTarget, error.message, true); }
  });
  document.querySelector('[data-admin-coupon]')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    try { await api('admin-coupons', Object.fromEntries(new FormData(event.currentTarget)), 'POST'); toast('Coupon created.'); location.reload(); } catch (error) { showMessage(event.currentTarget, error.message, true); }
  });
}

async function initRider() {
  try {
    const user = await api('me');
    if (user.role !== 'rider') throw new Error('Sign in with a rider account.');
  } catch (error) {
    document.querySelector('.rider-dashboard').innerHTML = `<div class="error-banner">${esc(error.message)} <a href="?page=login&role=rider">Rider sign in</a></div>`;
    return;
  }
  const availableNode = document.querySelector('[data-rider-available]');
  const ordersNode = document.querySelector('[data-rider-orders]');
  const messagesNode = document.querySelector('[data-rider-messages]');
  let previousReadyCodes = null;
  const refresh = async () => {
    const [available, orders, notifications] = await Promise.all([api('available-orders'), api('orders'), api('notifications')]);
    const activeDelivery = orders.some((order) => ['out_for_delivery', 'picked_up', 'on_the_way', 'arrived'].includes(order.status));
    if (!activeDelivery && riderWatchId !== null) {
      navigator.geolocation?.clearWatch(riderWatchId);
      riderWatchId = null;
      riderLocationMessage = 'Delivery complete. Location sharing stopped.';
    }
    const readyCodes = new Set(available.map((order) => order.order_code));
    if (previousReadyCodes) {
      for (const orderCode of readyCodes) {
        if (!previousReadyCodes.has(orderCode)) toast(`New order ${orderCode} is ready. Request pickup for admin approval.`);
      }
    }
    previousReadyCodes = readyCodes;
    availableNode.innerHTML = available.length ? available.map((order) => {
      const requested = order.request_status === 'pending';
      const label = requested ? 'Request sent — awaiting admin' : order.request_status === 'rejected' ? 'Request again' : 'Request pickup';
      return `<article class="available-order"><div><strong>${esc(order.order_code)}</strong><small>${new Date(order.created_at).toLocaleString()}</small></div><b>${money(order.total)}</b><button class="button button-small" data-request-order="${esc(order.order_code)}" ${requested || activeDelivery ? 'disabled' : ''}>${label}</button></article>`;
    }).join('') : '<p>No ready deliveries available. You will see new orders here when they are ready.</p>';
    messagesNode.innerHTML = notifications.length ? notifications.slice(0, 10).map((item) => `<p class="notification"><strong>${esc(item.title)}</strong><br>${esc(item.message)}<small>${esc(item.created_at)}</small></p>`).join('') : '<p class="muted">No messages yet. New order and request updates will appear here.</p>';
    ordersNode.innerHTML = orders.length ? orders.map((order) => {
      const next = { out_for_delivery: 'picked_up', picked_up: 'on_the_way', on_the_way: 'arrived', arrived: 'delivered' }[order.status];
      const labels = { picked_up: 'Confirm pickup', on_the_way: 'Start delivery', arrived: 'I have arrived', delivered: 'Confirm delivery' };
      const active = ['out_for_delivery', 'picked_up', 'on_the_way', 'arrived'].includes(order.status);
      const locationMessage = riderLocationMessage || 'Your location is not being shared.';
      return `<article class="rider-order"><h3>${esc(order.order_code)} · ${esc(order.customer_name)}</h3>${statusBadge(order.status)}<p>Deliver to: ${esc(order.delivery_address)}</p><p>Customer: <a href="tel:${esc(order.customer_phone)}">${esc(order.customer_phone)}</a></p><p>${money(order.total)} · ${esc(order.payment_method)}</p>${active ? `<div class="rider-location-share"><span data-location-message>${esc(locationMessage)}</span><button class="button button-small button-outline" data-share-location>${riderWatchId !== null ? 'Stop sharing' : 'Share my location'}</button></div>` : ''}${next ? `${next === 'delivered' ? '<label>Customer verification code<input data-delivery-code type="text" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" maxlength="6" required></label>' : ''}<button class="button button-small" data-next-status="${next}" data-code="${esc(order.order_code)}" ${next === 'delivered' ? 'disabled' : ''}>${labels[next]}</button>` : ''}</article>`;
    }).join('') : '<p>No delivery accepted yet.</p>';
  };
  try {
    await refresh();
    window.foodplanRiderRefreshTimer = setInterval(() => {
      refresh().catch((error) => toast(`Could not refresh rider messages: ${error.message}`));
    }, 15000);
  } catch (error) {
    document.querySelector('.rider-dashboard').innerHTML = `<div class="error-banner">${esc(error.message)}</div>`;
  }
}

async function initPage() {
  updateCartCount();
  updateDeliveryArea();
  const page = document.body.dataset.page;
  const handlers = {
    home: initHome, menu: initMenu, offers: initOffers, cart: initCart, checkout: initCheckout,
    track: initTrack, account: initAccount, reviews: initReviews, support: initSupport,
    admin: initAdmin, rider: initRider,
  };
  await handlers[page]?.();
}

document.addEventListener('click', async (event) => {
  const add = event.target.closest('[data-add]');
  const confirm = event.target.closest('[data-confirm-order]');
  const assign = event.target.closest('[data-assign-order]');
  const accept = event.target.closest('[data-accept]');
  const requestOrder = event.target.closest('[data-request-order]');
  const riderRequest = event.target.closest('[data-rider-request]');
  const next = event.target.closest('[data-next-status]');
  const share = event.target.closest('[data-share-location]');
  const logout = event.target.closest('[data-logout]');
  const hide = event.target.closest('[data-hide-product]');
  if (add) {
    try {
      const product = await api('product', {}, 'GET', { id: add.dataset.add });
      const cart = getCart();
      const current = cart.find((item) => Number(item.id) === Number(product.id));
      if (current) current.quantity = Math.min(20, current.quantity + 1);
      else cart.push({ id: Number(product.id), name: product.name, price: Number(product.price) * (1 - Number(product.discount_percent || 0) / 100), image_url: product.image_url, quantity: 1 });
      setCart(cart);
      toast(`${product.name} added to cart.`);
    } catch (error) { toast(error.message); }
  }
  if (confirm) {
    confirm.disabled = true;
    try { const result = await api('admin-confirm', { code: confirm.dataset.confirmOrder }, 'POST'); toast(result.message); location.reload(); }
    catch (error) { toast(error.message); confirm.disabled = false; }
  }
  if (assign?.value) {
    try { await api('admin-assign', { code: assign.dataset.assignOrder, rider_id: Number(assign.value) }, 'POST'); toast('Rider assigned.'); location.reload(); }
    catch (error) { toast(error.message); }
  }
  if (accept) {
    accept.disabled = true;
    try { const result = await api('accept-order', { code: accept.dataset.accept }, 'POST'); toast(result.message); location.reload(); }
    catch (error) { toast(error.message); accept.disabled = false; }
  }
  if (requestOrder) {
    requestOrder.disabled = true;
    try {
      const result = await api('rider-request-order', { code: requestOrder.dataset.requestOrder }, 'POST');
      toast(result.message);
      location.reload();
    } catch (error) {
      toast(error.message);
      requestOrder.disabled = false;
    }
  }
  if (riderRequest) {
    riderRequest.disabled = true;
    try {
      const result = await api('admin-rider-requests', {
        request_id: Number(riderRequest.dataset.riderRequest),
        decision: riderRequest.dataset.decision,
      }, 'POST');
      toast(result.message);
      location.reload();
    } catch (error) {
      toast(error.message);
      riderRequest.disabled = false;
    }
  }
  if (next) {
    const card = next.closest('.rider-order');
    const deliveryCode = card.querySelector('[data-delivery-code]')?.value || '';
    next.disabled = true;
    try { await api('order-status', { code: next.dataset.code, status: next.dataset.nextStatus, delivery_code: deliveryCode }, 'POST'); toast('Delivery updated.'); location.reload(); }
    catch (error) { toast(error.message); next.disabled = false; }
  }
  if (share) startLocationSharing();
  if (logout && !logout.closest('.admin-content')) {
    try { await api('logout', {}, 'POST'); location.href = '?page=login'; } catch (error) { toast(error.message); }
  }
  if (hide) {
    try { await api('admin-product-update', { available: false }, 'POST', { id: hide.dataset.hideProduct }); location.reload(); } catch (error) { toast(error.message); }
  }
});

let riderWatchId = null;
let riderLocationMessage = '';
function syncRiderLocationUI() {
  document.querySelectorAll('[data-location-message]').forEach((node) => {
    node.textContent = riderLocationMessage || 'Your location is not being shared.';
  });
  document.querySelectorAll('[data-share-location]').forEach((button) => {
    button.textContent = riderWatchId !== null ? 'Stop sharing' : 'Share my location';
  });
}
function startLocationSharing() {
  if (riderWatchId !== null) {
    navigator.geolocation.clearWatch(riderWatchId);
    riderWatchId = null;
    riderLocationMessage = 'Location sharing stopped.';
    syncRiderLocationUI();
    return;
  }
  if (!navigator.geolocation) {
    riderLocationMessage = 'This browser does not support GPS location.';
    syncRiderLocationUI();
    return;
  }
  riderLocationMessage = 'Waiting for GPS permission…';
  syncRiderLocationUI();
  let lastSent = 0;
  riderWatchId = navigator.geolocation.watchPosition(async (position) => {
    if (Date.now() - lastSent < 10000) return;
    lastSent = Date.now();
    try {
      await api('rider-location', { latitude: position.coords.latitude, longitude: position.coords.longitude }, 'POST');
      riderLocationMessage = `Location shared · ${new Date().toLocaleTimeString()}`;
      syncRiderLocationUI();
    } catch (error) {
      riderLocationMessage = error.message;
      syncRiderLocationUI();
    }
  }, (error) => {
    if (riderWatchId !== null) navigator.geolocation.clearWatch(riderWatchId);
    riderLocationMessage = `GPS error: ${error.message}`;
    riderWatchId = null;
    syncRiderLocationUI();
  }, { enableHighAccuracy: true, maximumAge: 5000, timeout: 15000 });
  syncRiderLocationUI();
}

document.querySelector('.nav-toggle')?.addEventListener('click', (event) => {
  const nav = document.querySelector('.main-nav');
  const open = nav.classList.toggle('is-open');
  event.currentTarget.setAttribute('aria-expanded', String(open));
});
document.querySelector('[data-auth]')?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const action = form.dataset.auth;
  try {
    const result = await api(action, Object.fromEntries(new FormData(form)), 'POST');
    const next = new URLSearchParams(location.search).get('next');
    location.href = next === 'checkout' ? '?page=checkout' : `?page=${result.user.role === 'admin' ? 'admin' : result.user.role === 'rider' ? 'rider' : 'account'}`;
  } catch (error) { showMessage(form, error.message, true); }
});
document.querySelector('[data-setup-admin]')?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const button = form.querySelector('button[type="submit"],button:not([type])');
  button.disabled = true;
  try {
    const result = await api('setup-admin', {}, 'POST');
    location.href = `?page=${result.user.role === 'admin' ? 'admin' : 'login&role=admin'}`;
  }
  catch (error) { showMessage(form, error.message, true); button.disabled = false; }
});
document.querySelector('[data-api-form="reservations"]')?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const button = form.querySelector('button[type="submit"],button:not([type])');
  button.disabled = true;
  try {
    const result = await api('reservations', Object.fromEntries(new FormData(form)), 'POST');
    showMessage(form, result.message);
    form.reset();
  } catch (error) {
    showMessage(form, error.message, true);
  } finally {
    button.disabled = false;
  }
});
document.querySelector('[data-review-form]')?.addEventListener('submit', async (event) => {
  event.preventDefault();
  try { const result = await api('reviews', Object.fromEntries(new FormData(event.currentTarget)), 'POST'); showMessage(event.currentTarget, result.message); await initReviews(); }
  catch (error) { showMessage(event.currentTarget, error.message, true); }
});
document.addEventListener('change', async (event) => {
  const status = event.target.closest('[data-order-status]');
  const reservation = event.target.closest('[data-reservation]');
  if (status) {
    try { await api('order-status', { code: status.dataset.orderStatus, status: status.value }, 'POST'); toast('Order updated.'); location.reload(); }
    catch (error) { toast(error.message); status.selectedIndex = 0; }
  }
  if (reservation) {
    try { await api('admin-reservations', { status: reservation.value }, 'PATCH', { id: reservation.dataset.reservation }); toast('Reservation updated.'); }
    catch (error) { toast(error.message); }
  }
});
document.addEventListener('input', (event) => {
  const codeInput = event.target.closest('[data-delivery-code]');
  if (!codeInput) return;
  codeInput.value = codeInput.value.replace(/\D/g, '').slice(0, 6);
  const confirmButton = codeInput.closest('.rider-order').querySelector('[data-next-status="delivered"]');
  confirmButton.disabled = !/^\d{6}$/.test(codeInput.value);
});
initPage();

# Scope Detection Patterns

`getCurrentScope()` maps the current WP admin URL (query params + hash route) to a tour scope string. The scope string is the key used in `window.MYPLUGIN.tours[scope]`.

## URL-only pages (full page reloads)

```js
// page=myplugin-settings → 'settings'
if (page === 'myplugin-settings') return 'settings';
if (page === 'myplugin-wizard')   return 'wizard';
```

## Hash-routed SPA pages

```js
// Strip pagination: /orders/page/2 → /orders
const hash = window.location.hash.replace('#', '').replace(/\/page\/\d+$/, '');

if (!hash || hash === '/' || hash === '/dashboard') return 'dashboard';
if (hash.startsWith('/products/add'))  return 'add-product';  // sub-routes need startsWith
if (hash === '/orders/new')            return 'create-order';
if (hash === '/orders')                return 'orders';
```

**Order matters:** Put `startsWith` checks before exact-match checks for the same prefix.

## Listening for SPA navigation

```js
// Re-run on hash change (SPA routing)
window.addEventListener('hashchange', () => setTimeout(autoStartTours, 500));
```

## Common pitfalls

| Mistake | Correct |
|---------|---------|
| `page.startsWith('myplugin-')` | `page.startsWith('myplugin')` — bare `page=myplugin` is valid |
| `hash === '/products/add'` | `hash.startsWith('/products/add')` — sub-routes like `/products/add/digital` |
| `hashchange` without delay | `setTimeout(fn, 500)` — React needs render time |

# Browser Debug Patterns

## Console Error Capture

```js
// Run before triggering the action under test
const errors = [];
const handler = e => errors.push(e.message);
window.addEventListener('error', handler);
window.addEventListener('unhandledrejection', e => errors.push(e.reason?.message ?? e.reason));

// After the action:
console.log(errors);  // report via evaluate_script
```

Or via MCP after the fact:
```
list_console_messages()      // all messages this session
get_console_message(id)      // specific entry
```

---

## Network Request Inspection

```
list_network_requests()           // all requests since page load
get_network_request(id)           // response body + headers + status
```

**What to look for:**

| Symptom | Check |
|---------|-------|
| Settings not saving | POST to `admin-ajax.php` or REST endpoint — status 200? Response `success:false`? |
| Script not loading | GET for `.js` file — 404? Wrong URL path? |
| AJAX returning HTML | Response body is a PHP error/notice — check `WP_DEBUG` |
| 403 on AJAX | Missing nonce in request body or `X-WP-Nonce` header |

---

## evaluate_script for Debug Data

```js
// Read WP globals
wp.data.select('core').getSite()

// Read a hidden option via REST
const r = await fetch('/wp-json/wp/v2/settings', {
  headers: { 'X-WP-Nonce': wpApiSettings.nonce }
});
return await r.json();

// Check if a script is enqueued
Array.from(document.scripts).map(s => s.src).filter(s => s.includes('my-plugin'))

// Read current user
wp.data.select('core').getCurrentUser()

// Check admin notices
Array.from(document.querySelectorAll('.notice')).map(n => n.innerText.trim())
```

---

## Screenshot Strategy

```
take_screenshot()    // full page
```

**When to screenshot:**
- Before + after a form save (prove state changed)
- On any broken-layout report (capture with evidence)
- After activating/deactivating plugin (capture notice)
- When a test fails (attach to report)

**Annotate findings in text** — quote exact visible text alongside screenshot path so the report is readable without opening the image.

---

## PHP Error Detection in Browser

WP admin outputs PHP notices/warnings as HTML before the page doctype when `WP_DEBUG=true` and `WP_DEBUG_DISPLAY=true`. Detect via:

```js
// Check if page starts with error output
document.documentElement.outerHTML.substring(0, 500)
```

If output contains `<b>Notice</b>`, `<b>Warning</b>`, or `<b>Fatal error</b>` before `<!DOCTYPE`, there is a PHP error leaking into the response.

---

## Lighthouse Audit (Performance / Accessibility)

```
lighthouse_audit(url, categories=['performance','accessibility','best-practices'])
```

Use when: checking a front-end page changed by the plugin, not for wp-admin pages (admin is not public-facing).

---

## Common Debug Checklist

```
[ ] JS console — any errors?
[ ] Network — any 4xx/5xx requests?
[ ] PHP notice in HTML output?
[ ] Admin notices visible on screen?
[ ] Correct plugin version loaded? (check enqueued script URL)
[ ] Correct user role for the test?
```

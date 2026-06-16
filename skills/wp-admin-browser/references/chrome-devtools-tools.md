# Chrome DevTools MCP — Tool Reference (WP Admin Subset)

## Input Automation

| Tool | Use for | Required params |
|------|---------|-----------------|
| `fill_form` | Fill multiple fields at once — **always prefer over individual fills** | `elements: [{uid, value}]` |
| `fill` | Single input, select, checkbox | `uid`, `value` (`"true"`/`"false"` for checkboxes) |
| `click` | Buttons, links, menu items | `uid` |
| `press_key` | Keyboard shortcuts, Enter to submit, Tab to focus | `key` (e.g. `"Enter"`, `"Control+S"`) |
| `handle_dialog` | WordPress delete confirmations, beforeunload | `action: "accept" \| "dismiss"` |
| `upload_file` | Media uploads, avatar uploads | `uid`, `filePath` |
| `type_text` | Type into already-focused field | `text`, optional `submitKey` |

### fill_form pattern
```json
{
  "elements": [
    { "uid": "e12", "value": "tmp_admin" },
    { "uid": "e13", "value": "admin@example.com" },
    { "uid": "e14", "value": "true" }
  ]
}
```
Checkboxes/toggles: `"true"` = checked, `"false"` = unchecked.  
Radio buttons: `"true"` to select.  
`<select>`: pass the option **value** string.

---

## Navigation

| Tool | Use for | Key params |
|------|---------|-----------|
| `take_snapshot` | Get page elements + UIDs before interacting | none (use before every fill/click) |
| `navigate_page` | Login redirect only — not for admin navigation | `type: "url"`, `url` |
| `wait_for` | Wait after clicks/submits before next action | `text: ["string to wait for"]` |
| `list_pages` | Check open tabs | none |
| `select_page` | Switch active tab | `pageId` |

### Always snapshot before interacting
```
take_snapshot → read UIDs → fill_form/click
```
Never guess UIDs. Always take a fresh snapshot after navigation.

---

## Debugging

| Tool | Use for |
|------|---------|
| `take_screenshot` | Verify page state visually |
| `evaluate_script` | Read-only data fetch, get nonce, check DOM state |
| `list_console_messages` | Check for JS errors after form submit |
| `list_network_requests` | Verify form submission fired correct request |

### evaluate_script for REST nonce (read-only)
```js
async () => {
  return fetch('/wp-admin/admin-ajax.php?action=rest-nonce', {
    credentials: 'include'
  }).then(r => r.text());
}
```

### evaluate_script for read-only API call
```js
async () => {
  const nonce = await fetch('/wp-admin/admin-ajax.php?action=rest-nonce', {credentials:'include'}).then(r=>r.text());
  const res = await fetch('/wp-json/wc-affiliate/v1/affiliates?per_page=5', {
    credentials: 'include',
    headers: { 'X-WP-Nonce': nonce }
  });
  return res.json();
}
```

---

## Tool Priority Rules

```
fill_form  >  fill (always batch inputs)
take_snapshot  >  take_screenshot (text is cheaper, use screenshot only to verify visually)
wait_for  >  sleep (never sleep — wait for text confirmation)
click (menu item)  >  navigate_page (for WP admin navigation)
```

---

## Interaction Sequence Template

```
1. take_snapshot                        → get UIDs
2. fill_form([...fields])               → fill all fields at once
3. click(submit button uid)             → submit
4. wait_for(["Settings saved", "..."])  → confirm success
5. take_screenshot (optional)           → visual verify
6. list_console_messages (if issue)     → debug errors
```

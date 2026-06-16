---
name: wp-admin-browser
description: Use when interacting with a WordPress admin panel via Chrome DevTools MCP — logging in, creating users, navigating menus, submitting forms, or performing any data operations (create/update/delete) through the browser. Also use when needing a temporary admin user for testing instead of the main admin account.
---

# WordPress Admin via Browser (Chrome DevTools MCP)

## Core Rules — Non-Negotiable

1. **Never touch the main admin user.** Always create a temporary admin for testing.
2. **All data operations go through the browser UI.** No WP-CLI, no direct DB, no REST API calls to mutate data — use WordPress forms.
3. **Navigate via menus, not hardcoded URLs.** Click the menu item; don't jump straight to `/wp-admin/users.php?action=...`.
4. **Use `fill_form` + click for all inputs.** Never skip the form and post directly.

---

## Step 1 — Log In

```js
// POST to wp-login.php via fetch (fastest, no UI needed for login itself)
async () => {
  const res = await fetch('/wp-login.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      log: 'YOUR_USER',
      pwd: 'YOUR_PASS',
      'wp-submit': 'Log In',
      redirect_to: '/wp-admin/',
      testcookie: '1',
    }),
    credentials: 'include',
    redirect: 'follow',
  });
  return { ok: res.ok, url: res.url };
}
```

Login via `fetch` is acceptable because it's a pure auth step — no data mutation.

---

## Step 2 — Create a Temporary Admin User

**Always create a temp user before any testing that requires admin actions.**

Navigate to Users → Add New via menu clicks, not direct URL.

```
Admin menu → Users → Add New
```

Fill the form using `fill_form`:

| Field | Value |
|-------|-------|
| Username | `tmp_admin_<timestamp>` |
| Email | `tmp+<timestamp>@example.com` |
| First Name | `Temp` |
| Last Name | `Admin` |
| Role | `Administrator` |
| Password | strong generated password |
| Send notification | unchecked |

After testing: **delete the temp user** via Users list → hover → Delete.

---

## Step 3 — Get a REST Nonce (for read-only API calls only)

```js
async () => {
  return fetch('/wp-admin/admin-ajax.php?action=rest-nonce', {
    credentials: 'include',
  }).then(r => r.text());
}
```

Use nonce only for **GET** requests to read data. All mutations go through WP forms.

---

## Browser Interaction Rules

### Navigation
```
✅ Click: Admin menu → Submenu item
❌ Never: navigate_page to hardcoded /wp-admin/edit.php?post_type=...
```

### Forms
```
✅ fill_form on visible fields → click Submit button
❌ Never: fetch POST directly to admin-post.php / admin-ajax.php for data changes
❌ Never: wp eval or wp post create via CLI for browser-visible operations
```

### Menus
Always wait for the page to load after each menu click before interacting with the next element.

---

## Data Operation Checklist

| Operation | Method |
|-----------|--------|
| Create post/page | Posts → Add New → fill form → Publish |
| Update user | Users → find user → Edit → fill form → Update |
| Delete item | List view → hover row → Delete (confirm dialog) |
| Change setting | Settings menu → fill field → Save Changes |
| Install plugin | Plugins → Add New → Search → Install → Activate |

---

## Common Mistakes

| Mistake | Fix |
|---------|-----|
| Using main admin for destructive tests | Create temp admin first |
| Navigating directly to `?action=delete&id=X` | Use list UI → Delete link |
| Using `wp user create` CLI to seed browser session | Use Add New User form |
| Skipping `fill_form` and posting via fetch | Always fill the visible form |
| Leaving temp admin after testing | Delete via Users list when done |

---

## References

- [Chrome DevTools MCP tools](references/chrome-devtools-tools.md) — full tool params, interaction sequence template, priority rules
- [WordPress admin navigation](references/wp-admin-navigation.md) — menu paths, form submit patterns, success strings

---

## Cleanup

After every test session:
1. Log in as main admin (or existing admin).
2. Users → find all `tmp_admin_*` users → bulk delete.
3. Do **not** reassign their content (temp users have no content).

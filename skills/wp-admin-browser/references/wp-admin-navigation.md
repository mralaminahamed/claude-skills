# WordPress Admin Navigation Reference

## Login

```js
// Via fetch — acceptable for auth (no data mutation)
async () => {
  const res = await fetch('/wp-login.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      log: 'USERNAME',
      pwd: 'PASSWORD',
      'wp-submit': 'Log In',
      redirect_to: '/wp-admin/',
      testcookie: '1',
    }),
    credentials: 'include',
    redirect: 'follow',
  });
  return { ok: res.ok, url: res.url };  // url should end in /wp-admin/
}
```

---

## Temp Admin User — Create

**Required before any destructive or test operation.**

```
Admin Bar / Left Menu:
  Users → Add New User

Fields to fill (fill_form):
  - user_login    → "tmp_admin_<timestamp>"
  - email         → "tmp+<timestamp>@example.com"
  - first_name    → "Temp"
  - last_name     → "Admin"
  - role (select) → "administrator"
  - user_pass     → strong password
  - send_password → "false" (uncheck notification)

Submit: click "Add New User" button
Wait for: "New user created"
```

## Temp Admin User — Delete

```
Users → All Users
  → hover row for tmp_admin_* user
  → click "Delete"
  → handle_dialog(accept) or click "Confirm Deletion"
  → select "Delete all content" (no reassign needed)
```

---

## Common Admin Navigation Paths (click-based)

### Posts
```
Dashboard → Posts → All Posts        (list)
Dashboard → Posts → Add New          (create)
All Posts → hover row → Edit         (update)
All Posts → hover row → Trash        (delete)
```

### Pages
```
Dashboard → Pages → All Pages
Dashboard → Pages → Add New
```

### Users
```
Dashboard → Users → All Users
Dashboard → Users → Add New User
All Users → hover row → Edit
```

### Media
```
Dashboard → Media → Library
Dashboard → Media → Add New Media File
```

### Settings
```
Dashboard → Settings → General
Dashboard → Settings → Reading
Dashboard → Settings → Permalinks     ← save after any permalink change
```

### Plugins
```
Dashboard → Plugins → Installed Plugins
Dashboard → Plugins → Add New Plugin
Installed Plugins → hover row → Activate / Deactivate / Delete
```

### WooCommerce
```
Dashboard → WooCommerce → Orders
Dashboard → WooCommerce → Products
Dashboard → WooCommerce → Settings
```

### WC Affiliate (plugin-specific)
```
Dashboard → WC Affiliate → Dashboard
Dashboard → WC Affiliate → Affiliates
Dashboard → WC Affiliate → Referrals
Dashboard → WC Affiliate → Transactions
Dashboard → WC Affiliate → Settings
```

---

## Form Submit Patterns

### Standard Settings Page
```
fill_form([...setting fields])
click("Save Changes" button)
wait_for(["Settings saved."])
```

### Post/Page Editor (Classic)
```
fill_form([title, content fields])
click("Publish" or "Update" button)
wait_for(["Post published", "Page updated"])
```

### Post/Page Editor (Gutenberg/Block)
```
click Title field → type_text(title)
click content area → type_text(content)
click "Publish" / "Save" button (top-right)
wait_for(["is now live", "saved"])
```

### Delete with Confirmation
```
click("Delete" or "Move to Trash")
handle_dialog("accept")          ← if JS confirm dialog
-- OR --
wait_for(["Are you sure"])
click("OK" / "Confirm" button)
```

---

## Success / Error Confirmation Strings

| Action | Wait for text |
|--------|---------------|
| Settings saved | `"Settings saved."` |
| User created | `"New user created"` |
| User updated | `"User updated."` |
| Post published | `"Post published."` |
| Post updated | `"Post updated."` |
| Plugin activated | `"Plugin activated."` |
| Plugin deactivated | `"Plugin deactivated."` |
| Item deleted | `"moved to the Trash"` or `"deleted"` |
| Permalink saved | `"Permalink structure updated."` |

---

## Rules Summary

| Situation | Do | Don't |
|-----------|-----|-------|
| Navigate to Users list | Click `Users` in menu | `navigate_page('/wp-admin/users.php')` |
| Delete a post | Hover row → click Trash | Fetch `?action=delete&post=N` |
| Change settings | Fill form → Save Changes | `wp option update` CLI |
| Create user | Add New User form | `wp user create` CLI |
| Read data for display | `evaluate_script` + fetch GET | — |
| Mutate data | WP admin form | `evaluate_script` + fetch POST |

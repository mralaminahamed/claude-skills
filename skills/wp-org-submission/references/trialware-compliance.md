# Trialware Compliance — Guideline 5

## The rule

**The free plugin on WP.org must be 100% functional.** No features gated behind a license key, upgrade prompt, or Pro plan check.

Non-dismissible admin notices are a separate Guideline 11 violation — see Issue 17 in `review-issues-catalog.md`.

WP.org allows a freemium model but the Pro tier must be a **completely separate plugin**, hosted on the developer's own website, with no licensing code inside the free plugin.

---

## WP.org-legal freemium pattern

```
Free plugin (on WP.org)          Pro plugin (on your site)
────────────────────────         ────────────────────────
All current features, fully      Genuinely new features not
functional, no gating.           present in the free plugin.

Hosted: plugins.svn.             Hosted: your own site or
wordpress.org                    a marketplace (EDD, Freemius).
```

Key distinction: the Pro plugin **adds** features; it does not **unlock** features that exist in the free plugin's codebase.

---

## Code patterns that trigger a Guideline 5 violation

Reviewers look at the code, not just the UI. Any of these in the free plugin's source is a violation:

```php
// License key check gating a feature
if ( ! $this->plan_manager->is_pro() ) {
    return; // Silently skips functionality
}

// Upgrade nag in UI
if ( ! $this->is_pro ) {
    echo '<p>Upgrade to Pro to enable this feature.</p>';
}

// Disabled UI element with "Requires Pro" tooltip
<button disabled title="Requires Pro">Run backfill</button>
```

```tsx
// TypeScript/React — same principle
const { isPro } = usePlan();
<option value="both" disabled={!isPro}>Both directions (Requires Pro)</option>
```

```php
// Comments that hint at free-tier limitation
// Deletion logging only — no Sheets row removal in free tier.
// Pro only: sync both directions
```

Even code comments that reference "free tier" limitations indicate the plugin is designed for feature gating.

---

## Audit checklist — Guideline 5

Run before submission whenever the plugin previously had a freemium model.

**PHP files:**
- [ ] No `PlanManager`, `LicenseManager`, `SubscriptionManager` class
- [ ] No `is_pro()`, `require_pro()`, `is_licensed()` method calls
- [ ] No `license_key` in `get_option()` / `update_option()` / `sanitize_settings()`
- [ ] No "free tier" or "Pro only" comments in hook handlers or pusher/puller classes
- [ ] No feature guards that return early based on plan status
- [ ] Plugin's settings option has no `license_key` field

**TypeScript/React files:**
- [ ] No `usePlan` / `useLicense` hook
- [ ] No Plans/Pricing page component
- [ ] No `isPro` / `isLicensed` boolean from context/store
- [ ] No `disabled` UI elements with "Requires Pro" or "Upgrade" labels
- [ ] No feature matrix showing locked vs unlocked items
- [ ] No `plan: 'free' | 'pro'` in types or API responses

**Assets / localize data:**
- [ ] `wp_localize_script` data has no `plan` or `license_key_exists` field
- [ ] REST API responses have no `plan` field
- [ ] `safe_settings()` / `mask_secrets()` have no `license_key` entry

**readme.txt / UI copy:**
- [ ] FAQ answers don't say "Pro feature" or "requires upgrade"
- [ ] Description doesn't imply any feature is unavailable in the free version

---

## How to remove a licensing layer

Typical removal path when a free plugin previously had `PlanManager`-style gating.

> **Note:** The file paths, class names, and method names below are from the **StoreSheet** plugin. Substitute your own plugin's equivalents.

### 1. Delete licensing infrastructure

```
includes/Licensing/PlanManager.php          → delete
src/hooks/usePlan.ts                        → delete
src/components/pages/PlansPage.tsx          → delete
```

### 2. Remove references from main class

```php
// class-storesheet.php — remove:
use StoreSheet\Licensing\PlanManager;
private ?PlanManager $plan_manager = null;
public function plan_manager(): PlanManager { … }
```

### 3. Clean settings controller

```php
// SettingsController::sanitize_settings() — remove license_key field
// SettingsController::mask_secrets() — remove license_key from key_map
// SettingsController::save_settings() — remove license_key from preserve loop
```

### 4. Clean assets / localize data

```php
// Assets::enqueue() — remove 'plan' => storesheet()->plan_manager()->get_plan()
// Assets::safe_settings() — remove 'license_key' from key_map
// OverviewController::get_overview() — remove 'plan' from response
```

### 5. Clean TypeScript types

```ts
// src/types/index.ts — remove from Settings interface:
license_key: string;
license_key_exists: boolean;

// Remove from OverviewData and StoresheetAdmin:
plan: 'free' | 'pro';
```

### 6. Un-gate UI elements

```tsx
// SyncPage.tsx — was:
<option value="both" disabled={!isPro}>Both directions (Requires Pro.)</option>

// After:
<option value="both">Both directions. Two-way sync.</option>
```

### 7. Remove hook comments

```php
// ProductHooks.php — remove comments like:
// Deletion logging only — no Sheets row removal in free tier.
```

### 8. Clean up Nav / routing

```tsx
// Nav.tsx — remove Unlock Pro promo block, Crown import, usePlan import
// App.tsx — remove PlansPage route and import
// src/config/tabs.ts — remove Plans tab entry
```

---

## What to say to the reviewer

When replying to a Guideline 5 rejection, be brief. Context only — no change list:

> "The Pro companion plugin is a separate plugin hosted on my own website that adds genuinely new features not present in this package. I've removed the Plans page and all license-key handling from this plugin. Every feature is now fully functional for all users with no gating."

---

## External services section — Plans page WP.org API call

If the removed Plans page was fetching data from `api.wordpress.org` (to show other plugins), remove that entry from `== External services ==` in `readme.txt` as well:

```
== WordPress.org Plugin Directory (admin only) ==     ← delete this block
The Plans page optionally fetches a list of the plugin author's other plugins
from the WordPress.org API…
```

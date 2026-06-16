# Root-Cause Pattern Catalog

Recurring QA-failure patterns seen on this codebase, each as:
**symptom (what QA sees) → root cause → how to detect → fix.**

Add a new entry every time a non-obvious bug is traced. This file is the
memory that makes each round faster than the last.

---

## PHP / Backend

### P1 — `(int)` cast of an array yields `1` after `load()` overwrites a field

- **Symptom:** A feature scoped to "the wrong entity" — email sent to affiliate
  #1, lookup always resolves the first record, a check silently fails for every
  row except id 1.
- **Root cause:** `load()` replaces a raw scalar (`$this->data['affiliate']`, an
  int from the DB) with an object array (`$affiliate->to_array()`). A later
  `(int) $this->data['affiliate']` casts an array → `1` in PHP 8.
- **Detect:** Grep the model's `load()` for `$this->data['X'] = ...->to_array()`.
  Check every `get_*()` that casts `X`.
- **Fix:** Stash the raw scalar before the overwrite, read the stash in the getter.
  ```php
  $this->data['affiliate_id'] = (int) $this->data['affiliate']; // before overwrite
  // getter:
  return (int) ( $this->data['affiliate_id'] ?? $this->get( 'affiliate' ) );
  ```
- **Seen in:** PR #343 (Transaction + Referral models).

### P2 — Hook fired in only one of several state-transition paths

- **Symptom:** An email/notification fires for some flows but is silently
  dropped for another that reaches the same end state.
- **Root cause:** `do_action( 'wc_affiliate_payout_processed', ... )` lived only
  inside `update_status('completed')`. The payout path inserts a row directly
  with `status = 'completed'` via `create()`, never calling `update_status()`,
  so the hook never fires.
- **Detect:** `grep -rn "do_action( 'the_hook'"` — find ALL call sites. Then map
  every code path that reaches the triggering state. Any path that sets the
  state without going through the hook site is a silent drop.
- **Fix:** Fire the hook in `create()` too when the persisted status matches:
  ```php
  if ( self::STATUS_COMPLETED === $transaction->get_status() ) {
      do_action( 'wc_affiliate_payout_processed', ... );
  }
  ```
- **Seen in:** PR #343 (Transaction::create).

### P3 — Email shows raw `%%token%%` instead of the value (placeholder/context key mismatch)

- **Symptom:** QA sees a raw placeholder label (`%%bonus_amount%%`) or a blank
  field in a delivered email instead of the actual value.
- **Root cause:** The email template renders `%%key%%` tokens, but the
  `compose_*()` method's `context` array uses a *different* key (e.g. template
  wants `bonus_amount`, context provides `amount`; template wants
  `referred_affiliate_name`, context provides `referred_user_name`). Unmatched
  tokens pass through `str_replace` untouched → shown raw.
- **Detect:** Grep the default template body for every `%%...%%` token, then
  diff that set against the keys in the compose method's `context` array. Any
  token with no matching context key is a raw-label bug.
  ```bash
  grep -o '%%[a-z_]*%%' <template>   # tokens the template needs
  # compare to the context array keys in the compose_* method
  ```
- **Fix:** Add the missing keys to the context (keep old keys for back-compat).
  Note: numeric values get `number_format`; to prepend the currency symbol the
  key must be in the `wc_affiliate_email_currency_fields` filter list.
- **Seen in:** PR #135 (wc-affiliate-pro bonus emails — `bonus_amount`,
  `referred_affiliate_name`, `referred_affiliate_email`).

### P4 — Pro/add-on bug actually lives in the base plugin

- **Symptom:** QA reports a feature broken while testing an add-on PR, but the
  feature is owned by the parent/base plugin.
- **Root cause:** The add-on has no code for that feature; the bug is in base and
  may already be fixed in a separate, unmerged base PR. On the QA env (base
  `master` + add-on branch) it still reproduces.
- **Detect:** `grep -rn "<feature>" app legacy` in the add-on repo. Zero hits →
  not the add-on's concern. Check the base repo for an open fix PR.
- **Fix:** Don't patch the add-on. In the QA comment, point to the base PR and
  note it must merge first. (Paid Referral on PR #135 → base wc-affiliate#343.)

---

## Frontend / SPA

### F1 — Chart/select/list renders the raw key/id instead of the label

- **Symptom:** QA sees a raw slug (`not_converted`, `Non_converted`) in a
  tooltip, option, or legend instead of the friendly translated text.
- **Root cause:** Visualization libs (nivo, recharts, react-select, etc.) render
  the datum **id/value** by default unless given a custom label/tooltip renderer.
  nivo `ResponsivePie` default tooltip prints `datum.id`.
- **Detect:** Open the chart/list component. If there's no `tooltip=` /
  `formatOptionLabel=` / `renderItem=` prop, the default raw render is in effect.
- **Fix:** Pass a render prop that uses the label field:
  ```tsx
  tooltip={ ( { datum } ) => (
    <div className="...">{ datum.data.label }: { datum.value }</div>
  ) }
  ```
- **Seen in:** PR #362 (admin + dashboard ConversionChart).

### F2 — Near-duplicate components drift; fix exists in one, missing in the other

- **Symptom:** QA: "works on screen A, broken on screen B" — same feature, two
  surfaces (admin vs dashboard, list vs detail).
- **Root cause:** The same component was copy-pasted into two trees and edited
  independently. One copy got a CSS/logic fix (`!mb-0`, `items-center`, a guard)
  the other never received.
- **Detect:** `diff` the two components, or grep both for the class/prop that's
  correct on A.
  ```bash
  diff spa/admin/.../ConversionHeader.tsx spa/dashboard/.../ConversionHeader.tsx
  ```
- **Fix:** Port the missing change so both match. (Longer term: extract a shared
  component — note it, don't necessarily do it mid-QA-fix.)
- **Seen in:** PR #362 (ConversionHeader legend alignment: dashboard had `!mb-0`
  + `items-center`, admin did not).

### F3 — Default `<p>`/element margin breaks vertical alignment

- **Symptom:** Legend/label text sits slightly below its icon/dot; "please align".
- **Root cause:** Theme/global CSS gives `<p>` a default bottom margin; inside a
  flex row it pushes the text off-center from its sibling dot.
- **Detect:** Look for bare `<p>` in a flex row without a margin reset.
- **Fix:** `!mb-0` (Tailwind important reset) on the text, `items-center` on the row.
- **Seen in:** PR #362.

---

## Tooling / Environment (not product bugs, but they block verification)

### E1 — `yarn` corepack lockfile error

- **Symptom:** `This package doesn't seem to be present in your lockfile`.
- **Cause:** Global yarn/corepack version drifted from the project lockfile.
- **Workaround:** Call the local binaries directly — `node_modules/.bin/eslint`,
  `node_modules/.bin/wp-scripts build`.

### E2 — `.eslintrc.js` circular structure / `tsc` deprecation halt

- **Symptom:** `Converting circular structure to JSON` from eslint; tsc stops on
  `Option 'X' is deprecated`.
- **Cause:** Installed eslint/TS newer than the project config targets.
- **Workaround:** `node_modules/.bin/tsc --noEmit --ignoreDeprecations 6.0`.
  Treat the resulting large error count as pre-existing **unless** an error names
  one of your changed files. The build compiling is the real pass signal.

## chart-uses-wrong-stat-field

- **Symptom:** chart/slice counts mismatch the rate or sibling numbers shown elsewhere (e.g. conversion pie shows 3/5 but rate says 75%).
- **Cause:** component reads a plausible-but-wrong field from the stats payload. Classic: conversion pie uses `total_referrals` (and on affiliate path that's period_stats = approved+paid only) instead of `converted_visits`. Two near-identical fields, wrong one picked.
- **Detect:** dump the stats payload (browser fetch with X-WP-Nonce); compare every candidate field to what the chart renders. Note backend methods that scope counts (e.g. `get_period_statistics` filters to approved+paid).
- **Fix:** use the field whose definition matches the chart's meaning (a conversion pie = converted_visits vs total_visits-converted_visits; sums to total_visits, no clamp). Apply to ALL sibling chart components (admin + dashboard), not just the one QA happened to screenshot.

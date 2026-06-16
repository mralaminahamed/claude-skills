# Conventional Commits — WP Plugin Reference

## Format

```
<type>(<scope>): <summary>

[optional body — wrap at 72 chars]

[optional footer: Closes #N, Breaking change notice]
```

---

## Types

| Type | When to use |
|------|------------|
| `feat` | New feature visible to users |
| `fix` | Bug fix |
| `refactor` | Code change with no behavior change |
| `perf` | Performance improvement |
| `test` | Add or fix tests only |
| `ci` | CI/CD config changes |
| `build` | Build system, webpack, composer |
| `chore` | Maintenance: version bumps, dep updates, cleanup |
| `docs` | Documentation only |
| `revert` | Revert a prior commit |
| `wip` | Work in progress (draft PR commits only — squash before merge) |

**No `style` type** — formatting changes go under `refactor` or `chore`.

---

## Scope

Scope = the affected subsystem. Keep it a single noun, kebab-case. Common scopes for WP plugins:

```
admin          Frontend admin pages
api            REST API endpoints
blocks         Gutenberg blocks
build          Webpack / @wordpress/scripts
checkout       Checkout flow (WooCommerce)
ci             GitHub Actions workflows
coupons        Coupon logic
db             Database / migrations
email          Email templates and sending
gateway        Payment gateway
i18n           Translations
orders         Order processing
phpcs          Coding standards config
phpstan        Static analysis
release        Version bump / changelog
settings       Plugin settings
shipping       Shipping methods
tests          Test suite scaffolding
```

Omit scope when the change is truly cross-cutting (e.g., a rename across all files).

---

## Examples

```bash
feat(blocks): add testimonials block with schema markup
fix(gateway): handle null response from payment API on timeout
refactor(orders): replace WP_Query with wc_get_orders for HPOS compat
perf(api): cache product meta queries with transients
test(checkout): add Brain\Monkey unit tests for discount calculator
ci(phpunit): add PHP 8.3 to matrix
build(webpack): split admin and frontend entry points
chore(deps): update WooCommerce tested-up-to to 8.5
docs(readme): add screenshots for onboarding wizard
revert(shipping): revert "add zone-based rate override" (breaks flat-rate)
fix(i18n): load text domain on init not plugins_loaded
```

---

## Multi-Commit PR Rules

One commit per scope. Never mix scopes in one commit.

```bash
# ✅ Three commits, three scopes
fix(coupons): clamp discount to cart total
fix(admin): show correct order count in dashboard widget
build(webpack): add source maps for development mode

# ❌ One lump commit — hard to revert, hard to bisect
fix: various fixes
```

---

## Footer Conventions

```
Closes #42          # closes issue on same repo
Closes owner/repo#42  # cross-repo issue close (PR merges to code repo)
Fixes #42           # alias for Closes
Relates to #15      # reference without closing
Reverts <sha>       # mandatory on revert commits
BREAKING CHANGE: <description>  # triggers major version bump in semver
```

---

## Summary Line Rules

- Imperative mood: "add", "fix", "remove" — not "added" / "fixes" / "removes"
- Lowercase after the colon
- No period at end
- Max 72 characters total (including type/scope prefix)
- Describe WHAT changed, not WHY (why goes in body)

```bash
# ✅
fix(api): return 404 when product not found instead of 500

# ❌
Fix: Fixed the API to not return 500 errors anymore.
```

---

## Reword Non-Conventional Commits Before PR

```bash
# Interactive rebase to fix last N commits
git rebase -i HEAD~3

# In the editor: change 'pick' to 'reword' for commits to fix
# Git opens each for editing in turn
```

CI validates commit messages on PR — a non-conventional commit blocks merge on repos with `validate-commit-messages` check.

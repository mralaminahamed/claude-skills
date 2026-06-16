# GitHub Repo Setup

Standard commands for creating and configuring a new phpstan-stubs repo.

## Create repo

```bash
gh repo create mralaminahamed/phpstan-<slug>-stubs \
  --public \
  --description "<PluginName> function and class declaration stubs for static analysis." \
  --source=. \
  --remote=origin \
  --push
```

## Ensure main branch (not trunk)

```bash
# If local branch is trunk
git branch -m trunk main
git push origin main

# Set default branch on GitHub
gh repo edit mralaminahamed/phpstan-<slug>-stubs --default-branch main

# Delete trunk ref if it was pushed
gh api repos/mralaminahamed/phpstan-<slug>-stubs/git/refs/heads/trunk -X DELETE 2>/dev/null || true
```

## Add topics

Standard topics for all phpstan-stubs packages:
```bash
gh repo edit mralaminahamed/phpstan-<slug>-stubs \
  --add-topic phpstan \
  --add-topic php \
  --add-topic stubs \
  --add-topic wordpress \
  --add-topic phpstan-stubs \
  --add-topic <slug>
```

Extra topics by type:
- wp-plugin: `--add-topic wordpress-plugin`
- composer: `--add-topic composer`

## Update description and topics on existing repo

```bash
gh repo edit mralaminahamed/phpstan-<slug>-stubs \
  --description "<PluginName> function and class declaration stubs for static analysis."
```

## Push tags

```bash
git push origin main --follow-tags
# or push all tags at once
git push origin --tags
```

## Verify tags on remote

```bash
gh api repos/mralaminahamed/phpstan-<slug>-stubs/tags --jq '.[].name' | sort -V
```

## Set PERSONAL_TOKEN secret (for release workflow PR creation)

```bash
gh secret set PERSONAL_TOKEN --repo mralaminahamed/phpstan-<slug>-stubs
```

## List all mralaminahamed phpstan-stubs repos

```bash
gh repo list mralaminahamed --json name,description,url \
  --jq '.[] | select(.name | startswith("phpstan-")) | [.name, .url] | @tsv'
```

## Current 11 repos (as of June 2026)

| Repo | Source type |
|------|-------------|
| phpstan-freemius-stubs | composer |
| phpstan-dokan-stubs | wp-plugin |
| phpstan-fluent-forms-stubs | wp-plugin |
| phpstan-forminator-stubs | wp-plugin |
| phpstan-ninja-forms-stubs | wp-plugin |
| phpstan-squad-modules-lite-stubs | wp-plugin |
| phpstan-wpforms-lite-stubs | wp-plugin |
| phpstan-woocommerce-product-addons-stubs | paid |
| phpstan-woocommerce-subscriptions-stubs | paid |
| phpstan-surecart-stubs | wp-plugin |
| phpstan-action-scheduler-stubs | composer |

# Contributing

## Adding a skill

1. Create `skills/<skill-name>/SKILL.md` (kebab-case directory name).
2. Add frontmatter — `name` and `description` are required. `version` and `compatibility` are optional and not enforced by CI:

   ```markdown
   ---
   name: my-skill
   description: Use when <trigger conditions>. <what it does>. Not for: <out-of-scope cases> — use `other-skill`.
   ---

   # Skill Title

   ## When to use

   - "Phrase that triggers this skill", "another trigger phrase".

   **Not for:** Out-of-scope task — use `other-skill-name`.

   ## Method

   Step-by-step instructions…

   ## References

   - `references/patterns.md` — description of what's in the file.
   ```

3. Write a `description` that leads with the trigger (`Use when…`), states what it does, and ends with `Not for:` cases pointing to the correct skill. Claude Code matches on the description to auto-activate.
4. Add a row to the skill table in [README.md](README.md) under the appropriate group.

## References

Put supporting material that is too long for `SKILL.md` into `references/` files inside the skill directory.

- One file per topic (e.g. `patterns.md`, `config-examples.md`, `error-catalog.md`).
- Reference every file explicitly in a `## References` section at the bottom of `SKILL.md` with a one-line description — Claude won't read a file it doesn't know exists.
- Use `scripts/` for executable helpers (bash, Python). Reference them as `scripts/<name>.sh` from `SKILL.md`.
- Aim for ≥ 3 reference files per skill so the skill has depth beyond the main doc.

## Evals

Every skill should have `evals/evals.json`. Evals verify the skill triggers correctly and produces the right output.

```json
{
  "skill_name": "my-skill",
  "evals": [
    {
      "id": 0,
      "name": "short-slug-describing-scenario",
      "prompt": "Natural language prompt a user would actually type.",
      "expected_output": "What Claude should do/produce — be specific about commands run, files changed, decisions made. Not a transcript, a checklist of what correct behaviour looks like.",
      "files": []
    }
  ]
}
```

Rules:
- `skill_name` must match the directory name exactly.
- `id` values are sequential integers starting at 0.
- Write at least 3 evals per skill: setup-from-scratch, debug/fix-broken, golden-path real-world scenario.
- `expected_output` describes *behaviour*, not prose. List what Claude must do, in what order, with what tools/commands.
- `files` is reserved for fixture file paths — leave `[]` until fixtures exist.

## Conventions

- Kebab-case for all directory and file names.
- One skill = one focused job. Split rather than overload.
- `## When to use` (lowercase u) in every `SKILL.md`.
- `**Not for:**` cross-references use backtick skill names: `use \`other-skill\``.
- Cross-ref to official skills uses the official skill's exact name (e.g. `wp-plugin-development`, `wp-phpstan`).
- No real project/plugin names in skill content — use generic mockup names.
- Reference scripts via `${CLAUDE_PLUGIN_ROOT}` — never hardcode absolute paths.

## Validation

CI ([`.github/workflows/validate.yml`](.github/workflows/validate.yml)) runs on every push/PR and checks:

- both `.claude-plugin/*.json` manifests are valid JSON,
- `plugin.json`, `marketplace.json`, `gemini-extension.json`, and `package.json` versions all match,
- every `skills/*/` has a `SKILL.md` with `name` + `description` frontmatter.

Run it locally before pushing:

```bash
python3 .github/scripts/validate_skills.py
```

## Releasing

Bump the version in **all four** manifests — keep them in sync:

- `.claude-plugin/plugin.json` → `version`
- `.claude-plugin/marketplace.json` → `plugins[0].version`
- `gemini-extension.json` → `version`
- `package.json` → `version`

Commit, tag `vX.Y.Z`, push.

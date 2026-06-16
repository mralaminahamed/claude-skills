# Contributing

Thanks for improving **claude-skills**.

## Adding a skill

1. Create `skills/<skill-name>/SKILL.md` (kebab-case directory name).
2. Add frontmatter — `name` and `description` are required:

   ```markdown
   ---
   name: my-skill
   description: Use when <trigger conditions>. <what it does>.
   ---

   Skill instructions…
   ```

3. Write a `description` that states **when** to use the skill — Claude Code matches on it to auto-activate. Lead with the trigger ("Use when…"), then what it does.
4. Put supporting material in `references/` (docs) or `scripts/` (helpers) inside the skill directory.
5. Add a row to the table in [README.md](README.md).

## Conventions

- Kebab-case for directory and file names.
- One skill = one focused job. Split rather than overload.
- Reference scripts via `${CLAUDE_PLUGIN_ROOT}` in skill bodies — never hardcode absolute paths.

## Validation

CI ([`.github/workflows/validate.yml`](.github/workflows/validate.yml)) runs on every push/PR and checks:

- both `.claude-plugin/*.json` manifests are valid JSON,
- `plugin.json` and `marketplace.json` versions match,
- every `skills/*/` has a `SKILL.md` with `name` + `description` frontmatter.

Run it locally before pushing:

```bash
python3 .github/scripts/validate_skills.py
```

## Releasing

Bump the version in **both** manifests (`plugin.json` and `marketplace.json`), commit, tag `vX.Y.Z`, push.

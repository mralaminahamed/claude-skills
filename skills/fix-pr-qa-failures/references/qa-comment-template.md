# QA Re-test Comment Template

Fill the placeholders and post with `gh pr comment <number> --repo <owner/repo> --body "..."`.
Keep the four required sections: **what changed table**, **per-feature steps**,
**regression check**, **commit SHAs**.

```markdown
## QA Re-test Request — <one-line scope>

Thanks @<qa-handle>. <N> issue(s) from your last review are fixed.

### What changed

| Issue | Root cause | Fix |
|---|---|---|
| **<feature/symptom>** (<surface>) | <one line> | <one line> |
| **<feature/symptom>** (<surface>) | <one line> | <one line> |

<Note any feature that was already correct and is unchanged.>

### Steps to test

> Run `git checkout <branch> && yarn build` first.   <!-- only if frontend -->

1. **<Feature A>** (<exact UI path: wp-admin → … >)
   - <action> → <expected result, specific>
2. **<Feature B>** (<exact UI path>)
   - <action> → <expected result>
3. **Regression — <previously passing feature>** (<UI path>)
   - Confirm <X> still works (was already passing)

### Commits
- `<sha>` <commit subject>
- `<sha>` <commit subject>
```

## Rules

- **Never** add an AI-attribution footer (no "Generated with Claude Code", no 🤖). Standing user preference.
- **Surface-specific paths.** "Hover the pie slice on Affiliate Dashboard →
  tooltip reads 'Converted: N'" beats "check the chart".
- **Always include a regression line** for features QA already confirmed, so a
  later fix isn't assumed to have broken them.
- **List the exact SHAs** pushed this round — QA tests against those, not the
  whole PR history.
- **Mirror QA's wording** for each symptom so they recognize their own report.

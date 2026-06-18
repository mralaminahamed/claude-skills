# Install wp-dev-skills

One install per agent. Pick yours from the table below.

## Per-agent install

| Agent | Install command | Auto-activates? |
|---|---|:-:|
| **Claude Code** | See below | Yes |
| **Gemini CLI** | `gemini extensions install https://github.com/mralaminahamed/wp-dev-skills` | Yes |
| **opencode** | `curl -fsSL https://raw.githubusercontent.com/mralaminahamed/wp-dev-skills/trunk/AGENTS.md > AGENTS.md` | Yes (AGENTS.md) |
| **Cursor** | See rule-file section below | Yes (rule file) |
| **Windsurf** | See rule-file section below | Yes (rule file) |
| **Cline** | See rule-file section below | Yes (rule file) |
| **GitHub Copilot** | See rule-file section below | Yes (rule file) |
| **Codex CLI** | `npx skills add mralaminahamed/wp-dev-skills -a codex` | Per-session: name a skill |
| **Continue** | `npx skills add mralaminahamed/wp-dev-skills -a continue` | No — name skill per session |
| **Roo Code** | `npx skills add mralaminahamed/wp-dev-skills -a roo` | No |
| **Augment Code** | `npx skills add mralaminahamed/wp-dev-skills -a augment` | No |
| **Sourcegraph Amp** | `npx skills add mralaminahamed/wp-dev-skills -a amp` | No |
| **Warp** | `npx skills add mralaminahamed/wp-dev-skills -a warp` | No |
| **Aider Desk** | `npx skills add mralaminahamed/wp-dev-skills -a aider-desk` | No |
| **Block Goose** | `npx skills add mralaminahamed/wp-dev-skills -a goose` | No |
| **OpenHands** | `npx skills add mralaminahamed/wp-dev-skills -a openhands` | No |
| **Devin** | `npx skills add mralaminahamed/wp-dev-skills -a devin` | No |
| **Kilo Code** | `npx skills add mralaminahamed/wp-dev-skills -a kilo` | No |
| **IBM Bob** | `npx skills add mralaminahamed/wp-dev-skills -a bob` | No |

For "auto-activates? No" agents — reference a skill by name in your session (e.g. "use wp-plugin-release skill to bump the version").

---

## Claude Code

```bash
claude plugin marketplace add mralaminahamed/wp-dev-skills
claude plugin install wp-dev-skills@wp-dev-skills
```

From a local clone:

```bash
claude plugin marketplace add ~/path/to/wp-dev-skills
claude plugin install wp-dev-skills@wp-dev-skills
```

Skills activate automatically — no slash commands needed.

## Gemini CLI

```bash
gemini extensions install https://github.com/mralaminahamed/wp-dev-skills
```

## Cursor / Windsurf / Cline / GitHub Copilot

Drop the rule file into your repo — pick the path for your agent:

```bash
# Cursor
mkdir -p .cursor/rules
curl -fsSL https://raw.githubusercontent.com/mralaminahamed/wp-dev-skills/trunk/src/rules/wp-dev-skills.md \
  > .cursor/rules/wp-dev-skills.mdc

# Windsurf
mkdir -p .windsurf/rules
curl -fsSL https://raw.githubusercontent.com/mralaminahamed/wp-dev-skills/trunk/src/rules/wp-dev-skills.md \
  > .windsurf/rules/wp-dev-skills.md

# Cline
curl -fsSL https://raw.githubusercontent.com/mralaminahamed/wp-dev-skills/trunk/src/rules/wp-dev-skills.md \
  > .clinerules/wp-dev-skills.md

# GitHub Copilot
mkdir -p .github
curl -fsSL https://raw.githubusercontent.com/mralaminahamed/wp-dev-skills/trunk/src/rules/wp-dev-skills.md \
  > .github/copilot-instructions.md
```

## opencode / AGENTS.md-based agents (Codex, Devin, OpenHands, …)

```bash
curl -fsSL https://raw.githubusercontent.com/mralaminahamed/wp-dev-skills/trunk/AGENTS.md > AGENTS.md
```

## All other agents (Continue, Roo, Augment, Amp, Warp, …)

```bash
npx skills add mralaminahamed/wp-dev-skills -a <agent-slug>
```

Replace `<agent-slug>` with your agent id. Run `npx skills list` to see all supported slugs.

---

## Repo layout

```
wp-dev-skills/
├── .claude-plugin/
│   ├── plugin.json               # Claude Code plugin manifest
│   └── marketplace.json          # Claude Code marketplace manifest
├── .codex/
│   ├── config.toml               # Codex CLI features
│   └── hooks.json                # Codex SessionStart hook
├── src/rules/
│   └── wp-dev-skills.md          # Rule file for Cursor/Windsurf/Cline/Copilot
├── AGENTS.md                     # Universal context (opencode, Codex, Devin, …)
├── GEMINI.md                     # Gemini CLI context (@-include syntax)
├── gemini-extension.json         # Gemini CLI extension manifest
├── package.json                  # npx skills compatibility
└── skills/
    └── <skill-name>/
        ├── SKILL.md              # required — frontmatter: name, description
        ├── references/           # supporting docs and code patterns
        ├── evals/
        │   └── evals.json        # eval scenarios for testing the skill
        └── scripts/              # optional helper scripts
```

---

Stuck? [Open an issue](https://github.com/mralaminahamed/wp-dev-skills/issues).

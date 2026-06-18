# Install wp-dev-skills

One install per agent. Pick yours from the table below.

## Per-agent install

| Agent | Install command | Auto-activates? |
|---|---|:-:|
| **Claude Code** | See below | Yes |
| **Gemini CLI** | `gemini extensions install https://github.com/mralaminahamed/wp-dev-skills` | Yes (extension) |
| **Gemini CLI** | `npx skills add mralaminahamed/wp-dev-skills -a gemini-cli` | On-demand |
| **opencode** | `curl -fsSL https://raw.githubusercontent.com/mralaminahamed/wp-dev-skills/trunk/AGENTS.md > AGENTS.md` | Yes (AGENTS.md) |
| **Cursor** | Rule file (always-on) — see below | Yes |
| **Cursor** | `npx skills add mralaminahamed/wp-dev-skills -a cursor` | On-demand |
| **Windsurf** | Rule file (always-on) — see below | Yes |
| **Windsurf** | `npx skills add mralaminahamed/wp-dev-skills -a windsurf` | On-demand |
| **Cline** | Rule file (always-on) — see below | Yes |
| **Cline** | `npx skills add mralaminahamed/wp-dev-skills -a cline` | On-demand |
| **GitHub Copilot** | Rule file — see below | Yes |
| **Codex** | `npx skills add mralaminahamed/wp-dev-skills -a codex` | On-demand |
| **Continue** | `npx skills add mralaminahamed/wp-dev-skills -a continue` | On-demand |
| **Roo Code** | `npx skills add mralaminahamed/wp-dev-skills -a roo` | On-demand |
| **Augment Code** | `npx skills add mralaminahamed/wp-dev-skills -a augment` | On-demand |
| **Amp / Replit** | `npx skills add mralaminahamed/wp-dev-skills -a amp` | On-demand |
| **Warp** | `npx skills add mralaminahamed/wp-dev-skills -a warp` | On-demand |
| **Aider Desk** | `npx skills add mralaminahamed/wp-dev-skills -a aider-desk` | On-demand |
| **Block Goose** | `npx skills add mralaminahamed/wp-dev-skills -a goose` | On-demand |
| **OpenHands** | `npx skills add mralaminahamed/wp-dev-skills -a openhands` | On-demand |
| **Devin** | `npx skills add mralaminahamed/wp-dev-skills -a devin` | On-demand |
| **Kilo Code** | `npx skills add mralaminahamed/wp-dev-skills -a kilo` | On-demand |
| **IBM Bob** | `npx skills add mralaminahamed/wp-dev-skills -a bob` | On-demand |
| **Kiro CLI** | `npx skills add mralaminahamed/wp-dev-skills -a kiro-cli` | On-demand |
| **Rovo Dev** | `npx skills add mralaminahamed/wp-dev-skills -a rovodev` | On-demand |
| **Qwen Code** | `npx skills add mralaminahamed/wp-dev-skills -a qwen-code` | On-demand |
| **Trae** | `npx skills add mralaminahamed/wp-dev-skills -a trae` | On-demand |

**Always-on vs on-demand:**
- **Rule file** — loads all skill descriptions into every session (more context, always available)
- **npx skills** — injects skills only when the agent detects a matching task (less context, better for large skill sets)

For on-demand skills — say the skill name: "use wp-plugin-release to bump the version".

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

As a native extension (auto-discovers all skills):

```bash
gemini extensions install https://github.com/mralaminahamed/wp-dev-skills
```

Or via npx skills (on-demand):

```bash
npx skills add mralaminahamed/wp-dev-skills -a gemini-cli
```

## Cursor / Windsurf / Cline / GitHub Copilot

**Option A — Rule file (always-on).** Drop the rule file into your repo:

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

**Option B — npx skills (on-demand).** Installs all SKILL.md files to the agent's skills directory:

```bash
npx skills add mralaminahamed/wp-dev-skills -a cursor
npx skills add mralaminahamed/wp-dev-skills -a windsurf
npx skills add mralaminahamed/wp-dev-skills -a cline
```

## opencode / AGENTS.md-based agents (Codex, Devin, OpenHands, …)

```bash
curl -fsSL https://raw.githubusercontent.com/mralaminahamed/wp-dev-skills/trunk/AGENTS.md > AGENTS.md
```

## All other agents

```bash
npx skills add mralaminahamed/wp-dev-skills -a <agent-slug>
```

Run `npx skills add mralaminahamed/wp-dev-skills --list` to preview all skills before installing.
Run `npx skills find wordpress` to search the public skills registry.

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
├── GEMINI.md                     # Gemini CLI context
├── gemini-extension.json         # Gemini CLI extension manifest
├── package.json                  # npm metadata
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

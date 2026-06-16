#!/usr/bin/env python3
"""Validate claude-skills plugin structure. Stdlib only."""
import json
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
errors = []


def fail(msg):
    errors.append(msg)


def load_json(path):
    try:
        return json.loads(path.read_text())
    except FileNotFoundError:
        fail(f"missing: {path.relative_to(ROOT)}")
    except json.JSONDecodeError as e:
        fail(f"invalid JSON in {path.relative_to(ROOT)}: {e}")
    return None


def parse_frontmatter(text):
    """Return dict of top-level scalar keys from a leading --- block."""
    if not text.startswith("---"):
        return None
    end = text.find("\n---", 3)
    if end == -1:
        return None
    out = {}
    for line in text[3:end].splitlines():
        if ":" in line and not line.startswith((" ", "\t", "#")):
            k, _, v = line.partition(":")
            out[k.strip()] = v.strip()
    return out


# 1. manifests
plugin = load_json(ROOT / ".claude-plugin/plugin.json")
market = load_json(ROOT / ".claude-plugin/marketplace.json")

# 2. versions in sync
if plugin and market:
    pv = plugin.get("version")
    mv = (market.get("plugins") or [{}])[0].get("version")
    if pv != mv:
        fail(f"version mismatch: plugin.json={pv} marketplace.json={mv}")

# 3. skills
skills_dir = ROOT / "skills"
if not skills_dir.is_dir():
    fail("missing skills/ directory")
else:
    found = sorted(p for p in skills_dir.iterdir() if p.is_dir())
    if not found:
        fail("no skills found under skills/")
    for d in found:
        sk = d / "SKILL.md"
        if not sk.is_file():
            fail(f"{d.name}: missing SKILL.md")
            continue
        fm = parse_frontmatter(sk.read_text())
        if fm is None:
            fail(f"{d.name}: SKILL.md has no frontmatter block")
            continue
        if not fm.get("name"):
            fail(f"{d.name}: SKILL.md frontmatter missing 'name'")
        if not fm.get("description"):
            fail(f"{d.name}: SKILL.md frontmatter missing 'description'")
    print(f"checked {len(found)} skill(s)")

if errors:
    print("\nVALIDATION FAILED:")
    for e in errors:
        print(f"  - {e}")
    sys.exit(1)
print("OK: all checks passed")

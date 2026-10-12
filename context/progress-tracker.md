# Progress tracker

Where the project is now. Keep this short; move finished history into the changelog in the readme.

## Phase

**v1.0.0 — live on WordPress.org since 2025-10-04, and the repo matches it.**
On 2026-10-13 the repo was re-labelled from 1.0.3 to 1.0.0: the live zip
(`downloads.wordpress.org/plugin/cb-qr-code.1.0.0.zip`) and every shipped file here are
byte-identical, and that commit is tagged `v1.0.0` with a GitHub release. The code itself never
changed — 1.0.1–1.0.3 were GitHub-only labels on what became 1.0.0.

The next WordPress.org upload is **1.0.1**.

## As of 2026-10-13

- Agent rules and `context/` set up (this file, `agent.md`, `CLAUDE.md`).
- PHP 7.4 lint and Plugin Check's PHPCS ruleset (`plugin-review.xml`) both pass clean.
- [issues.md](issues.md) has open entries from a read-through; #1 (percent-encoded URLs) matters
  most for the next release.
- Plugin is **not active** on cb-test and has no saved settings.

## Next

Nothing scheduled. Likely candidates: issues 1–3 as one "URL handling" change for 1.0.1.

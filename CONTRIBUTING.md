# Contributing to `mercadopago` (PrestaShop plugin)

This repository is part of the **Plugins & Payments (P&P)** domain at Mercado Libre and
follows the team's centralized development process. Read [`AGENTS.md`](AGENTS.md) and the
guides under [`.agents/`](.agents/) first — they are the source of truth for how this
repo is built, tested and shipped.

## Team references

- **Domain hub (P&P):** https://github.com/melisource/fury_mp-op-pp-sdd
- **Process & standards hub:** https://github.com/melisource/fury_mp-op-pp-development-cycle
  - Code review guide: [`docs/CODE_REVIEW_GUIDE.md`](https://github.com/melisource/fury_mp-op-pp-development-cycle/blob/master/docs/CODE_REVIEW_GUIDE.md)
  - Coding standards: [`docs/CODING_STANDARDS.md`](https://github.com/melisource/fury_mp-op-pp-development-cycle/blob/master/docs/CODING_STANDARDS.md)
  - Definition of Ready / Done: [`docs/DEFINITION_OF_READY.md`](https://github.com/melisource/fury_mp-op-pp-development-cycle/blob/master/docs/DEFINITION_OF_READY.md) · [`docs/DEFINITION_OF_DONE.md`](https://github.com/melisource/fury_mp-op-pp-development-cycle/blob/master/docs/DEFINITION_OF_DONE.md)

## PR process

1. Branch from `develop`: `feature/<description>` or `fix/<description>`.
2. Implement following the conventions in [`AGENTS.md`](AGENTS.md), the `.agents/` guides,
   and the layer rules under [`.claude/rules/`](.claude/rules/).
3. Run the quality gate locally before committing (see below). The pre-commit configuration runs the checks through managed hooks; see the
   [installation and push-stage instructions](.agents/runbook.md#local-validation-and-hooks).
4. Verify the Definition of Done in [`.agents/runbook.md`](.agents/runbook.md) and the
   DoD checklist in [`AGENTS.md`](AGENTS.md#process-dordod).
5. Open the PR against `develop` using [`.github/pull_request_template.md`](.github/pull_request_template.md).
6. Wait for review per the team's 5 review pillars:
   https://mercadolibre.atlassian.net/wiki/spaces/PLU/pages/2240021388/Processo+de+Revis+o+de+C+digo

## Local quality gate

Pre-commit is required for all Mercado Pago repositories. Every human or agent harness must run the repository pre-commit gate explicitly after final edits, including documentation/configuration changes, as required by [AGENTS.md](AGENTS.md#mandatory-validation-for-every-harness). Direct lint/test/build commands do not replace it.

```bash
composer install
npm ci
pre-commit install-hooks
pre-commit run --all-files
pre-commit run doc-freshness --hook-stage manual
```

Before pushing, run `pre-commit run doc-freshness --hook-stage manual --from-ref origin/develop --to-ref HEAD` against the committed PR range (adjust the base if the PR targets another branch). Preserve mandatory WebSec/DataSec hooks, rerun after hook fixes, and include commands/outcomes in the PR. If a required hook cannot pass, report validation as incomplete.

PHP/JS/CSS lint, both PHPUnit suites, the standalone URL test and an isolated production ZIP build run through `bin/check-local.sh`. CSS validation does not apply fixes. Rebuild assets explicitly with `npm run build:js && npm run build:css`, inspect and stage the generated files before committing. `npm run commit:pre` delegates to pre-commit; preserve managed WebSec/DataSec hooks and follow the runbook to activate the push gate. PHP 7.4 through 8.4 is recommended for the pinned PHPCS.

Document freshness reminds at commit and blocks at push/manual: edit affected `.agents/` guides in the branch. Scopes live in `.agents/aoc.yaml` under `freshness.guides`; guides do not need frontmatter or `version:` stamps. Use a specific `Docs-Exempt` commit trailer only for a justified exception. GitHub Actions are not used, and publishing remains an explicit release operation.

## Release checklist

Bump the version in **all three** places (they must match — see `traps.md`):

- `MP_VERSION` constant in `mercadopago.php`
- `$this->version` in `mercadopago.php`
- `version` field in `package.json`

Then update `CHANGELOG.md`, rebuild minified assets, and confirm `bin/create-release-zip.sh`
builds `mercadopago.zip` cleanly.

---

❤️ Thanks! Reviewer: see the team's 5 review pillars:
https://mercadolibre.atlassian.net/wiki/spaces/PLU/pages/2240021388/Processo+de+Revis+o+de+C+digo

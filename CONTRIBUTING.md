# Contributing to `mercadopago` (PrestaShop plugin)

This repository is part of the **Plugins & Payments (P&P)** domain at Mercado Libre and
follows the team's centralized development process. Read [`AGENTS.md`](AGENTS.md) and the
guides under [`docs/agent/`](docs/agent/) first — they are the source of truth for how this
repo is built, tested and shipped.

## Team references

- **Domain hub (P&P):** https://github.com/melisource/fury_mp-op-pp-sdd
- **Process & standards hub:** https://github.com/melisource/fury_mp-op-pp-development-cycle
  - Code review guide: [`docs/CODE_REVIEW_GUIDE.md`](https://github.com/melisource/fury_mp-op-pp-development-cycle/blob/master/docs/CODE_REVIEW_GUIDE.md)
  - Coding standards: [`docs/CODING_STANDARDS.md`](https://github.com/melisource/fury_mp-op-pp-development-cycle/blob/master/docs/CODING_STANDARDS.md)
  - Definition of Ready / Done: [`docs/DEFINITION_OF_READY.md`](https://github.com/melisource/fury_mp-op-pp-development-cycle/blob/master/docs/DEFINITION_OF_READY.md) · [`docs/DEFINITION_OF_DONE.md`](https://github.com/melisource/fury_mp-op-pp-development-cycle/blob/master/docs/DEFINITION_OF_DONE.md)

## PR process

1. Branch from `develop`: `feature/<description>` or `fix/<description>`.
2. Implement following the conventions in [`AGENTS.md`](AGENTS.md), the `docs/agent/` guides,
   and the layer rules under [`.claude/rules/`](.claude/rules/).
3. Run the quality gate locally before committing (see below). The `.husky/pre-commit` hook
   runs `npm run commit:pre` automatically.
4. Verify the Definition of Done in [`docs/agent/runbook.md`](docs/agent/runbook.md) and the
   DoD checklist in [`AGENTS.md`](AGENTS.md#process-dordod).
5. Open the PR against `develop` using [`.github/pull_request_template.md`](.github/pull_request_template.md).
6. Wait for review per the team's 5 review pillars:
   https://mercadolibre.atlassian.net/wiki/spaces/PLU/pages/2240021388/Processo+de+Revis+o+de+C+digo

## Local quality gate

```bash
composer install        # PHP dependencies (SDK + phpcs / php-cs-fixer)
npm install             # JS dev tooling (jshint, stylelint, minify, husky)

npm run php:lint        # PHP lint (composer phpcs, PSR2/PSR1 via phpcs.xml)
npm run lint:js         # JS lint (jshint)
npm run lint:css        # CSS lint (stylelint --fix)
npm run build:js        # rebuild minified JS  (commit the *.min.js)
npm run build:css       # rebuild minified CSS (commit the *.min.css)
```

`npm run commit:pre` runs all of the above and stages the regenerated minified assets. There is
no automated unit-test suite — see [`docs/agent/runbook.md`](docs/agent/runbook.md) ("Test Harness")
for what is and isn't covered, and [`docs/agent/traps.md`](docs/agent/traps.md) for the reasoning.

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

# Releasing

This package is distributed to customers via **Private Packagist**. Versions come
from **git tags** (there is intentionally no `version` field in `composer.json`).

## One-time distribution setup (Private Packagist)

1. Create/enter your Private Packagist organisation (packagist.com).
2. Add this repository as a **package source** (GitHub → `jmulders/mister-chameleon-statamic`).
   Private Packagist mirrors the repo and picks up every tag automatically.
3. Under **Composer Repository**, copy the customer repository URL
   (`https://repo.packagist.com/<your-org>/`).
4. Create per-customer or per-tier **access tokens** (Settings → Composer Repository
   → Tokens). Each customer authenticates with their own token — revoke to cut access.
5. Put the repository URL + token in the customer's onboarding e-mail (see README).

Customers then require `mister-chameleon/statamic:^1.0` — no GitHub access needed.

## Cutting a release

Semantic versioning: `MAJOR.MINOR.PATCH`.

- **PATCH** — bug fixes, no behaviour change (`v1.0.1`).
- **MINOR** — backwards-compatible features (`v1.1.0`).
- **MAJOR** — breaking changes (`v2.0.0`).

```bash
# 1. Land your changes on main and update CHANGELOG.md (move Unreleased → the version).
git checkout main && git pull

# 2. Tag and push.
git tag -a v1.0.0 -m "v1.0.0"
git push origin v1.0.0
```

Private Packagist syncs the new tag within a minute (or trigger a manual update).
Customers pick it up with `composer update mister-chameleon/statamic`.

## First release

Start at **v1.0.0** if you consider the current feature set stable for customers
(edge/client/hybrid, stable `mc_vid`, meta-keyword interest signals). Prefer
**v0.x** (e.g. `v0.1.0`) if you still expect breaking changes before GA — with
`0.x`, a MINOR bump is allowed to break, and customers pin with `^0.1`.

# Codereyes IMEI Ledger for Mobile Shops

A WordPress plugin for second-hand phone shops: a purchase and sale ledger where every phone is tracked by its IMEI.

- Record purchases (IMEI 1/2, phone details, seller with mobile and NID) and sales (buyer, price), with profit per phone.
- IMEI check-digit validation, live while typing. Bengali digits accepted everywhere.
- Duplicate protection: an IMEI already in stock is blocked; one sold before needs explicit confirmation.
- Search by IMEI, name, mobile, NID or model. Reports by date range. Excel-safe CSV export.

![All phones](docs/images/1-all-phones.jpg)

**Shop owners and staff:** read the [User Guide](docs/USER-GUIDE.md).
**WordPress.org listing:** see [readme.txt](readme.txt).

---

## Requirements

- WordPress 6.4+ (uses `%i` in `$wpdb->prepare()` and `wp_admin_notice()`)
- PHP 7.4+
- MySQL / MariaDB (uses `GET_LOCK()` to serialise duplicate checks)

## Project layout

```
codereyes-imei-ledger.php           Bootstrap: constants, requires, hooks
uninstall.php                       Keeps data unless IMEI_LEDGER_DELETE_DATA is true
includes/
  class-imei-ledger-*.php           Pure rules (no WordPress needed): Digits, Imei,
                                    Money, Validator, Duplicates, Query, Csv, Status
  class-imei-ledger-installer.php   Table (dbDelta) and capability
  class-imei-ledger-repository.php  All $wpdb access
  admin/                            Menu, screens, admin-post handlers, AJAX, list table
views/                              Templates (all output escaped)
assets/                             admin.css, admin.js (no build step)
tests/unit/                         PHPUnit tests for the pure rules
docs/                               User guide and screenshots
docs/brand/                         Icon and banner sources (icon.svg, banner.html, render.sh)
.wordpress-org/                     WordPress.org icon, banner and screenshots (SVN assets/)
```

To change the icon or banner, edit the files in `docs/brand/` and run `docs/brand/render.sh`. It uses headless Chrome and the Inter, JetBrains Mono and Noto Sans Bengali fonts to rewrite `icon-128x128.png`, `icon-256x256.png`, `icon.svg`, `banner-772x250.png` and `banner-1544x500.png` in `.wordpress-org/`.

Business rules live in the pure classes so they can be tested without WordPress or a database. The repository is the only class that talks to `$wpdb`; admin classes only read requests and render views.

## Development

```bash
composer install
```

```bash
composer test
```

Coding standards (WordPress ruleset; PHPCS with WPCS installed globally):

```bash
phpcs --standard=WordPress --extensions=php --ignore=vendor/*,tests/* .
```

## Releasing

1. **Bump the version** in three places:
   - `codereyes-imei-ledger.php`: the `Version:` header and `IMEI_LEDGER_VERSION`
   - `readme.txt`: `Stable tag:` plus a `== Changelog ==` entry (and `== Upgrade Notice ==` if needed)
   - If the table schema changed: `Imei_Ledger_Installer::DB_VERSION` and the `CREATE TABLE` SQL. `dbDelta` runs automatically when the stored version differs.
2. **Check:** tests, PHPCS, then Plugin Check on the plugin.

   ```bash
   wp plugin check codereyes-imei-ledger --exclude-directories=tests,vendor,docs --ignore-codes=hidden_files,application_detected,unexpected_markdown_file
   ```

3. **Build the zip.** `wp dist-archive` honours `.distignore`, so tests, vendor, docs, `.wordpress-org` and Markdown files are left out.

   ```bash
   wp dist-archive wp-content/plugins/codereyes-imei-ledger
   ```

4. **Commit, tag and push.** Pushing the tag starts the deploy (see below).

   ```bash
   git tag -a v1.0.1 -m "Codereyes IMEI Ledger 1.0.1"
   ```

   ```bash
   git push origin master v1.0.1
   ```

### Automated deploys (GitHub Actions)

| Workflow | Runs when | What it does |
|---|---|---|
| `.github/workflows/deploy.yml` | A `v*.*.*` tag is pushed | Checks the tag matches the plugin header, `IMEI_LEDGER_VERSION`, `Stable tag` and has a changelog entry. Runs PHPUnit on PHP 7.4 and 8.3 and Plugin Check on the shipped files. If all pass, commits to SVN `trunk/` and `tags/<version>/`, syncs `.wordpress-org/` to `assets/`, and attaches the zip to a GitHub release. |
| `.github/workflows/update-assets.yml` | `readme.txt` or `.wordpress-org/` changes on `master` (or run by hand) | Pushes only the readme and screenshots/banners/icons to WordPress.org, without a release. |

One-time setup, after WordPress.org approves the plugin and creates the SVN repository:

1. On WordPress.org, open **Edit Profile → Account & Security** and generate an SVN password.
2. Add two repository secrets (**Settings → Secrets and variables → Actions**), or from the terminal:

   ```bash
   gh secret set SVN_USERNAME -R shahadul878/codereyes-imei-ledger
   ```

   ```bash
   gh secret set SVN_PASSWORD -R shahadul878/codereyes-imei-ledger
   ```

3. Optional: under **Settings → Environments → wordpress.org**, add yourself as a required reviewer so every deploy waits for your approval.

Both workflows fail until the SVN repository exists, so don't push a release tag before approval.

## Security notes

- Every state-changing request checks the `manage_imei_ledger` capability and a nonce (per record for delete and cancel-sale).
- All SQL goes through `$wpdb->prepare()`. Column names and sort order come from whitelists in `Imei_Ledger_Query`.
- CSV cells starting with `= + - @` are prefixed with `'`. Digit columns get a leading tab so spreadsheets keep them as text.
- The list page posts its export filters, so searched names, phones and NIDs do not end up in URLs.

## License

GPL-2.0-or-later.

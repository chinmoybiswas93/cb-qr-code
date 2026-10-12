# 8 — Uninstall cleanup

| Tier | Depends on | Size |
|---|---|---|
| Must-have | — (extend as later specs add data) | XS |

## Goal

When the plugin is **deleted** (not just deactivated), remove everything it stored.

## Why

- Issue #8: today `cbqrcode_settings` and `cbqrcode_post_types` stay in `wp_options` forever.
- WordPress.org reviewers expect uninstall cleanup.
- Kaya and Composer both ship an `uninstall.php`.

## How competitors do it

- **Kaya and Composer:** an `uninstall.php` with the `WP_UNINSTALL_PLUGIN` guard that calls
  `delete_option()`.
- **Dynamic QR Code:** also drops its custom tables.

## Plan

1. Add `uninstall.php` with the `WP_UNINSTALL_PLUGIN` guard. It deletes:
   - both options
   - post meta `_cbqrcode_*` (once spec 5 exists)
   - the cache: transients and/or the uploads folder (once spec 9 exists)
   - the dynamic QR posts and their meta (once spec 10 exists)
2. Keep the list in one place in that file. **Every later spec that adds stored data adds its
   cleanup line here in the same PR.**
3. Multisite: loop over the sites with `get_sites()` and `switch_to_blog()`.
4. Optional: a "Keep data on uninstall" setting, for people who reinstall. Only add it if dynamic
   QR codes (10) exist, since losing those printed codes would hurt.

## Contracts added

None. This removes contracts' data.

## Test

- Standard steps from the [README](README.md).
- Save settings, then delete the plugin from Plugins → Delete. Check that `wp_options` has no
  `cbqrcode_%` rows left. Use cb-test's socket `aRpCXvFUz` for the query (see the site
  `CLAUDE.md`).
- Deactivate only: the data is still there.
- **Test on a copy.** Deleting the plugin from wp-admin removes the working tree's files. Do it on a
  zip-installed copy, or back up the plugin folder first.

## Done when

- A delete leaves no options, meta, files or posts behind.
- Issue #8 is closed.

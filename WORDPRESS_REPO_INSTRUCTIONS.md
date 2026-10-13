# Releasing to WordPress.org

How a new version of CB QR Code gets from `main` to
[wordpress.org/plugins/cb-qr-code](https://wordpress.org/plugins/cb-qr-code/). Last used for 1.0.1
(2026-10-13, SVN r3744468).

The plugin is already approved, so releases are SVN commits — the zip upload form is only for a
first submission. Checks to run along the way are in
[`context/pre-release-checks.md`](context/pre-release-checks.md).

| Where | What |
|---|---|
| SVN | `https://plugins.svn.wordpress.org/cb-qr-code` — `trunk/`, `tags/<version>/`, `assets/` |
| Download | `https://downloads.wordpress.org/plugin/cb-qr-code.<version>.zip` |
| API | `https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=cb-qr-code` |
| GitHub | tag `v<version>` + a release with the same zip attached |

## 1. Prepare the version (one PR)

- Version in five places: plugin header `Version`, `CBQRCODE_PLUGIN_VERSION`, readme `Stable tag`,
  the `app/Views/admin/about.php` fallback, the `.pot` header.
- `readme.txt`: a changelog entry and an upgrade notice; bump `Tested up to` if a newer WordPress
  was tested.
- Regenerate the `.pot`:
  `wp i18n make-pot . languages/cb-qr-code.pot --slug=cb-qr-code --domain=cb-qr-code --exclude=vendor,tests,context,.claude`
  (run through Local's PHP with cb-test's socket — see `agent.md` → site traps).

## 2. Make sure `main` has it

Stacked PRs merge into their base branch, not `main`. Before going further:

```bash
git checkout main && git pull --ff-only
grep -E '^Version|PLUGIN_VERSION' cb-qr-code.php; grep 'Stable tag' readme.txt
```

## 3. Build the zip from `main`

Build from `git archive` so untracked local files can't slip in. Never unzip or install it over
this working tree — WordPress deletes the plugin folder before unpacking.

```bash
VERSION=1.0.1
WORK="$(mktemp -d)"
mkdir "$WORK/src" "$WORK/build"
git archive main | tar -x -C "$WORK/src"
rsync -a --exclude-from=.distignore "$WORK/src/" "$WORK/build/cb-qr-code/"
(cd "$WORK/build" && zip -rqX "../cb-qr-code-$VERSION.zip" cb-qr-code)

unzip -Z1 "$WORK/cb-qr-code-$VERSION.zip" | grep -E '/(tests|context|\.claude)/|\.md$|/\.git'   # expect nothing
shasum -a 256 "$WORK/cb-qr-code-$VERSION.zip"
```

Run Plugin Check on the extracted build (pre-release check 5) before publishing.

## 4. Tag and GitHub release

```bash
git tag -a "v$VERSION" -m "CB QR Code $VERSION" main
git push origin "v$VERSION"
gh release create "v$VERSION" "$WORK/cb-qr-code-$VERSION.zip" --title "$VERSION" --notes-file notes.md --latest
```

Notes follow the earlier releases: the readme changelog entry, a short "What's Changed" list of
PRs, and the zip's SHA-256.

## 5. SVN: stage trunk and the tag

```bash
svn co --depth immediates https://plugins.svn.wordpress.org/cb-qr-code "$WORK/svn"
cd "$WORK/svn"
svn up --set-depth infinity trunk
svn up --set-depth immediates tags assets

# trunk must still be the previous release — if not, someone committed to SVN directly; stop and look.
gh release download "v<previous>" -p '*.zip' -D "$WORK/prev" && unzip -q "$WORK/prev/"*.zip -d "$WORK/prev"
diff -rq --exclude=.svn trunk "$WORK/prev/cb-qr-code"

rsync -a --delete --exclude=.svn "$WORK/build/cb-qr-code/" trunk/
svn status trunk | awk '$1=="?"{print $2}' | xargs -I{} svn add -q --parents "{}"
svn status trunk | awk '$1=="!"{print $2}' | xargs -I{} svn rm -q "{}"
svn cp trunk "tags/$VERSION"

svn status | awk '{print $1}' | sort | uniq -c        # sanity-check the A/M/D counts
diff -rq --exclude=.svn "tags/$VERSION" "$WORK/build/cb-qr-code"   # expect nothing
```

`assets/` (banners, icon, screenshots) is untouched by a release.

## 6. Commit — maintainer only

The commit ships the version to every site running the plugin and needs the maintainer's
WordPress.org password, so the maintainer runs it in their own terminal:

```bash
svn ci "$WORK/svn" -m "Release $VERSION" --username <wordpress.org-username>
```

## 7. Confirm it's live

The API picked up 1.0.1 within a minute.

```bash
curl -s 'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=cb-qr-code' \
  | python3 -c 'import sys,json; d=json.load(sys.stdin); print(d["version"], d["requires_php"], d["tested"])'
curl -sL -o live.zip "https://downloads.wordpress.org/plugin/cb-qr-code.$VERSION.zip"
unzip -q live.zip -d live && diff -rq live/cb-qr-code "$WORK/build/cb-qr-code"   # expect nothing
```

Then update `context/progress-tracker.md` (phase, release date, SVN revision).

## Other SVN changes

- **Readme only** (e.g. `Tested up to` after a WordPress release): edit `readme.txt` in `trunk/`
  and in `tags/<stable>/`, commit both. No version bump.
- **Banners, icon, screenshots:** the SVN `assets/` folder, not the plugin. Names that work:
  `banner-772x250.png`, `banner-1544x500.png`, `icon.svg` (or `icon-128x128.png` /
  `icon-256x256.png`), `screenshot-N.png` matching the `== Screenshots ==` captions in the readme.
- **Rolling back:** set `Stable tag` in `trunk/readme.txt` to the previous tag and commit. Don't
  delete or edit an existing tag.

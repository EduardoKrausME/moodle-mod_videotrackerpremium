# Changelog

This changelog documents **only the two most recent published GitHub releases** of `mod_videotrackerpremium`. Each entry is based on the verified Git diff between its release tag and the immediately preceding **published release** tag. Unreleased and post-release commits are excluded.

## [1.0.8] - 2026-10-23

Published: 2026-10-23 23:50:45 UTC.

Comparison: [1.0.7...1.0.8](https://github.com/EduardoKrausME/moodle-mod_videotrackerpremium/compare/1.0.7...1.0.8)

### Changed: compiled AMD JavaScript

- Replaced the expanded, formatted JavaScript in `amd/build/player.min.js` with a compact, minified AMD module. The module still exposes `create`, dynamically loads the configured Video Bridge adapter, checks that the provider implements `create`, resolves the adapter, and propagates loading or creation errors.
- Replaced the expanded, formatted JavaScript in `amd/build/view.min.js` with a compact, minified AMD module. It still invokes the player adapter and delegates progress tracking to `local_video_bridge/progress`; on an adapter error, it applies the existing player-error UI treatment.
- The published diff shows minification/formatting of the built modules. **No intentional runtime behavior change is identifiable from these differences.** The corresponding AMD source files were not modified in this comparison.

### Release metadata

- Updated `version.php` from numeric plugin version `2026101900` / release `1.0.7` to `2026102300` / release `1.0.8`.
- The minimum required Moodle build remains `2024100700`, and the declared required `local_video_bridge` version remains `2026100618`.

### Scope

The published-tag comparison contains two commits and changes only `amd/build/player.min.js`, `amd/build/view.min.js`, and `version.php`. No new player adapters, viewing policies, progress calculations, activity screens, or other functional source changes are evidenced by the release diff.

## [1.0.7] - 2026-10-19

Published: 2026-10-19 14:58:04 UTC.

Comparison: [1.0.6...1.0.7](https://github.com/EduardoKrausME/moodle-mod_videotrackerpremium/compare/1.0.6...1.0.7)

### Documentation: Video Bridge installation requirements

- Expanded `README.md` with a **Requirements and installation** section clarifying that `local_video_bridge` version `2026100618` or later is required and must be installed separately at `local/video_bridge` before installing or updating Video Tracker Premium.
- Documented that Video Bridge requires Moodle 4.5 or later and currently depends on `local_ai_bridge`; installation instructions direct administrators to install its dependencies and complete the upgrade through Moodle's Site administration > Notifications.
- Clarified that deployments limited to Marketplace-available plugins must ensure the required companion plugins are available, because Video Tracker Premium cannot function without Video Bridge.
- The required `local_video_bridge` dependency was **already declared** in `version.php` at release `1.0.6`; this release makes the existing requirement explicit in documentation rather than introducing a new dependency.

### Release workflow

- Added a GitHub Actions `concurrency` block to `.github/workflows/release.yml`, with group `release-${{ github.ref }}`, `cancel-in-progress: false` and `queue: max`.

### Release metadata

- Updated `version.php` from numeric plugin version `2026101500` / release `1.0.6` to `2026101900` / release `1.0.7`.
- The minimum required Moodle build remained `2024100700`, and the declared `local_video_bridge` dependency remained `2026100618`.

### Scope

The comparison contains three commits and modifies **only** `.github/workflows/release.yml`, `README.md`, and `version.php`. It does not modify playback, reminders, operational reporting, completion, or other functional PHP/JavaScript logic.

---

Entries reflect only verified changes between published release tags. Writing this changelog did not involve running tests, installing plugins, editing release descriptions, or modifying source files.

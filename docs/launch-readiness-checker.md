# Launch Readiness Checker

## Goal

- Build a free launch-readiness audit tool at `/tools/launch-readiness-checker`.
- Keep the UI clean, simple, and visually close to the provided reference screenshot.
- Let visitors run scans without signing in.
- Offer separate tool-user auth that feels like the current project while keeping tool users in separate database tables.

## Current scope

- Full MVP with real server-side checks.
- Public saved history of scans.
- Optional save toggle on each scan, enabled by default.
- Daily limit of 20 scans per actor.
- Shared Tailwind, shared `.env`, shared Laravel app.

## Tool auth approach

- Separate `tool_users` table.
- Separate `tool_auth_magic_links` table.
- Separate session auth guard/provider for tool users.
- Reuse the current project style of Google/email sign-in flow as closely as practical.

## MVP audit coverage

- Meta information
- Content structure
- Technical optimization
- Accessibility basics
- Social and rich results
- Links analysis
- AI and launch signals

## Notes

- Saved scans are public in the history view.
- Unsaved scans can still be viewed immediately through their private result URL.
- The tool should ship with OG placeholder metadata and JSON-LD placeholders for its own pages.

## Progress log

- 2026-05-31: Started integrated implementation inside the main Laravel app.
- 2026-06-03: Added signed-in workspace foundations:
  - shared launch-readiness page-data support
  - tool-user admin email support via config
  - authenticated dashboard and settings routes
  - shared light app-shell design tokens
  - avatar dropdown menu with dashboard/settings/logout
- 2026-06-03: Added admin-managed tool branding:
  - admin-only branding form on the dashboard
  - configurable tool logo, site name, favicon, and Google Font link
  - configurable tool font size, text color, and background color
  - shared branding service backed by `storage/app/settings.json`
  - branding applied across public tool pages and the signed-in workspace

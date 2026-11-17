# MIS Logbook reference UI

Status: DONE

## Executive Snapshot

Current state: The public form/history, filtered CSV/XLSX export, and persistent time-only editing are implemented and verified while keeping the no-admin decision.

Current phase: Complete.

## Objective

Complete the public history workflow with filtered CSV/XLSX downloads, persistent time-only editing, and responsive controls/dialogs using the existing PHP/MySQL flow.

## Acceptance Criteria

- Desktop and mobile screenshots compared at 1440 px and 390 px.
- The guest submits date, identity, purpose, entry time, and exit time once when leaving; the database stores one complete visit row.
- Public history filters, filtered CSV/XLSX export, persistent time editing, and QR form flow work; there is no admin area.
- Dashboard counts today's visits, complete visits, and legacy incomplete rows from the database.
- No login or signature feature is added.

## Execution Board

| Phase | Status | Evidence |
| --- | --- | --- |
| Reference and local audit | DONE | Browser screenshots and accessibility snapshots captured for reference guest/history, detail dialog, settings, mobile, and local guest/history. |
| Implementation | DONE | Single guest form and complete-row insert; public history header, summary, search, status/date filters, time table, detail dialogs, and pagination. |
| Verification | DONE | Desktop and mobile history screenshots and dialogs inspected; public form/history requests and retired routes tested. |
| History export and time editing | DONE | Filtered CSV/XLSX opened successfully; time edits persisted in MySQL, survived reload, entered XLSX, supported empty checkout, and rejected reversed times. |
| Deployment verification | SKIPPED | Production deployment was outside this implementation request; `/admin` remains absent by user decision. |

## Decisions

- The user's latest instruction supersedes the earlier code-based checkout: guests fill the form once while leaving. The old code flow and tabs were removed.
- Keep company optional and omit reference-only contact, cloud integration, and staff presets because they are outside the requested data model.
- Preserve legacy incomplete rows and let admins complete them through Edit; no schema migration is needed because `check_out_time` is already present and `visit_code_hash` permits NULL.
- Keep visitor history read-only at `/history`. Reference cloud sync, browser-local history, CSV, settings management, and admin operations do not fit the requested flow.
- Revision 2026-11-17: the user explicitly requested CSV/XLSX export and time-only editing on `/history`; history is no longer read-only, but no admin area is added. “Sinkronkan Data” reloads the current MySQL-backed view because the project has no external Sheets integration.

## Verification

- `php -l` passed for changed PHP files.
- Local revised smoke script in ignored `.runtime/smoke-single-form.py` passed and removed its test visits afterward.
- Reversed entry/exit times returned HTTP 400 without a row. Valid submission saved both times and server recording timestamps in one row; public history summary and filters passed.
- `/history` returned HTTP 200 and `/admin/` returned HTTP 404 because the admin area was removed.
- Browser screenshots were inspected directly; the browser tool refused saving them inside the workspace, so no screenshot artifacts were retained.
- `smoke-history-edit-export.py` passed CSV/XLSX, persistence, post-edit export, validation-error, and incomplete-visit checks; its synthetic row was removed in `finally`.
- Browser checks at 1440×900, 768×1024, and 390×844 confirmed responsive controls, reachable sticky Aksi, usable edit dialog/buttons, and no page-level horizontal scroll.

## NEXT ACTION

None — ticket complete.

# History full visit editing

Status: DONE
Plan Depth: STANDARD
Current Phase: Complete
Last Updated: 2026-11-17

## Executive Snapshot

The history dialog now edits every mutable visit field through the existing CSRF-protected MySQL flow while keeping ID and derived status informational. Persistence, validation, export, desktop, and mobile checks passed.

## Objective

Allow users to edit visit date, visitor name, company, person met, purpose, check-in time, and optional check-out time from the existing detail dialog.

## Acceptance Criteria

- All mutable fields can be edited and persist after reload.
- ID stays immutable and status follows whether check-out time is present.
- Existing validation, filters, CSV/XLSX export, CSRF protection, and responsive dialog behavior continue to work.
- Invalid input displays an error without overwriting the stored visit.

## Scope

In scope: `/history` POST handling, detail-dialog markup/behavior, responsive styles, targeted persistence/export tests.

Out of scope: administrator area, authentication, schema changes, deletion, or editing the visit ID directly.

## Architecture / Approach

Replace the time-only form with one full-visit form in each existing native dialog. Validate on the server and update the same `visits` row with prepared SQL. Derive status from the optional check-out field and reopen the same dialog after redirect.

## Execution Board

| Phase | Status | Goal | Evidence / Result |
| --- | --- | --- | --- |
| Phase 1 — Implementation | DONE | Add full-field form and update handler. | Prepared update covers date, identity, company, person, purpose, and both times; invalid values return to the same dialog. |
| Phase 2 — Verification | DONE | Verify persistence, validation, export, and responsive UI. | Isolated smoke test passed reload/export/status/error cases; desktop 1440 px and mobile 390 px dialogs were inspected. |

## Decisions

- ID is immutable because it is the database primary key.
- Status remains derived: empty check-out means `Belum lengkap`; a valid check-out means `Lengkap`.
- Company remains optional; all other descriptive fields follow the existing form limits.

## NEXT ACTION

None — ticket complete.

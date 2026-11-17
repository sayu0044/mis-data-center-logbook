# History direct inline update

Status: DONE
Plan Depth: STANDARD
Current Phase: Complete
Last Updated: 2026-11-17

## Executive Snapshot

The detail dialog already contains a complete edit form, but opening a row first shows a read-only view and saving reloads the page. Change the arrow to open edit mode immediately and save with JSON so only the selected row is updated in place.

## Objective

Make one-click row editing update the chosen visit immediately without reloading, while preserving database persistence, validation, derived status, and export data.

## Acceptance Criteria

- Clicking a row arrow immediately opens its populated edit form.
- Save updates only that visit and refreshes its table row without navigation.
- Failed saves keep entered values and display an error.
- Cancel resets unsaved changes and closes the dialog.
- Two-row isolation, reload persistence, export output, desktop, tablet, and mobile behavior are verified.

## Architecture / Approach

Keep the prepared MySQL update and CSRF validation. Add an application/json response for fetch requests, add stable data attributes to each row, and update the selected row from the returned canonical visit data. Non-JavaScript POST/redirect remains as fallback.

## Execution Board

| Phase | Status | Goal | Evidence / Result |
| --- | --- | --- | --- |
| Phase 1 — Implementation | DONE | Add direct edit opening and JSON save/update. | Arrow opens the populated full form; fetch returns canonical JSON and updates only the matching table row. |
| Phase 2 — Verification | DONE | Test two-row isolation, cancel, persistence, export, and responsive UI. | Two-row browser test passed, reload persisted the edit, Cancel restored values, CSV/XLSX smoke tests passed, and 1440/768/390 px layouts were checked. |

## Decisions

- Status remains derived from whether Jam Keluar is populated.
- ID remains immutable and identifies the update target.
- Successful fetch saves close the dialog and show a page-level success message.

## Verification Evidence

- PHP lint passed for `public/index.php` and `app/history.php`; JavaScript syntax check passed for `public/assets/app.js`.
- Isolated smoke test confirmed direct editor markup, CSV/XLSX export, row isolation, database persistence, validation errors, and optional checkout.
- Browser test edited the first of two temporary visits (name and checkout), observed an immediate row update without navigation, reloaded to confirm persistence, and confirmed the second row stayed unchanged.
- Browser test changed the second visit then pressed Batal; the dialog closed and both the row and reset form retained the stored value.
- At 768 px and 390 px, the page had no horizontal document overflow, the form had no internal horizontal overflow, and Batal/Simpan/Tutup remained reachable.

## NEXT ACTION

None.

# Tailwind migration

Status: DONE
Plan Depth: STANDARD
Current Phase: Complete
Last Updated: 2026-11-17

## Executive Snapshot

Move the PHP pages to a compiled Tailwind CSS asset while retaining the established visual design and responsive behavior. The current stylesheet has about 495 lines of bespoke component rules.

## Objective

Install a reproducible Tailwind build, use its utilities for shared styling, and serve a committed generated CSS asset so PHP deployment does not require Node at runtime.

## Acceptance Criteria

- Tailwind is installed and its build is documented.
- Pages load the generated stylesheet and preserve their appearance.
- Guest form, history table, and edit dialog remain usable at desktop and phone sizes.

## Execution Board

| Phase | Status | Goal | Evidence / Result |
| --- | --- | --- | --- |
| Implementation | DONE | Integrate Tailwind build and migrate shared rules. | Added Tailwind CLI, source/build files, and utility based shared rules; PHP serves compiled asset. |
| Verification | DONE | Build and inspect PHP pages and responsive behavior. | Build and PHP lint passed; `/`, `/history`, and asset returned 200; mobile dialog controls and overflow checked. |

## NEXT ACTION

None — ticket complete.

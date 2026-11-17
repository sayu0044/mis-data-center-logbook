# MIS Data Center Logbook

- Native PHP 8.2+, PDO, MySQL Community Server 8.4; UI uses Indonesian and timezone Asia/Jakarta.
- Keep web-accessible files in `public/`; keep configuration and application code outside the document root.
- Use prepared SQL, escaped HTML, and CSRF tokens for mutations.
- Do not add administrator login or session authentication. Protect `/admin` with an explicit internal-network IP allowlist and server-level network policy; deny access when no trusted network is configured.
- Store secrets in environment variables; never commit real credentials.
- Keep guest and admin operations in the same visit table. Guests fill one form when leaving, including both entry and exit times; do not add separate check-in/check-out steps or visit codes.
- Project request came from the supplied MIS Data Center Logbook master prompt. `RTK.md` and `TOOL_PREFERENCES.md` were not present in the workspace.

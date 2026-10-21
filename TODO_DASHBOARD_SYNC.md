# TODO - Sinkronisasi Statistik Dashboard dengan Data Tabel

## Steps

- [x] 1. Update `resources/views/dashboard.blade.php` — Replace hardcoded `$stats` with dynamic values computed from `$activities` collection, add `data-stat` attributes (no UI changes).
- [x] 2. Update `public/js/dashboard.js` — Add `updateStats()` to recompute stat cards from visible rows; call it in `filterTable()` and on initial load.
- [x] 3. Verify blade syntax via `php artisan view:cache` and manual search/filter testing in browser.
- [x] 4. Clear compiled view cache via `php artisan view:clear`.

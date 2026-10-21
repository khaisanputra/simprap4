# TODO - Pindahkan Statistik Dashboard ke Database (MVC Laravel)

## Steps

- [x] 1. Create migration `create_attendances_table`.
- [x] 2. Create `App\Models\Attendance`.
- [x] 3. Create `App\Http\Controllers\DashboardController` with Eloquent stat queries.
- [x] 4. Update `routes/web.php` to use `DashboardController`.
- [x] 5. Update `resources/views/dashboard.blade.php` — remove PHP stat logic, render from controller variables (no UI changes).
- [x] 6. Create `AttendanceSeeder` demo data + register in `DatabaseSeeder`.
- [x] 7. Run `php artisan migrate` + `php artisan db:seed --class=AttendanceSeeder`.
- [x] 8. Remove temporary `check_tables.php`.
- [x] 9. Verify via `php artisan serve`.

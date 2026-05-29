# tests/ — PHPUnit test suites

## Purpose
Automated tests, configured in `phpunit.xml` with two suites: `Unit` (pure PHPUnit) and `Feature` (boots the Laravel app, makes HTTP calls). The test env uses in-memory SQLite, array cache/session, sync queue. ⚠️ Only scaffold tests exist today — effectively no coverage.

## Key files
- `Unit/ExampleTest.php` — template for pure unit tests; extends `PHPUnit\Framework\TestCase` (no app/DB).
- `Feature/ExampleTest.php` — template for integration tests; extends `Tests\TestCase`, uses `$this->get('/')` + `$response->assertStatus(200)`. The `RefreshDatabase` trait is imported-but-commented; enable it for DB tests.
- `TestCase.php` — Laravel base test case (boots the framework).
- `CreatesApplication.php` — bootstraps the app for tests.
- ⚠️ `tests/routes/` — stray copies of route files; not real tests.

## Data flow
`artisan test` → PHPUnit reads `phpunit.xml` → runs `Unit/*` then `Feature/*` → Feature tests spin up the app against `:memory:` SQLite and assert on HTTP responses.

## Dependencies
- **Depends on:** `phpunit.xml` (env overrides), `app/` code under test, in-memory SQLite (migrations run per test if `RefreshDatabase` is on).
- **Depended on by:** nothing (no CI pipeline is configured).

## Conventions
- Unit → `extends PHPUnit\Framework\TestCase`; Feature → `extends Tests\TestCase`.
- Methods are `test_snake_case(): void`. See `docs/PATTERNS.md` §7.
- New DB-touching Feature tests should `use Illuminate\Foundation\Testing\RefreshDatabase;`.

## Common commands
```
C:\php83\php.exe -c "C:\php83\php.ini" artisan test
C:\php83\php.exe -c "C:\php83\php.ini" artisan test --filter=ExampleTest
```

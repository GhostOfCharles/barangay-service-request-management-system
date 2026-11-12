# Development guidance

## Source of truth

- Treat `documents/COMMUNITY_SERVICE_REQUEST_MANAGEMENT_SYSTEM_REVISED - Copy.docx` (the revised planning document) as the requirements and wireframe reference. Its 14 functional requirements (FR-01 to FR-14), 15 wireframe screens, 13 flowcharts, navigation maps, and nine-table database design (section 10.1) define the scope. The README's **Implementation decisions** table records how details the document left open were settled.
- The system is a web app for Barangay San Rafael. It is a student project, not an officially adopted government system.
- Preserve the documented boundary: three roles (Administrator, Staff, User/Resident); residents register themselves and stay inactive until Staff or the Administrator verify their valid ID; one service-request workflow covers certificates/clearances, complaints, and assistance/referrals as service types.

## Required workflow direction

- One status workflow for every request: Submitted > Under Review > Approved > Cash Payment > Completed, one step at a time. Staff may set statuses up to Cash Payment; only the Administrator sets Completed. Services with a ₱0.00 fee skip Cash Payment. Every change is written to `request_logs`. These rules live in `App\Enums\RequestStatus` and `ServiceRequest::canMoveTo()`; reuse them instead of re-implementing them.
- A request cannot be submitted until every required document on its service type's checklist is attached (JPG, PNG, or PDF, up to 5 MB each). Validation is shared in `App\Http\Controllers\Concerns\ValidatesRequestSubmission`.
- Fulfillment is offline: residents pay cash at the barangay hall on receipt, and the Administrator marks the request Completed. No online payments, SMS, email notifications, or live chat; residents see status in their dashboard, and concerns go through asynchronous tickets (Open, Answered, Closed).
- Residents see and manage only their own requests and tickets. Uploaded IDs and documents stay on the private `local` disk and are served only through `PrivateFileController` with the gates in `AppServiceProvider`.
- Use the document's wireframes and navigation maps for layout and visible actions. Match documented fields and labels; do not infer additional business rules from mockups.
- Keep paper records and official signatures where required. Do not add out-of-scope features (digital signatures, online payments, SMS/email notifications, live chat, advanced analytics, accounting/payroll, unrelated municipal systems) without updated requirements.

## Technical direction

- Use the existing Laravel MVC structure: routes, controllers, Eloquent models, migrations, and Blade views. Keep the implementation simple and appropriate to the project's beginner-level Laravel context.
- The stack is PHP 8.3+, Laravel 13, Blade, HTML/CSS/Bootstrap 5 (bundled with Vite), and MySQL. Tailwind CSS was removed to match the planning document; do not reintroduce it.
- Status, role, account, verification, ticket, and category values live in `app/Enums`; reuse them instead of hard-coding strings. The purok list and upload limits are in `config/barangay.php`.
- Route groups use `role:resident` (resident portal under `/my`) and `role:personnel` (Staff and Administrator); Administrator-only actions use the `administer` gate.
- Pages must stay usable on phones (about 375px wide) as well as office monitors. Follow the existing patterns: the `offcanvas-lg` sidebar, Bootstrap `d-none d-md-table-cell` to hide secondary table columns with a `.cell-sub` line under the main cell, `.page-actions` for header buttons, `.filter-bar` for filters, `modal-fullscreen-sm-down` for form modals, `data-href` on table rows, and the `<x-status-badge>`, `<x-status-tracker>`, `<x-peso>`, and `<x-submission-fields>` components.
- `.env.example` selects MySQL. Local setup requires a running MySQL server and a database created by the developer; `composer run setup` runs migrations but does not create the database.
- Keep credentials and local `.env` values out of tracked changes.

## Unclear requirements

The puroks, the starting service-type checklists, the password-reset flow, and the no-delete rule are decided in the README's **Implementation decisions** table. Follow it, and update it together with the code if the team changes a decision. Do not invent an audit/retention policy, deployment model, or production availability guarantees. The planning document explicitly excludes guaranteed operation during internet outages.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>

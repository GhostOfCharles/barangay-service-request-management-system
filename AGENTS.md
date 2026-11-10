# Development guidance

## Source of truth

- Treat `documents/COMMUNITY SERVICE REQUEST MANAGEMENT SYSTEM.docx` as the requirements and wireframe reference. It contains interface wireframes; follow them to guide frontend layout and documented workflows. The README summarizes its planned MVP; neither document implies a feature is already implemented.
- The system is an internal web app for Barangay San Rafael. It is a student project, not an officially adopted government system.
- Preserve the documented boundary: authenticated Administrator and Staff roles; residents are requesters without accounts; one service-request workflow covers certificates/clearances, complaints, and assistance/referrals.

## Required workflow direction

- Keep the core records connected: reusable resident, service request, current status, and request follow-up history.
- Support request intake, status updates, follow-up logging, search/filtering, dashboard monitoring, and basic reports.
- Use the document’s login, dashboard, residents, service request, report, logout wireframes and navigation plan to guide frontend layout and functionality. Match documented fields and visible actions; do not infer additional business rules from mockups.
- Restrict user-management controls to Administrators. Protect resident information with authentication and role-based access.
- Keep paper records/signatures where required. Do not add out-of-scope features such as resident accounts/public portal, digital signatures, online payments, SMS/email notifications, advanced analytics, accounting/payroll, or unrelated municipal systems without updated requirements.

## Technical direction

- Use the existing Laravel MVC structure: routes, controllers, Eloquent models, migrations, and Blade views. Keep the implementation simple and appropriate to the project’s beginner-level Laravel context.
- The target stack is PHP 8.3+, Laravel 13, Blade, HTML/CSS/Bootstrap, and MySQL. Current package configuration also includes Vite and Tailwind CSS; align with the existing repository unless the project team resolves this stack discrepancy.
- `.env.example` selects MySQL. Local setup requires a running MySQL server and a database created by the developer; `composer run setup` runs migrations but does not create the database.
- Keep credentials and local `.env` values out of tracked changes.

## Unclear requirements

Do not invent detailed validation rules, request status transition rules, document-check semantics, reference-number format, audit/retention policy, deployment model, or production availability guarantees. Flag these for the project team when implementation depends on them. The planning document explicitly excludes guaranteed operation during internet outages.

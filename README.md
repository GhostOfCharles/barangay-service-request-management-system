# Community Service Request Management System

An internal web application planned for Barangay San Rafael to help authorized personnel manage resident records and service requests in one place. The project is based on the supplied planning and design document; it is a student-developed system and does not claim official barangay adoption or production deployment.

## Project overview

The system is intended to reduce reliance on separate paper logbooks, folders, spreadsheets, and personal notes. Its central record is a service request associated with a resident. Staff can record the request, track its status, log follow-up actions, search previous records, and use basic reports to monitor work.

The planned roles are **Administrator** and **Staff**. Residents are recorded as requesters and do not need application accounts. Certificate and clearance requests, complaints, and community assistance or referrals use the same request workflow as categories.

## Planned MVP scope

- Authenticated access with role-based permissions and administrator-only user management.
- Resident records with name, address, contact details, and purok, reusable across requests.
- Service request intake with resident, category, description, document-check information, and date received.
- Request status tracking and follow-up actions kept with the request history.
- Search and filtering for resident and request records.
- A dashboard for request counts, pending work, and recent transactions.
- Basic reports filtered by date range, category, and status, including total, pending, in-progress, and completed requests.
- Paper records and signatures remain in use wherever required.

Out of scope for the MVP: resident accounts or a public portal, digital signatures, online payments, SMS/email notification infrastructure, advanced analytics, accounting/payroll, unrelated municipal systems, specialized hardware, and guaranteed operation during internet outages. The planning document does not specify detailed validation rules, retention periods, or deployment arrangements; confirm these with the project team before implementing them as requirements.

## Wireframe and frontend guidance

Use the supplied planning document’s interface wireframes and navigation plan as the design reference for frontend and workflow changes:

1. Staff login with username and password.
2. Dashboard emphasizing pending work, request counts, recent requests, and shortcuts.
3. Searchable/filterable resident and request list.
4. Add-resident form or modal for name, address, contact, and purok.
5. Service request form or modal for resident, category, description, document-check status, and date received.
6. Reports board with date-from/date-to, category, and status filters and request totals.
7. Logout confirmation available from application boards.

Keep the navigation centered on Dashboard, Residents, Service Requests and request details, Reports, and Logout. Show user-management controls only to Administrators. Follow the wireframes’ intent and documented fields; do not add unrequested workflows or imply that a wireframe alone defines unspecified business rules.

## Tech stack

- PHP 8.3 or later and Laravel 13.
- Blade, HTML, CSS, and Bootstrap for the interface, as specified in the planning document.
- MySQL for persistent application data.
- Vite, Tailwind CSS 4, and the Laravel Vite plugin are present in the current frontend package configuration.

The repository currently contains the Laravel starter structure; the planned application features above should not be assumed to be implemented yet.

## Local setup

Prerequisites: PHP 8.3+, Composer, Node.js/npm, and a running MySQL server.

1. Clone the repository and open its project folder.
2. Create a local MySQL database.
3. Copy `.env.example` to `.env` if setup has not already created it. Set `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` for your local MySQL instance.
4. Install and initialize the application:

   ```bash
   composer run setup
   ```

   This installs PHP and JavaScript dependencies, creates the app key, runs migrations, and builds frontend assets. It does not create the MySQL database itself.

5. Start local development:

   ```bash
   composer run dev
   ```

   Open the local URL printed in the terminal. This Laravel dev command starts the web server, queue listener, and Vite. To run only the web server, use `php artisan serve` and run `npm run dev` separately when working on frontend assets.

## Project requirements

The requirements source is [`documents/COMMUNITY SERVICE REQUEST MANAGEMENT SYSTEM.docx`](documents/COMMUNITY%20SERVICE%20REQUEST%20MANAGEMENT%20SYSTEM.docx). It includes wireframes; developers should follow them as the reference for the frontend layout and documented workflows. Treat the document as the project scope. If a wireframe or requirement is unclear or conflicts with the existing application, call out the issue and ask for direction instead of inventing a requirement.

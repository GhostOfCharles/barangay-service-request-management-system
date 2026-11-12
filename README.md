# Community Service Request Management System

A web application for Barangay San Rafael that centralizes resident records and service requests, and gives verified residents a self-service portal to submit requests, upload required documents, track status, and raise issue tickets. It is a student-developed system based on the revised planning and design document; it does not claim official barangay adoption or production deployment.

## Project overview

The system replaces separate logbooks, folders, spreadsheets, and personal notes with one central record: the **service request**. Certificate/clearance, complaint, and assistance/referral concerns are handled as **service types** within one workflow.

There are three roles:

| Role | What they do |
| --- | --- |
| **Resident** | Registers an account with a valid ID, waits for verification, then submits requests with the required documents, tracks status and the amount due, and opens support tickets. Sees only his or her own records. |
| **Staff** | Verifies residents, adds walk-in residents, creates staff-assisted requests, moves requests through the workflow up to Cash Payment, adds follow-up notes, answers tickets, and views reports. |
| **Administrator** | Everything Staff can do, plus Mark Completed, service types/fees/checklists, and user accounts. |

## MVP scope (implemented)

Each functional requirement in the revised document and where it lives:

| FR | Requirement | Screens |
| --- | --- | --- |
| FR-01 | Login for three roles, role-based menus and access | Login, Logout |
| FR-02 | Requests by residents or staff-assisted, unique reference `SR-2026-0006` | New Request, Add New Service Request |
| FR-03 | Resident records with verification status | Residents, Add New Resident, resident details |
| FR-04 | One status workflow with request log; only the Administrator completes; ₱0.00 skips Cash Payment | Request Details |
| FR-05 | Follow-up notes in the request log | Request Details |
| FR-06 | Search and filter requests and residents | Service Requests, Residents |
| FR-07 | Staff dashboard: requests by status, pending verifications, open tickets | Staff Dashboard |
| FR-08 | Reports by status, service type and date range, with cash collected | Reports |
| FR-09 | Resident registration with valid ID and Data Privacy Act consent; inactive until verified | Resident Registration, Residents (Review ID) |
| FR-10 | Document checklist per service type; JPG/PNG/PDF up to 5 MB; submit only when complete | New Request, Add New Service Request |
| FR-11 | Resident dashboard with reference numbers, status tracker, amount due (no SMS/email) | My Requests |
| FR-12 | Cash on delivery at the barangay hall; Administrator marks Completed | Request Details, My Requests |
| FR-13 | Asynchronous tickets `TK-2026-0012`: Open, Answered, Closed | Support Tickets, Ticket Inbox |
| FR-14 | Administrator maintains service types, fees (₱0.00 allowed), and checklists | Service Types, Fees and Checklists |

Out of scope: online payments and payment gateways, live chat, SMS or email notifications, digital signatures, advanced analytics, accounting/payroll, unrelated municipal systems, specialized hardware, guaranteed operation during internet outages, and replacing paper records where required.

## Implementation decisions

The planning document leaves some details open. The implementation settles them as follows; update this table and the code together if the project team decides otherwise.

| Topic | Decision | Basis in the planning document |
| --- | --- | --- |
| Status workflow | Submitted → Under Review → Approved → Cash Payment → Completed, **one step at a time** (no skipping ahead or going back). Staff may set up to Cash Payment; only the Administrator uses **Mark Completed**. A ₱0.00 service goes Approved → Completed. | FR-04 and its flowchart ("Allowed next step in the workflow?"). |
| Fee on a request | The service type's fee is copied onto the request when it is submitted, so a later fee change does not alter existing requests. Amount due is that fee until the request is Completed. | FR-11, FR-12; `fee_amount` in section 10.1. |
| Document checklists | The six starting service types and fees come from the Screen 13 wireframe. Only the Barangay Clearance checklist is named in the document (Valid ID (front), Proof of Residency, Community Tax Certificate); the other checklists are sensible starting points the Administrator can edit. A checklist item can be marked optional. | FR-14, Screen 13. |
| Deactivating a service type | **Deactivate** hides it from new requests; existing requests keep it. It can be activated again. | Screen 13 ("Deactivate"). |
| Puroks | The Purok dropdown lists Purok 1 to 7, set in `config/barangay.php`. Change the list there to match the barangay. | Screens 2 and 8 ("Select purok"). |
| Residents added by Staff | A walk-in resident added on the Residents screen is recorded as **Verified** (seen in person); the valid ID upload is optional. Such residents have no portal account. | Screen 8 ("Valid ID (optional)"). |
| Rejecting a registration | **Reject** requires a reason. The resident sees it at login, and the account stays inactive. | FR-09 flowchart ("Reject with a reason; account stays inactive"). |
| Forgotten passwords | **Forgot Password?** tells the user to ask the Administrator, who issues a one-time temporary password from User Management; the user must change it at next login. There is no email reset because the system sends no email. | No reset flow is shown in the revised document; kept as the safest option without email. |
| User accounts | The Administrator creates Staff and Administrator accounts. Resident accounts come only from registration and are activated through verification. | FR-01, FR-09, section 10. |
| File storage | Valid IDs and request documents are stored on Laravel's private disk (`storage/app/private`) and opened only through authorized routes. Files are not kept after a failed submission, so the form asks the user to attach them again. | NFR Security, section 9.2 (File Storage). |
| Ticket replies | A Staff or Administrator reply sets **Answered**; a resident reply sets **Open** again; either side can **Close**. Closed tickets take no further replies. | FR-13 and its flowchart. |
| Deleting records | Residents, requests, logs and tickets cannot be deleted in the application. Staff accounts are deactivated, not deleted. | NFR Reliability ("preserve successfully stored request records and their logs"). |
| Phone use | The system is a responsive website, not a separate mobile app. | NFR Responsiveness ("smaller screens residents are likely to use"). |
| Time zone | Dates and times are recorded and shown in Philippine time (`Asia/Manila`). Set `APP_TIMEZONE` in `.env` to change it. | Barangay San Rafael is in the Philippines; request logs show times (Screen 10). |

## Screens

The 15 screens follow the revised document's wireframes:

| # | Screen | Who | Route |
| --- | --- | --- | --- |
| 1 | Login | All roles | `/login` |
| 2 | Resident Registration | New residents | `/register` |
| 3 | Resident Dashboard (My Requests) | Resident | `/my` |
| 4 | Dynamic Submission Form | Resident | `/my/requests/new` |
| 5 | Resident Support Tickets | Resident | `/my/tickets` |
| 6 | Staff Dashboard | Staff, Administrator | `/dashboard` |
| 7 | Residents List (with Verify Residency) | Staff, Administrator | `/residents` |
| 8 | Add New Resident | Staff, Administrator | modal on `/residents` |
| 9 | Service Requests | Staff, Administrator | `/service-requests` |
| 10 | Request Details | Staff, Administrator | `/service-requests/{id}` |
| 11 | Add New Service Request (Staff-Assisted) | Staff, Administrator | modal on `/service-requests` |
| 12 | Staff Ticket Inbox | Staff, Administrator | `/tickets` |
| 13 | Service Types, Fees and Checklists | Administrator | `/admin/service-types` |
| 14 | Reports | Staff, Administrator | `/reports` |
| 15 | Logout | All roles | modal from the sidebar |

User Management (`/admin/users`, Administrator) and Change Password (`/password`) are supporting screens.

## Tech stack

- PHP 8.3 or later and Laravel 13.
- Blade, HTML, CSS, JavaScript, and Bootstrap 5 (with Bootstrap Icons), bundled with Vite.
- MySQL for persistent data; Laravel's private storage disk for uploaded files. The automated tests use an in-memory SQLite database and a fake disk.
- Laravel Boost (development only) provides AI-assistant guidelines in `AGENTS.md` and `CLAUDE.md`.

### Code map

| Area | Files |
| --- | --- |
| Routes | `routes/web.php` (resident portal under `/my`, Staff and Administrator pages, `admin/` for Administrator only) |
| Controllers | `app/Http/Controllers`: `Auth/` (login, registration, password), `Resident/` (dashboard, requests, tickets), `ResidentController`, `ResidentVerificationController`, `ServiceRequestController`, `RequestStatusController`, `RequestNoteController`, `TicketInboxController`, `DashboardController`, `ReportController`, `PrivateFileController`, `Admin/ServiceTypeController`, `Admin/UserController` |
| Models | `app/Models`: `User`, `Resident`, `ServiceType`, `DocumentRequirement`, `ServiceRequest`, `RequestDocument`, `RequestLog`, `Ticket`, `TicketMessage` |
| Workflow rules | `app/Enums/RequestStatus.php` and `ServiceRequest::canMoveTo()` / `moveTo()` |
| Enums | `app/Enums`: roles, account status, verification status, request status, ticket status, log entry type, service category |
| Settings | `config/barangay.php` (puroks, upload types and size) |
| Database tables | `database/migrations`: `users`, `residents`, `service_types`, `document_requirements`, `service_requests`, `request_documents`, `request_logs`, `tickets`, `ticket_messages` |
| Views | `resources/views` (one folder per area; `layouts/app.blade.php` holds the role-based sidebar and logout modal; shared components in `components/`) |
| Page scripts | `resources/js`: document checklist (`submission-form.js`), resident search (`resident-picker.js`), checklist editor (`checklist-editor.js`) |
| Tests | `tests/Feature` |

## Local setup

Prerequisites: PHP 8.3+, Composer, Node.js/npm, and a running MySQL server (for example through Laragon).

1. Clone the repository and open its project folder.
2. Create a local MySQL database.
3. Copy `.env.example` to `.env` if setup has not already created it. Set `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` for your local MySQL instance.
4. Install and initialize the application:

   ```bash
   composer run setup
   ```

   This installs PHP and JavaScript dependencies, creates the app key, runs migrations, and builds frontend assets. It does not create the MySQL database itself.

5. Seed the first Administrator, the starting service types, and (when `APP_ENV=local`) fictional demo records:

   ```bash
   php artisan db:seed
   ```

   | Account | Username | Password | Notes |
   | --- | --- | --- | --- |
   | Administrator | `admin` | `password` | Temporary; must be changed at first login. |
   | Demo administrator (local only) | `demoadmin` | `password` | For trying **Mark Completed** and Service Types without changing the main admin password. |
   | Demo staff (local only) | `staff` | `password` | Fictional "Sec. Cruz". |
   | Demo resident (local only) | `resident` | `password` | Fictional "Cynthia Flores", verified, with requests and tickets. |
   | Pending registrations (local only) | `ramon`, `liza` | `password` | Waiting for verification; use them to try **Review ID**. |

   Change or deactivate these accounts before any real use. The demo residents, requests, and documents are fictional; the sample documents are generated PDFs.

   To start over with fresh demo data: `php artisan migrate:fresh --seed` (this erases the local database).

6. Start local development:

   ```bash
   composer run dev
   ```

   Open the local URL printed in the terminal. To run only the web server, use `php artisan serve` and run `npm run dev` separately when working on frontend assets.

7. Run the tests:

   ```bash
   composer run test
   ```

## Using it on a phone (office Wi-Fi)

Phones can open the system when they are on the same Wi-Fi network as the computer running it.

1. On the computer running the system, find its local IP address. Run `ipconfig` and look for **IPv4 Address**, for example `192.168.1.20`.
2. Build the frontend files. Don't use `npm run dev` or `composer run dev` for phone access: the Vite dev server only serves the computer itself, so phones would load pages without styling. If `public/hot` exists, stop the dev server first.

   ```bash
   npm run build
   ```

3. Start the server so other devices on the network can reach it:

   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

4. The first time, Windows may ask whether to allow PHP through the firewall. Allow it on **Private** networks only.
5. On the phone, open Chrome and go to `http://<computer IP>:8000`, for example `http://192.168.1.20:8000`.

Notes:

- This works only inside the office network and while the computer and server are running. Residents using the portal from home would need online hosting with HTTPS; the planning document lists only a local environment and "final demonstration deployment if required", so hosting is not set up here.
- Traffic on the office Wi-Fi is not encrypted (plain `http`). Because the system holds IDs and personal data covered by the Data Privacy Act, use a password-protected network.
- Sessions expire after 120 minutes of inactivity (`SESSION_LIFETIME` in `.env`). Log out after using a shared device.

## Project requirements

The requirements source is [`documents/COMMUNITY_SERVICE_REQUEST_MANAGEMENT_SYSTEM_REVISED - Copy.docx`](documents/COMMUNITY_SERVICE_REQUEST_MANAGEMENT_SYSTEM_REVISED%20-%20Copy.docx), the revised planning document with three roles. It includes wireframes, flowcharts, navigation maps, and the database design; follow them for layout and workflows. If a wireframe or requirement is unclear or conflicts with the application, call out the issue and ask for direction instead of inventing a requirement.

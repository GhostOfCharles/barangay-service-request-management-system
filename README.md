# Community Service Request Management System

An internal web application planned for Barangay San Rafael to help authorized personnel manage resident records and service requests in one place. The project is based on the supplied planning and design document; it is a student-developed system and does not claim official barangay adoption or production deployment.

## Project overview

The system is intended to reduce reliance on separate paper logbooks, folders, spreadsheets, and personal notes. Its central record is a service request associated with a resident. Staff can record the request, track its status, log follow-up actions, search previous records, and use basic reports to monitor work.

The planned roles are **Administrator** and **Staff**. Residents are recorded as requesters and do not need application accounts. Certificate and clearance requests, complaints, and community assistance or referrals use the same request workflow as categories.

## MVP scope (implemented)

- Authenticated access with role-based permissions and administrator-only user management.
- Resident records with name, address, contact details, and purok, reusable across requests.
- Service request intake with resident, category, description, document-check information, and date received.
- Request status tracking and follow-up actions kept with the request history.
- Search and filtering for resident and request records.
- A dashboard for request counts, pending work, and recent transactions.
- Basic reports filtered by date range, category, and status, including total, pending, in-progress, and completed requests.
- Paper records and signatures remain in use wherever required.

Out of scope for the MVP: resident accounts or a public portal, digital signatures, online payments, SMS/email notification infrastructure, advanced analytics, accounting/payroll, unrelated municipal systems, specialized hardware, and guaranteed operation during internet outages. The planning document does not specify retention periods or deployment arrangements; confirm these with the project team before implementing them as requirements.

## Implementation decisions

The planning document leaves some details open. The implementation settles them as follows; update this section and the code together if the project team decides otherwise.

| Topic | Decision | Basis in the planning document |
| --- | --- | --- |
| Request statuses | **Pending**, **In Progress**, **Completed**. New requests start as Pending; staff may move to any other status. Every change is saved in the request history. | FR-04, dashboard and report wireframes. The FR-04 flowchart's longer list (For Review, Ready) is marked "e.g." and is not used. |
| Service categories | **Certificate / Clearance**, **Complaint**, **Community Assistance / Referral**. | FR-02. |
| Document-check information | A required dropdown on the request: **Complete**, **Incomplete**, or **Not Required**. Staff can change it later with *Edit Details* when documents are submitted. | Screens 5–6 text ("document-check information (status)"). The Screen 6 wireframe image omits the field, so it is added below Description. |
| Reference number | `SR-YYYY-NNNN` (for example `SR-2026-0001`), assigned on save and restarting at 0001 each year. | FR-02 flowchart ("Generate Unique Reference"). The wireframe's plain `1001` numbers are replaced with a year-prefixed number that is easier to write on paper forms. |
| Choosing the resident for a new request | The Resident field is searchable: staff type part of a name, purok, or contact number and pick from the matches. If the resident is not on file, **New resident** adds them inside the same form (name, address, contact, purok), and both are saved together. | FR-03 ("reuse saved resident information"); Usability ("Simple forms"). The Screen 6 wireframe shows a plain "Select resident" dropdown, which does not scale to hundreds of residents. |
| Follow-ups and status updates | One form, **Add Follow-up / Update Status**, with a date, an optional "change status to", and a note. The note is required when the status stays the same. Each save adds **one** entry to the **Request History**: a status change (with the note attached) or a follow-up. | FR-04, FR-05 and their flowcharts; Figure 15 ("Save to Status History"). The doc lists *Update Status* and *Add Follow-up* as separate button labels. They were merged after a design review because two forms that both changed the status confused staff and produced inconsistent history entries. |
| Request details page | No wireframe exists. It shows request information, *Edit Details*, the *Add Follow-up / Update Status* form, and the request history. On phones the history appears above the form. | Navigation plan ("Request Details: status, follow-up"). |
| Login and forgotten passwords | Login uses **username** and password. *Forgot Password?* shows a "contact the Administrator" message. The Administrator resets the password and is shown a one-time temporary password, and the user must set a new password at next login. New accounts work the same way. | FR-01 flowchart. |
| User management | Administrators can add users, edit name/username/position/role, deactivate accounts, and reset passwords. Administrators cannot remove their own Administrator role or deactivate themselves. | Section 10 (Authentication and User Management). |
| Validation | Required: resident name, address, purok; request resident, category, description, document check, date received (not in the future); follow-up date, plus a note unless the status changes. Resident contact number is optional. | NFR Reliability ("validate required fields before saving"). |
| Deleting records | Residents, requests, and history entries cannot be deleted in the application, so the records stay intact. Inactive staff accounts are deactivated, not deleted. | NFR Reliability ("preserving successfully stored request records"). |
| Dashboard figures | Pending Requests, In Progress, Completed Today (completed on today's date), Total Residents, set as a ruled strip of figures (each links to the list it counts, except Completed Today), plus the five most recent requests. | Dashboard wireframe; the four pastel cards with coloured dots were recomposed in the Hallmark redesign. |
| Phone use | The system is a **responsive website**, not a separate mobile app. Staff open it in Chrome on their phone. On phones the sidebar becomes a slide-out menu (☰), tables show only the key columns, and forms open full screen. There is no app-store app, installable web app, or offline mode. | NFR Responsiveness and Usability; the planning document's stack (Blade, HTML/CSS/Bootstrap). Chosen by the project team as the lowest-cost way to allow phone access. |
| Readability | Body text is 16px and the smallest text 14px; every text colour meets WCAG AA contrast; whole table rows open their record, so phones have large tap targets. | Usability ("Readable text", "Accessibility"); NFR Usability (varying computer familiarity). |
| Visual design | The interface follows a design system built with the Hallmark skill (Almanac theme): slate paper, records set on pale "printed sheets" with ruled tables, Hanken Grotesk headings, Newsreader text, IBM Plex Mono figures and reference numbers, square corners, and the **barangay green** as the one accent and the sidebar colour. Fonts are bundled with the app, so they work without internet. Colours and fonts live only in `resources/css/tokens.css`. | Redesign requested by the project team ("trustworthy and neat, less corporate"). It keeps every field, workflow and screen from the wireframes but **no longer copies their visual look** (pastel cards, coloured dots, all-caps labels). |
| Reports | Defaults to the current month. Filters: Date From/To (on date received), Category, Status. Shows totals, a by-category breakdown, and the matching requests, and can be printed from the browser. | Reports wireframe, FR-08. |

## Wireframe and frontend guidance

Use the supplied planning document’s interface wireframes and navigation plan as the reference for **screens, fields, and workflows**. The **visual style** (colours, type, spacing) comes from the design system in `resources/css/tokens.css` and `resources/css/app.css`, not from the wireframe images; see *Visual design* in the decisions table above.

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
- Blade, HTML, CSS, and Bootstrap 5 (with Bootstrap Icons) for the interface, as specified in the planning document. Tailwind CSS from the Laravel starter was removed to match the document. Bootstrap is restyled through the design tokens in `resources/css/tokens.css`.
- Fonts (Hanken Grotesk, Newsreader, IBM Plex Mono) are downloaded at build time and served by the app itself (`vite.config.js`), so pages keep their type when the office internet is down.
- MySQL for persistent application data. The automated tests use an in-memory SQLite database.
- Vite and the Laravel Vite plugin bundle the CSS and JavaScript.
- Laravel Boost (development only) provides AI-assistant guidelines in `AGENTS.md` and `CLAUDE.md`.

### Code map

| Area | Files |
| --- | --- |
| Routes | `routes/web.php` |
| Controllers | `app/Http/Controllers` (`Auth/`, `Admin/UserController`, `ResidentController`, `ServiceRequestController`, `ServiceRequestStatusController`, `FollowUpController`, `DashboardController`, `ReportController`) |
| Models | `app/Models` (`User`, `Resident`, `ServiceRequest`, `RequestUpdate`) |
| Status, category, and role values | `app/Enums` |
| Database tables | `database/migrations` (`users`, `residents`, `service_requests`, `request_updates`) |
| Views | `resources/views` (one folder per screen; `layouts/app.blade.php` holds the sidebar and the logout modal) |
| Tests | `tests/Feature` |

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

5. Create the first Administrator account (and, when `APP_ENV=local`, fictional demo records based on the wireframes):

   ```bash
   php artisan db:seed
   ```

   | Account | Username | Temporary password | Notes |
   | --- | --- | --- | --- |
   | Administrator | `admin` | `password` | Must be changed at first login. |
   | Demo staff (local only) | `staff` | `password` | Fictional "Sec. Cruz" account for demonstrations. |

   Change or deactivate these accounts before any real use. The demo residents and requests are fictional.

6. Start local development:

   ```bash
   composer run dev
   ```

   Open the local URL printed in the terminal. This Laravel dev command starts the web server, queue listener, and Vite. To run only the web server, use `php artisan serve` and run `npm run dev` separately when working on frontend assets.

7. Run the tests:

   ```bash
   composer run test
   ```

## Using it on a phone (office Wi-Fi)

Staff phones can open the system when they are on the same Wi-Fi network as the computer running it.

1. On the computer running the system, find its local IP address. Run `ipconfig` and look for **IPv4 Address**, for example `192.168.1.20`.
2. Build the frontend files. Don't use `npm run dev` or `composer run dev` for phone access: the Vite dev server only serves the computer itself, so phones would load pages without styling. If `public/hot` exists, stop the dev server first.

   ```bash
   npm run build
   ```

3. Start the server so other devices on the network can reach it:

   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

4. The first time, Windows may ask whether to allow PHP through the firewall. Allow it on **Private** networks only. If phones still can't connect, allow port 8000 for private networks in Windows Defender Firewall.
5. On the phone, open Chrome and go to `http://<computer IP>:8000`, for example `http://192.168.1.20:8000`. Bookmark it or use **Add to Home screen** for quick access.

Notes:

- This works only inside the office network, and only while the computer is on and the server is running. Access from outside the office would need online hosting with HTTPS, which the planning document does not cover.
- Traffic on the office Wi-Fi is not encrypted (plain `http`), so use a password-protected office Wi-Fi that residents and visitors cannot join.
- Sessions expire after 120 minutes of inactivity (`SESSION_LIFETIME` in `.env`). Staff should log out after using a shared phone. If a phone is lost, an Administrator can deactivate that staff account.

## Project requirements

The requirements source is [`documents/COMMUNITY SERVICE REQUEST MANAGEMENT SYSTEM.docx`](documents/COMMUNITY%20SERVICE%20REQUEST%20MANAGEMENT%20SYSTEM.docx). It includes wireframes; developers should follow them as the reference for the frontend layout and documented workflows. Treat the document as the project scope. If a wireframe or requirement is unclear or conflicts with the existing application, call out the issue and ask for direction instead of inventing a requirement.

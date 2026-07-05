# JobBoard — Static Preview Pages

Clickable HTML mockups of every key screen in the JobBoard Laravel application.
No backend, no build step — open `index.html` in any modern browser to explore.

## How to view

You can either open the files directly in your browser, or serve the folder
over a local HTTP server (recommended so the page can use relative paths cleanly).

### Direct

Double-click `index.html`, or in PowerShell:

```powershell
Start-Process .\previews\index.html
```

### Local server (recommended)

```bash
# from the project root
php -S 127.0.0.1:8000 -t previews
# then visit http://127.0.0.1:8000/

# or with Python
python -m http.server 8000 --directory previews
# then visit http://127.0.0.1:8000/
```

## Pages

| # | Page | File | Notes |
|---|---|---|---|
| 1 | Landing / marketing | `index.html` | Hero search, featured jobs, value props, hub of all previews |
| 2 | Browse jobs | `jobs.html` | List view with category, type, contract, and salary filters |
| 3 | Job detail | `job-detail.html` | Full description, company card, related jobs, apply CTA |
| 4 | Sign in | `login.html` | Role-aware login with OAuth buttons and demo credentials |
| 5 | Register | `register.html` | Three-step onboarding: pick role → fill profile → submit |
| 6 | Jobseeker dashboard | `dashboard-jobseeker.html` | Stats, recent applications, recommendations, saved jobs |
| 7 | Employer dashboard | `dashboard-employer.html` | Job posts, applicants table, monthly bar chart, status donut |
| 8 | Admin console | `dashboard-admin.html` | Platform metrics, growth line chart, moderation queue, users |

## Design system

- **Framework:** Tailwind CSS 3 (loaded via CDN — no `npm install` required)
- **Type:** Inter from Google Fonts
- **Color palette:**
  - `brand-600` `#4f46e5` — primary indigo
  - `emerald-500` `#10b981` — success / approved
  - `amber-500` `#f59e0b` — pending
  - `rose-500` `#ef4444` — danger / rejected
- **Layouts:** 12-column responsive grid; sidebar layouts for dashboards
- **Icons:** inline SVG (no icon library dependency)

## Content

The previews use the same company, role, and candidate names produced by
`database/seeders/SampleDataSeeder.php` (Sophie Martin, CloudNine Systems,
NorthStar Bank, etc.) so the marketing copy and dashboards stay consistent
with the live seeded database.

## Demo accounts (when running the actual Laravel app)

```
Admin:       admin@jobboard.com
Employer:    sample.cloudninesystems@jobboard-demo.com
Job seeker:  sample.sophie.martin@example.com
Password:    password
```

## Limitations

These are static mockups, not the running app. Specifically:

- No data persistence — pages do not connect to a database.
- Forms do not submit; clicks navigate between pages only.
- The jobseeker/employer/admin dashboards are visual prototypes that
  match the seeded counts, not live queries.
- Charts are CSS / SVG mockups, not Chart.js data bindings.
- Mobile responsiveness is functional but the layouts are desktop-first.

# DSP Certificate Lookup (`local_dsp_certificates`)

A Moodle LMS local plugin (MuTMS multi-tenancy) providing a compliance-optimized certificate lookup tool for Direct Support Learning (DSL) tenant administrators.

---

## Purpose

Moodle Workplace's built-in Certificate report displays the **certificate template name** rather than the course or certification the certificate was issued for. For agencies where all course certificates share a template named "Completion Certificate," the built-in report is nearly useless for audit purposes — every row looks identical.

This plugin replaces that report with a purpose-built lookup tool that:

- Displays the **actual course or certification name** by parsing the JSON metadata stored in `tool_certificate_issues.data`
- Allows tenant admins to find, verify, and download certificates for any DSP in their agency
- Enforces strict **tenant isolation** — admins can only see users within their own tenant
- Provides **individual PDF download** links and a **bulk zip download** for all certificates belonging to a selected user
- Supports **CSV export** for record-keeping

This plugin is designed for use in Oregon I/DD provider agencies subject to ODDS compliance audits. Accurate, accessible certificate documentation is a core audit requirement.

---

## Requirements

| Requirement | Version |
|---|---|
| Moodle LMS | 4.5+ |
| MuTMS (`tool_mutenancy`) | Any |
| DSL Tiers (`local_dsl_tiers`) | Any |
| PHP | 8.3+ |
| MySQL | 8.0+ (JSON_EXTRACT support required) |

---

## Features

- **Staff member autocomplete** — tenant-scoped user selector; no results render until a user is selected
- **Source type filter** — filter by Course certificates, Certification certificates, or both
- **Status filter** — Valid, Expired, or All
- **Expiration date filter** — show certificates expiring on or before a given date
- **Suspended user filter** — include or exclude suspended staff
- **Per-row PDF download** — direct link to each certificate PDF via Moodle's file store
- **Bulk zip download** — all certificates for the selected user in a single `.zip` file, with descriptive filenames
- **CSV export** — flat-text export of filtered results for record-keeping
- **Agency Management navigation** — registers as a child node under the shared "Agency Management" primary nav parent, visible only to users with the view capability

---

## Installation

1. Clone or download this repository into your Moodle installation:
   ```
   /local/local_dsp_certificates/
   ```

2. Log in to Moodle as a site administrator and navigate to **Site administration → Notifications** to trigger the plugin installation.

3. Assign the `local/dsp_certificates:view` capability to the **Tenant Admin** role (or equivalent) via **Site administration → Users → Permissions → Define roles**.

4. The **Agency Management** navigation item will appear automatically for users with the capability.

---

## Navigation

This plugin contributes a child node to a shared **Agency Management** primary navigation parent. Other DSL plugins contribute sibling nodes to the same parent independently — Moodle merges them into a single dropdown.

Current menu structure:
```
Agency Management ▾
   ├── Agency Credit Hours    (future plugin)
   ├── DSP Certificates       ← this plugin
   └── Staffing Checklist     (report_checklist, future update)
```

No changes to existing plugins are required when adding new tools to the menu.

---

## How It Works

### Certificate source resolution

Moodle Workplace stores issued certificates in `mdl_tool_certificate_issues`. The `component` column identifies what issued the certificate, and the `data` column contains JSON metadata with the human-readable source name:

| Component | Source | JSON key used |
|---|---|---|
| `mod_coursecertificate` | Course | `$.coursefullname` |
| `tool_dynamicrule` | Certification (via Dynamic Rule) | `$.certificationname` |
| Other | Falls back to template name | — |

### Tenant scoping

All queries enforce tenant isolation via MuTMS cohort membership. The viewing admin's tenant ID is read from `$USER->tenantid` (set by MuTMS). The corresponding `cohort_id` is looked up from `mdl_tool_mutenancy_tenant`, and results are restricted to users in `mdl_cohort_members` with that cohort — never derived from a URL parameter. A Tenant Admin can only ever retrieve records for users within their own tenant.

### PDF download

Certificate PDFs are retrieved directly from Moodle's file store using the File API:

```
component = 'tool_certificate'
filearea  = 'issues'
itemid    = mdl_tool_certificate_issues.id
filename  = '{code}.pdf'
```

No external HTTP requests are made. The zip download bundles files using PHP's `ZipArchive` with descriptive filenames in the format:

```
LASTNAME_FIRSTNAME_SOURCENAME_DATEISSUED.pdf
```

---

## Privacy

This plugin does not store any personal data. It is a read-only reporting tool that queries Moodle core tables (`tool_certificate_issues`, `tool_certificate_templates`, `user`) and MuTMS cohort tables (`cohort_members`). All personal data displayed is owned and managed by those subsystems.

Implements `\core_privacy\local\metadata\null_provider`.

---

## Capabilities

| Capability | Default role | Description |
|---|---|---|
| `local/dsp_certificates:view` | Manager | Access the Certificate Lookup tool |

Assign to the **Tenant Admin** role. Site administrators inherit access via the standard Moodle site admin override.

---

## Deployment

This plugin follows the DSL deployment workflow managed by Moodle US (MUS):

| Release type | Criteria | Process |
|---|---|---|
| Minor | Bug fixes, UI changes, additive features with no DB schema changes | Pull directly to production |
| Major | DB schema changes, new Workplace API integrations, behavior changes, first deployment | Pull to dev → test → pull to production |

First deployment of this plugin is a **Major release** — request a MUS pull to dev, test, then pull to production.

> Request a fresh dev clone from MUS before any major testing cycle. Dev and production can drift between refreshes.

---

## AMD Build

The `amd/src/filters.js` source file must be compiled to `amd/build/filters.min.js` using the Moodle grunt toolchain before deployment:

```bash
grunt amd
```

A pre-built `amd/build/filters.min.js` is included in this repository for convenience. Run grunt to regenerate it after any changes to the source file.

---

## Development

**Environment:** Moodle LMS 4.5+, MuTMS, PHP 8.3, MySQL 8.0

**Coding standards:** [Moodle coding style](https://moodledev.io/general/development/policies/codingstyle)

**Key files:**

| File | Purpose |
|---|---|
| `classes/certificate_helper.php` | All data access, SQL construction, URL helpers |
| `classes/certificates_table.php` | `table_sql` subclass — paginated, sortable results table |
| `classes/privacy/provider.php` | Privacy API null provider |
| `index.php` | Main page — filter form, table output, CSV export |
| `download.php` | Zip download endpoint |
| `templates/certificates_page.mustache` | Filter form and results area markup |
| `amd/src/filters.js` | Staff member autocomplete behaviour |
| `db/access.php` | Capability definitions |
| `db/caches.php` | Cache definitions (tenant user list) |

---

## Related Plugins

| Plugin | Purpose |
|---|---|
| `report_checklist` | ODDS Staffing Checklist — generates audit PDF for state regulators |
| `block_dsp_progress` | DSP progress dashboard showing credit hours and certification status |
| `local_dsp_isp` | ISP Manager — automates Individual Support Plan review course creation |

---

## License

GNU General Public License v3 or later  
http://www.gnu.org/copyleft/gpl.html

---

## About Direct Support Learning

[Direct Support Learning](https://www.directsupportlearning.com) is an Oregon-based company providing a compliance-focused Learning Management System exclusively for agencies that serve people with intellectual and developmental disabilities (I/DD). DSL's mission is to streamline and automate hiring, training, and compliance processes — empowering agencies to increase efficiency and focus on quality care.

support@directsupportlearning.com

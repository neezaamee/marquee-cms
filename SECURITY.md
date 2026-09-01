# Security Policy

## Supported Versions

The following table indicates which versions of MarqueeCMS currently receive security and maintenance updates:

| Version | Supported          | PHP Version  | Laravel Version |
| :---    | :---               | :---         | :---            |
| 1.3.x   | :white_check_mark: | 8.2 / 8.3    | 12.x            |
| 1.2.x   | :white_check_mark: | 8.2 / 8.3    | 12.x            |
| < 1.2   | :x:                | < 8.2        | <= 11.x         |

---

## Reporting a Vulnerability

The security of **MarqueeCMS** and our tenant data is our top priority. If you discover a security vulnerability, please report it responsibly.

### How to Report

* **Email**: Please send all vulnerability disclosures directly to `security@elaftech.com` or `devops@marqueecms.test`.
* **Details to Include**:
  * Description of the vulnerability and its potential impact.
  * Step-by-step instructions or proof-of-concept (PoC) to reproduce the issue.
  * The affected component, file, or API endpoint.
  * Suggested remediations or mitigations (if available).

### Response Timeline

* **Acknowledgment**: You will receive an initial response within **24 hours** of receipt.
* **Assessment & Fix**: Our security engineering team will provide status updates every **48 hours** until a patch is deployed.
* **Public Disclosure**: We ask that you give us adequate time to release a patch before making any public disclosure.

---

## Security Best Practices for Deployments

* **Environment Isolation**: Never commit `.env` files or hardcode production credentials in repository files.
* **Multi-Tenant Protection**: Always enforce `BelongsToTenant` and `BelongsToBranch` scopes on all Eloquent models and Livewire queries.
* **Authentication**: Use strong database and application keys (`APP_KEY`) generated via `php artisan key:generate`.
* **File Uploads**: All uploaded media (company logos, receipts) are sanitized and validated against allowed MIME types and file size boundaries.

# Security, Privacy & Data Sanitization Audit

**Target:** Apex Meridian Logistics (Portfolio Demonstration Candidate)  
**Standard:** Strict Zero-Confidentiality & Company-Neutral Policy  
**Review Status:** Audited and Passed  

---

## 1. Security & Privacy Principles

When publishing software originally developed for private or client operations into a public engineering portfolio, absolute data sanitization is paramount. Under no circumstances should real business identifiers, client information, proprietary tariffs, operational databases, or personal credentials be published.

### Core Sanitization Rules Enforced
1. **Zero Client Branding:** All occurrences of private client names, logos, letterheads, or corporate registration numbers have been replaced with the neutral brand *Apex Meridian Logistics*.
2. **Synthetic Data Exclusivity:** All databases, seeder records, invoice items, booking IDs, and milestone events are generated synthetically using reproducible seeders (`DemoSeeder.php`).
3. **No Production Secrets or Keys:** `.env.example` contains only non-sensitive local defaults. Application encryption keys (`APP_KEY`) are generated per environment.
4. **Dual Authentication Guarding:** Customer records and internal administrative records are stored in separated tables (`customers` vs `users`) with distinct session cookies to prevent privilege escalation.
5. **No Network Leaks:** Internal IP addresses (e.g., `192.168.1.111`), private subnets, staging domains, and real email addresses are excluded from the codebase.

---

## 2. Sanitization Audit & Token Scan Results

An exhaustive automated token scan was performed across all code, configuration files, views, assets, and database seeds:

| Token / Pattern Category | Original Value / Risk | Sanitized Replacement | Status |
| :--- | :--- | :--- | :--- |
| **Client Legal Name** | *LodeStar Line* / *نجم الشمال لاين* | *Apex Meridian Logistics* / *أفق ميريديان للخدمات اللوجستية* | **REPLACED (0 matches)** |
| **Client Domains** | `lodestar-line.com`, `ibrahimalhadi.com` | `example.com`, `apex-meridian.local` | **REPLACED (0 matches)** |
| **Phone Numbers** | Real Egyptian mobile & landlines (`01553818989`, `23750681`) | Synthetic international demo (`+1 (800) 555-0199`) | **REPLACED (0 matches)** |
| **Tax ID / Reg** | Egyptian Tax ID `700123456789003` | Generic alphanumeric `TAX-AE-2026-9901` | **REPLACED (0 matches)** |
| **Operational DBs** | `database/database.sqlite` (containing live client records) | Excluded via `.gitignore`; fresh synthetic database created via `DemoSeeder` | **QUARANTINED** |
| **Logistics Alliances** | Real WCA / FIATA / JC TRANS trademarked badges | Company-neutral SVG vector badges (Global Freight Alliance, etc.) | **REPLACED** |
| **Internal LAN IPs** | `192.168.1.111` in `.env` / scripts | `127.0.0.1` / `localhost` | **REPLACED** |

---

## 3. Application Security Controls

### 3.1 CSRF & Session Protection
Every state-changing HTTP request (`POST`, `PUT`, `DELETE`) is guarded by Laravel's `VerifyCsrfToken` middleware. Session cookies are configured with `HttpOnly`, `SameSite=lax`, and `Secure` flags when running over HTTPS.

### 3.2 SQL Injection Prevention
All database queries are executed through Laravel's Eloquent ORM or parameterized PDO query bindings, preventing SQL injection vulnerabilities.

### 3.3 Authorization & Scoping
Customer portal controllers enforce tenant isolation by scoping all queries directly to the authenticated user ID (`Auth::guard('customer')->id()`). A customer cannot view, modify, or download shipments, quotes, or invoices belonging to another account.

### 3.4 Financial Precision & Validation
All currency calculations, tariff surcharges, and tax rates are validated using strict numerical validation rules and stored using `decimal(10,2)` columns to eliminate floating-point arithmetic errors.

---

## 4. Demo-Specific Limitations

For the purposes of this public demonstration release candidate:
* **Mail Dispatch:** Configured with `MAIL_MAILER=log`. No actual emails are dispatched to external mail servers.
* **Payment Gateways:** Credit card processing and wire transfer verifications are simulated; no real payment gateway credentials (Stripe, PayPal, PayTabs) are bundled.
* **Carrier EDI / API Feeds:** Automated ocean carrier integrations (e.g., Maersk, MSC, CMA CGM API feeds) are replaced by synthetic schedule seeds to prevent live credential exposure.

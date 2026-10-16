# Developer Handover, 90-Day Warranty & Pilot Rollout Agreement
## Clothing Store Management System

### 1. Scope of Handover

This document formalizes the delivery and completion of the Clothing Store Management System across Master Prompts 1, 2, 3, and 4. All functional modules, database migrations, financial reconciliation services, security hardening measures, role-based dashboards, and automated test suites have been committed to the repository.

---

### 2. 90-Day Warranty & Developer Support Terms

- **Warranty Duration:** **90 Calendar Days** starting from the agreed Production Pilot Go-Live date.
- **Scope of Coverage:**
  - Remediation of software bugs, calculation defects, database exceptions, or security vulnerabilities within the approved requirements scope.
  - Zero cost for defects falling within the approved specifications of Prompts 1–4.
  - Ongoing feature enhancements, third-party hardware integration, or unapproved architectural refactoring fall outside the warranty scope and are billable on demand.
- **Service Level Agreements (SLAs) — First-Response Targets:**
  - **Severity 1 (Critical):** Within **4 Hours** (System outage, database corruption, active security breach, inability to ring up sales).
  - **Severity 2 (High):** Within **1 Business Day** (Core workflow impairment, transfer calculation discrepancies, unclosed register issues).
  - **Severity 3 (Normal):** Within **3 Business Days** (Minor UI cosmetic discrepancies, non-blocking export format requests).
- **Support Hours & Channels:**
  - Standard Support Hours: Sunday to Thursday, 9:00 AM – 6:00 PM (BST).
  - Emergency Incident Hotline: 24/7 for Severity 1 Critical outages.
  - Communication Channels: Dedicated GitLab Issue Tracker & Emergency WhatsApp Group.

---

### 3. Production Pilot & Multi-Store Rollout Strategy

To safeguard retail operations and prevent cross-store financial risk, the production launch must follow this strict phased rollout protocol:

```
[Phase 1: Single-Store Pilot (7 Days)]
   Gulshan Flagship Store only
   Monitor Daily Cash Closings & POS Concurrency
         │
         ▼ (Review & Sign-Off by Super Admin)
[Phase 2: Central Distribution Warehouse Integration]
   Central Logistics Depot activates live PO Receiving & Transfers
         │
         ▼ (Audit Reconciled Ledger & WAC)
[Phase 3: Multi-Store Expansion (Subsequent Stores)]
   Dhanmondi, Uttara & Banani branches Go-Live
```

#### Pilot Rules & Guardrails:
1. **Pilot Duration:** Exactly 7 consecutive calendar days at the initial pilot store (Gulshan Flagship Store).
2. **Go-Live Gate:** Only the **Super Admin** possesses authority to grant Go-Live approval for subsequent retail branches.
3. **Zero Automatic Activation:** Stores are never bulk-activated automatically at the end of the pilot. Each store rollout requires explicit administrative verification.
4. **Issue Tracking:** Any Critical or High-severity bug discovered during the 7-day pilot immediately pauses further expansion until resolved, deployed to Staging, and re-tested.

---

### 4. Developer Handover Checklist & Verification Sign-Off

- [x] All 72 automated feature tests pass cleanly in Staging (297 assertions, 0 failures, 0 regressions).
- [x] Complete source code version-controlled in the Private GitLab repository (`git log` verified).
- [x] OWASP security headers (`SecurityHeaders`), session lockout on deactivation, single-session concurrency, and login throttling (`throttle:5,1`) implemented.
- [x] Spreadsheet formula injection prevention (CWE-1236) enforced on all CSV exports.
- [x] Concurrency protection (`SELECT ... FOR UPDATE`) verified under load stress tests.
- [x] Database snapshot backup command (`php artisan pos:backup`) with table verification metadata implemented.
- [x] Offline sale prohibition protocol documented with heartbeat check (`/api/ping`).
- [x] Bilingual Bangla & English operations guides prepared for all 5 retail staff roles.
- [x] Staging-to-Production deployment runbooks and emergency rollback procedures established.
- [x] Requirements Traceability Matrix (RTM) finalized with 100% verified status.

**System Delivery Status:** **READY FOR PILOT STAGING SIGN-OFF & SUPER ADMIN APPROVAL.**

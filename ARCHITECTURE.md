# ABC Suite Architecture

## Purpose

ABC Suite should become a stable, deployable WordPress plugin for ABC Printing's internal workflow, not just a pile of merged experiments.

The plugin needs to support three layers:

1. **Internal workflow system** inside WordPress
2. **Outbound bridge/events** to OpenClaw or other automation receivers
3. **ABC-specific print operations** with business-aware data

---

## Product goals

ABC Suite should eventually support:

- estimating
- product/spec templates
- design requests
- artwork/proof tracking
- job log / production tracking
- pricing matrix management
- vendor handoff tracking
- reporting / payout support
- outbound automation events
- operator-friendly admin tools

---

## Core design principles

1. **Tiny plugin bootstrap**
   - `plugin.php` should only load core services and start the plugin.

2. **Clear module boundaries**
   - each feature area should have its own module bootstrap/service class.

3. **Domain first, transport second**
   - business objects and workflow rules should exist independently of webhook/OpenClaw transport.

4. **Graceful degradation**
   - if one module breaks, the entire plugin should not white-screen when avoidable.

5. **Repeatable deployments**
   - repo can contain working notes/tools, but production packaging must only ship runtime assets.

6. **Upgradeable schema**
   - table creation, migrations, and version upgrades must be explicit and idempotent.

---

## Proposed structure

```text
ABC-Suite/
  plugin.php
  README.md
  .distignore
  assets/
  templates/
  includes/
    Core/
      Plugin.php
      ModuleInterface.php
      ModuleLoader.php
      Logger.php
      Diagnostics.php
      Installer.php
      Upgrades.php
      Capabilities.php
    Domain/
      OrderContext.php
      EstimateContext.php
      JobContext.php
      ArtworkContext.php
      PrintSpec.php
    Modules/
      Admin/
      Estimates/
      Templates/
      Pricing/
      Production/
      Designer/
      Bridge/
      Reports/
      Vendors/
```

This repo is not fully moved to this structure yet, but that should be the target.

---

## Domain model

These are the main business entities the plugin should understand.

### 1. Estimate
- quote/estimate request
- customer info
- line items
- print specifications
- pricing inputs
- approval state
- linked order/job

### 2. Order
- WooCommerce order or internal order record
- customer identity
- totals/payment state
- linked estimate
- linked job(s)
- uploaded files / notes

### 3. Job
- production-level work item
- due date
- rush flag
- stage/status
- assigned owner
- vendor handoff info
- proof/artwork state

### 4. Artwork / Proof
- upload references
- proof version
- approval/rejection state
- timestamps
- internal notes

### 5. Product Template / Print Spec
- normalized product settings
- stock
- finish
- size
- sides
- bleed/trim
- lamination
- quantity tiers
- machine/vendor rules

### 6. Vendor Task
- external fulfillment work
- vendor name
- sent at / due at / completed at
- notes
- status

### 7. Commission / Payout
- commission items
- payout status
- linked order/job
- recipient user

---

## Data ownership

Not everything should live in the same storage layer.

### Use WooCommerce / WP posts when:
- object needs native admin list/edit UI
- revisions and metadata are acceptable
- records are relatively document-like

### Use custom tables when:
- data is log-like, relational, or query-heavy
- performance matters
- data has many rows or needs indexed reporting

### Recommended split

#### CPT / post-based
- estimates
- product templates
- design requests
- suite records / notes

#### Custom tables
- job logs
- status history
- commission items
- pricing rules
- order/job links
- webhook / bridge attempts
- diagnostics events
- vendor task ledger (if it becomes query-heavy)

---

## Module map

### Core module
Responsibilities:
- plugin boot
- module registration
- environment checks
- schema versioning
- diagnostics
- logging

### Admin module
Responsibilities:
- top-level menu
- diagnostics screen
- settings framework
- notices
- reusable admin UI helpers

### Estimates module
Responsibilities:
- estimate CPT
- estimate forms/meta boxes
- estimate calculation support
- conversion to order/job

### Templates module
Responsibilities:
- product templates
- reusable spec defaults
- organization templates

### Pricing module
Responsibilities:
- price matrix
- pricing rules
- rate lookup services
- future machine/vendor pricing logic

### Production module
Responsibilities:
- job log
- job jacket
- status history
- production stages
- due/rush/vendor workflow

### Designer module
Responsibilities:
- design requests
- designer-facing tools
- artwork/proof states
- b2b designer frontend

### Bridge module
Responsibilities:
- settings for OpenClaw/webhook receiver
- outbound event builder
- event logs
- manual resend
- test connection
- payload preview

### Reports module
Responsibilities:
- payout report
- job/production summaries
- future dashboards/exports

### Vendors module
Responsibilities:
- vendor records
- vendor-specific routing metadata
- outsourced work tracking

---

## Event system

ABC Suite should emit normalized domain events.

### Event types for v1 bridge
- `bridge.test`
- `order.created`
- `order.status_changed`
- `estimate.created`
- `design_request.created`
- `artwork.uploaded`
- `proof.approved`
- `proof.rejected`
- `job.created`
- `job.status_changed`
- `admin.manual_send`

### Event envelope

```json
{
  "source": "abc-suite",
  "event": "order.created",
  "occurred_at": "2026-03-22T23:00:00Z",
  "site": {
    "name": "ABC Printing",
    "url": "https://example.com"
  },
  "entity": {
    "type": "order",
    "id": 1234
  },
  "customer": {},
  "items": [],
  "totals": {},
  "artwork": {},
  "abc_context": {}
}
```

### ABC-specific context examples
- due date
- rush flag
- stock
- size
- finish
- lamination
- proof status
- production stage
- vendor assignment
- internal notes summary

---

## Security expectations

Bridge/security baseline should include:

- capability checks on all admin actions
- nonce checks on all write actions
- sanitized options
- secret/token not re-shown in plain text after save
- optional HMAC signature support
- timeout/error handling for outbound requests
- redacted logs
- quick disable toggle for bridge
- audit trail for bridge config changes

---

## Diagnostics expectations

A diagnostics screen should show:

- plugin version
- schema version
- active module status
- missing dependencies
- WooCommerce presence
- writable paths (if needed)
- last boot errors
- last bridge attempt
- recent module failures
- required table existence

---

## Definition of done

### Foundation done
- plugin activates cleanly
- bootstrap is resilient
- schema install/upgrade works safely
- diagnostics page exists
- package is deployable

### Workflow done
- estimates, templates, pricing, job log, and design requests work without fatal errors
- admin screens are navigable and consistent
- core entities are linked coherently

### Bridge done
- connection test works
- logs exist
- resend exists
- event toggles exist
- payload schema is stable

### Automation done
- receiver can archive/process events reliably
- success/failure is visible in WordPress

---

## Current priority

1. stabilize existing code
2. define domain/data ownership
3. add diagnostics
4. isolate bridge behavior
5. refactor modules incrementally

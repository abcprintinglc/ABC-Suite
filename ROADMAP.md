# ABC Suite Roadmap

## Phase 0 — Stabilization and visibility

Goal: stop mystery failures and make the current plugin inspectable.

### Tasks
- [x] Centralize module loading
- [x] Improve boot error reporting
- [x] Add packaging exclusions
- [x] Add repo-level README
- [ ] Add diagnostics/admin health screen
- [ ] Add bridge attempt logging table
- [ ] Add schema version option
- [ ] Add migration runner
- [ ] Add settings registration hardening

### Success criteria
- plugin no longer fails silently
- missing modules/dependencies are visible
- production package excludes non-runtime junk

---

## Phase 1 — Core architecture cleanup

Goal: create a maintainable plugin skeleton.

### Tasks
- [ ] Create `includes/Core/` structure
- [ ] Create `includes/Modules/` structure
- [ ] Move module loader into `Core`
- [ ] Introduce module interface/base pattern
- [ ] Create shared logger service
- [ ] Create diagnostics service
- [ ] Create upgrade/migration service
- [ ] Normalize admin menu ownership

### Success criteria
- plugin loads through a predictable core runtime
- each module has a clear home
- future features can be added without load-order spaghetti

---

## Phase 2 — Define domain and storage

Goal: stop mixing data randomly.

### Tasks
- [ ] Inventory current CPTs/meta/tables
- [ ] Define canonical entities: estimate/order/job/artwork/template/vendor/commission
- [ ] Decide what remains CPT-based vs moves to tables
- [ ] Add or update relationship mapping between estimate/order/job
- [ ] Standardize status enums where practical
- [ ] Standardize ABC print spec fields

### Success criteria
- clear ownership of business data
- no ambiguous “where does this live?” decisions
- future automation payloads map cleanly from internal data

---

## Phase 3 — Admin and operator UX

Goal: make it usable when people are busy.

### Tasks
- [ ] Add diagnostics page
- [ ] Add bridge health panel
- [ ] Clean up top-level suite menu
- [ ] Add consistent notices/feedback patterns
- [ ] Add order/admin meta boxes for manual actions
- [ ] Add last event / last error visibility
- [ ] Add payload preview tools

### Success criteria
- operator can see system health quickly
- operator can test and resend without code access
- UI feels like a product, not a dev sandbox

---

## Phase 4 — Bridge v1

Goal: reliable outbound events to OpenClaw or another receiver.

### Tasks
- [ ] Formalize bridge settings (URL, token/secret, enable flag, timeout)
- [ ] Add connection test action
- [ ] Add bridge event log
- [ ] Add retry/resend flow
- [ ] Add event enable/disable toggles
- [ ] Implement event envelope schema
- [ ] Implement HMAC option
- [ ] Add redacted request/response logging

### Initial events
- [ ] `bridge.test`
- [ ] `order.created`
- [ ] `order.status_changed`
- [ ] `estimate.created`
- [ ] `design_request.created`
- [ ] `admin.manual_send`

### Success criteria
- events send reliably
- failures are visible and retryable
- receiver gets a stable payload format

---

## Phase 5 — ABC workflow intelligence

Goal: model print-shop reality instead of generic Woo data.

### Tasks
- [ ] Normalize due date / rush fields
- [ ] Normalize stock / size / finish / lamination fields
- [ ] Add proof/artwork status tracking
- [ ] Add production stages
- [ ] Add vendor assignment workflow
- [ ] Improve job jacket generation
- [ ] Link estimates/orders/jobs consistently

### Success criteria
- plugin understands business-specific workflow
- events contain useful print metadata
- staff can track real production status

---

## Phase 6 — Reports and automation backbone

Goal: make the suite operationally powerful.

### Tasks
- [ ] Improve payout/commission reporting
- [ ] Add production summaries
- [ ] Add receiver contract docs
- [ ] Build JSON archive flow on receiver side
- [ ] Build CSV/job log append flow on receiver side
- [ ] Add job folder creation flow if still desired
- [ ] Add downstream integrations as needed

### Success criteria
- data leaves WordPress in a structured way
- downstream processing is reliable
- reporting becomes trustworthy

---

## Immediate next build targets

If working incrementally, these should come next:

1. diagnostics page
2. bridge log table
3. bridge settings cleanup
4. manual resend flow
5. event schema definition

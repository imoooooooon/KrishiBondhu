# F19: Treatment History and Reminders

- **SRS requirement:** R19 — Farmers can keep treatment notes and follow-up dates.
- **Actor:** Farmer (signed in, `Role::FARMER`).
- **Branch:** `sajedul/treatment-history`

## 1. Scope and Rules

1. **Treatment records (`treatment_records`):**
   - A farmer can record crop or animal health problems, treatments applied, medicine/dosage notes, treatment date, and an optional follow-up date.
   - Category is restricted to `crop` or `animal`.
   - Status is `ongoing` or `completed` (default `ongoing`).
   - Only the owning farmer can view, edit, or remove their own treatment records.

2. **Follow-up reminders (`reminders`):**
   - Designed to be shared across features (F10 pregnancy/calving and F19 treatment follow-ups) via nullable `source_type` and `source_id` columns.
   - When a farmer creates or updates a treatment record with a `follow_up_date`, the system creates or updates the linked pending reminder for that treatment.
   - If `follow_up_date` is cleared or the treatment is deleted, any pending reminder for that treatment is removed.
   - Farmers can view their reminders at `/reminders` (`reminders.index`) and mark a pending reminder as done via `PATCH /reminders/{reminder}/done` (`reminders.complete`).

## 2. Routes (Existing in `IA.md`)

- `treatments.*` resource (`/treatments`, `/treatments/create`, `/treatments/{treatment}`, `/treatments/{treatment}/edit`) in `routes/modules/health.php` (`auth`).
- `reminders.index` (`GET /reminders`) and `reminders.complete` (`PATCH /reminders/{reminder}/done`) in `routes/modules/account.php` (`auth`).
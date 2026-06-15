---
name: project-catalog-status
description: Catalog/Status module — product statuses (ACTIVO, DESCONTINUADO, ELIMINADO) implemented June 2026
metadata:
  type: project
---

Catalog/Status module fully implemented in June 2026.

**Why:** Defines possible states for a product/medication. Editable catalog — admin can add new states beyond the 3 seeded ones. No transition rules in this module; those belong in Products.

**How to apply:** When working on Products module, the StatusId FK links to `product_statuses.id`. The `is_active` field is the catalog's soft-delete flag, not the semantic meaning of "ACTIVO" code.

## Key design decisions
- AR name: `ProductStatus` (not `Status` — avoids conflicts)
- Table: `product_statuses`
- `StatusCode` normalizes to uppercase in constructor (same pattern as [[project-catalog-routes]])
- Unique constraints: LOWER() functional indexes on both `name` and `code`
- 3 pre-seeded statuses: ACTIVO, DESCONTINUADO, ELIMINADO (idempotent via `updateOrInsert`)
- Permission: `catalog.statuses.manage` — granted to super-admin, branch-manager, auditor
- Routes: `GET/POST/GET{id}/PUT{id}/DELETE{id}` under `/v1/catalog/statuses`

## File locations
- Domain: `src/PharmaControl/Catalog/Status/Domain/`
- Application: `src/PharmaControl/Catalog/Status/Application/`
- Infrastructure: `src/PharmaControl/Catalog/Status/Infrastructure/`
- Migration: `database/migrations/2026_01_03_000002_create_product_statuses_table.php`
- Seeders: `ProductStatusesSeeder`, `StatusPermissionsSeeder`
- App controller: `app/Http/Controllers/Catalog/StatusController.php`
- Resource: `app/Http/Resources/Catalog/StatusResource.php`

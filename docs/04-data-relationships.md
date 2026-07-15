# 04 — Data Relationships (Current State & Issues)

How tables connect today — with every inconsistency the schema designer must fix.

## Central Hub: organizations

The `organizations` table is the hub. 17 org_types flow in from DESE exports. Every org_type has different optional fields, handled via `tags JSON`.

## Relationship Map

| From | To | Current Join | Issue | Should Be |
|------|----|-------------|-------|-----------|
| restraint_data | organizations | `r.org_code = o.org_code` | STRING, no FK | `r.org_id = o.id` FK |
| discipline_data | organizations | `d.district_code = o.org_code` | STRING, no FK | `d.org_id = o.id` FK |
| enrollment_data | organizations | `e.district_code = o.org_code` | STRING, no FK | `e.org_id = o.id` FK |
| attendance_data | organizations | `a.district_code = o.org_code` | STRING, no FK | `a.org_id = o.id` FK |
| sped_results | organizations | `s.district_code = o.org_code` | STRING, no FK | `s.org_id = o.id` FK |
| prs_data | organizations | `p.district_code = o.org_code` | STRING, no FK | `p.org_id = o.id` FK |
| teacher_salaries | organizations | `t.district_code = o.org_code` | STRING, no FK | `t.org_id = o.id` FK |
| cases | organizations | `c.district_id = o.id` | CORRECT FK but inconsistent in subqueries | Standardize |
| prs_intakes_data | organizations | NAME-BASED `district_agency` | No FK at all | Add org_id FK with name→code mapping |
| prr_tracker | organizations | NAME-BASED `agency` | No FK at all | Add org_id FK with name→code mapping |
| documents | organizations | `org_id` FK | CORRECT | Keep |
| documents | cases | `case_id` FK | CORRECT | Keep |
| documents | PRS | `prs_number` VARCHAR | No FK | Add FK or junction |
| documents | PRR | `prr_request_id` FK | CORRECT | Keep |
| articles | cases | `article_case_links` junction | CORRECT | Keep |
| articles | organizations | `article_org_links` junction | CORRECT | Keep |
| speeches | organizations | `related_district_code` VARCHAR | STRING, no FK | `org_id` FK |
| updates | (nothing) | No org/case FK | Can't trace updates to districts | Add optional FK |

## The org_code Problem

Seven DESE data tables use `org_code` (VARCHAR) to reference organizations. These are INDEXED but not foreign keyed. The fix:

1. Add `org_id INT UNSIGNED NULL` to each DESE table
2. Populate: `UPDATE t JOIN organizations o ON t.district_code = o.org_code SET t.org_id = o.id`
3. Add FK constraint
4. Drop old `district_code` column
5. Update all controller queries

## The Name-Based Problem

PRS intakes and PRR tracker use organization NAMES, not codes. A name→code mapping table or cleaning step is needed before they can FK to organizations.

## Two Document Systems

The ingest pipeline and scraper both produce documents but use different schemas with non-overlapping columns. The rebuild must unify into one `documents` table.

## Organization Hierarchy

Schools belong to districts via `parent_org_code` (self-referencing on organizations). This is correct but the FK should be on `org_code` not `id` for the parent reference — or the parent should reference `id`.

## Junction Tables (Correct)

- `article_case_links`: article_id → articles.id, case_id → cases.id
- `article_org_links`: article_id → articles.id, org_id → organizations.id
- `article_tag_links`: article_id → articles.id, tag_id → article_tags.id
- `case_documents`: case_id → cases.id (but no FK to documents table!)

These are the model to follow for new junctions.

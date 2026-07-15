# 02 — Document Types Catalog

Every document type, format, and workflow in the PRS/PRR processes.

## 7 Document Families (from config/ingest_rules/doc_families/)

### 1. PRS — Problem Resolution System
DESE complaints about district non-compliance. Parser: parsers/prs.py. Links: case, organization, event. Strict deadline extraction.

### 2. Public Records Request
MGL c.66 Sec 10 requests, responses, appeals. Parser: parsers/public_records.py. Tracks request numbers, scope, appeal posture, fee disputes.

### 3. Determination
DESE/SPR official findings with ordered actions. Parser: parsers/determination.py. Requires public review before publishing.

### 4. Correspondence
Letters between parties. Parser: parsers/correspondence.py. Lightweight — 0.63 confidence.

### 5. Email (.eml)
Full email processing with thread relationships, 15 process signals, attachment inventory. Parser: parsers/email.py.

### 6. Spreadsheet (.xlsx/.xls/.csv)
Structured data exports. Parser: parsers/spreadsheet.py. Prefers table extraction.

### 7. Attachment (generic)
Catch-all for PDFs, images, unknown formats. Routed to metadata analysis.

All families: defaultVisibility=internal, defaultPublishEligible=false.

## Classification Chain (14 rules, from classify.py)

Priority order: .eml(0.99) > .xlsx/.csv(0.97) > SPR keywords(0.91) > PRS+local_response(0.95) > "prs"(0.88) > "determination"(0.95) > response letter(0.93) > fee waiver(0.92) > appendix(0.85) > .zip+PRR(0.94) > appeal/request(0.90) > correspondence(0.82) > known extension(0.55) > fallback(0.15)

## 28 Ingest Subtypes

Public Records(9): initial_request, district_response, initial_appeal, spr_response, spr_decision, responsive_records, fee_estimate, follow_up, withdrawal

PRS(5): initial_complaint, letter_of_finding, letter_of_closure, district_response_prs, corrective_action

SPR Appeals(3): appeal_filing, spr_order, reconsideration

IEP(4): iep_document, progress_report, evaluation, service_logs

Court/BSEA(3): complaint, order, settlement

Governance(3): meeting_minutes, meeting_agenda, policy

Data(2): spreadsheet, report

Correspondence(2): email, letter

Media(2): audio, video

## 15 Scraper Classes

meeting_agenda, meeting_minutes, meeting_packet, meeting_video, policy_manual, budget, annual_report, school_handbook, sepac_info, prr_response, des_report, correspondence, testimony, legal_filing, media_coverage, other

## 6 Source Systems

dese, apptegy, civicengage, boarddocs, youtube, manual

## 5 Situation Rules (cross-cutting)

metadata-red-flags > queue privacy review | ocr-needed > trigger OCR | table-heavy > prefer table extraction | internal-default-publish > keep internal | same-thread-linking > suggest thread links

## Documents Table (44 columns)

Classification: doc_type, doc_subtype. Content: title, description, body_text. File: path, url, name, size, mime, hash. Metadata: source, email_id, dates. People: author, recipient, agent. Decisions: summary, date, by. Processing: ocr, readability, pages, language. Links: parent_doc_id, case_id, org_id, prs_number, prr_request_id. Status: is_public, is_active.

**Observation**: Table does too much. Rebuild should split into documents (file/content), document_links (relationships), document_workflow (processing).

## Two Parallel Systems

(A) ingest pipeline: keyword taxonomy > 44-column documents table. (B) scraper: lifecycled ScrapedDocument > separate documents table. Must unify.

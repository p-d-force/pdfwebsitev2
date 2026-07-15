-- ============================================================================
-- Parent Data Force — Sample Seed Data
-- Minimal but realistic data for development and demo.
-- All org_code values match real MA DESE codes.
-- ============================================================================

-- ============================================================================
-- ORGANIZATIONS — 10 sample districts + schools
-- ============================================================================

INSERT IGNORE INTO organizations (id, org_code, org_name, org_type, parent_org_id, town, grade_span, title_1_status, tags) VALUES
-- Public School Districts
(1,  '00160000', 'Attleboro Public Schools',     'Public School District', NULL, 'Attleboro',     NULL, NULL, '{"lea_code":"0016"}'),
(2,  '00350000', 'Boston Public Schools',         'Public School District', NULL, 'Boston',        NULL, NULL, '{"lea_code":"0035"}'),
(3,  '00480000', 'Brockton Public Schools',       'Public School District', NULL, 'Brockton',      NULL, NULL, '{"lea_code":"0048"}'),
(4,  '02070000', 'Newton Public Schools',         'Public School District', NULL, 'Newton',        NULL, NULL, '{"lea_code":"0207"}'),
(5,  '02740000', 'Worcester Public Schools',      'Public School District', NULL, 'Worcester',     NULL, NULL, '{"lea_code":"0274"}'),
(6,  '00950000', 'Fall River Public Schools',     'Public School District', NULL, 'Fall River',    NULL, NULL, '{"lea_code":"0095"}'),
(7,  '01550000', 'Lowell Public Schools',         'Public School District', NULL, 'Lowell',        NULL, NULL, '{"lea_code":"0155"}'),
(8,  '02370000', 'Springfield Public Schools',    'Public School District', NULL, 'Springfield',   NULL, NULL, '{"lea_code":"0237"}'),

-- Public Schools (children of districts above)
(9,  '00160005', 'Attleboro High School',         'Public School', 1, 'Attleboro',     '09-12', NULL, '{}'),
(10, '00160010', 'Brennan Middle School',         'Public School', 1, 'Attleboro',     '05-08', NULL, '{}'),
(11, '00350555', 'Boston Latin School',           'Public School', 2, 'Boston',        '07-12', NULL, '{}'),
(12, '02070025', 'Newton North High School',      'Public School', 4, 'Newton',        '09-12', NULL, '{}'),
(13, '02740505', 'Worcester Technical High School','Public School', 5, 'Worcester',     '09-12', 'Title 1 Schoolwide', '{}'),

-- Charter Schools
(14, '04150000', 'Mystic Valley Regional Charter School', 'Charter School', NULL, 'Malden', 'K-12', NULL, '{}'),
(15, '04900000', 'Abby Kelley Foster Charter Public School', 'Charter School', NULL, 'Worcester', 'K-12', NULL, '{}'),

-- Collaborative Programs
(16, '06100000', 'Bi-County Collaborative',       'Collaborative Program', NULL, 'Franklin', NULL, NULL, '{"function_area":"Special Education"}'),

-- Approved SPED Schools
(17, '50150000', 'The New England Center for Children', 'Approved SPED School', NULL, 'Southborough', NULL, NULL, '{}'),
(18, '50600000', 'The May Institute',             'Approved SPED School', NULL, 'Randolph', NULL, NULL, '{}');

-- ============================================================================
-- CASES — sample advocacy cases
-- ============================================================================

INSERT IGNORE INTO cases (id, case_number, title, slug, case_type, status, filed_date, org_id, summary, body, is_featured, is_active) VALUES
(1, 'PRS-2024-0016', 'Attleboro — Systemic Restraint Investigation',
    'attleboro-systemic-restraint-investigation',
    'PRS', 'open', '2024-09-15', 1,
    'DESE Problem Resolution System investigation into systemic use of physical restraint at Attleboro Public Schools, initiated following multiple parent complaints and data analysis showing restraint rates significantly above state averages.',
    '<h2>Background</h2><p>In September 2024, Parent Data Force filed a systemic PRS complaint with the Massachusetts Department of Elementary and Secondary Education (DESE) regarding the Attleboro Public Schools'' use of physical restraint on students with disabilities.</p><h2>Data Analysis</h2><p>DESE data shows Attleboro''s restraint rate was 2.7 per 100 students in 2022-2023, compared to the state average of 1.4. For students with IEPs, the rate was 8.2 per 100.</p><h2>Complaint Allegations</h2><ul><li>Failure to implement positive behavioral interventions and supports (PBIS) with fidelity</li><li>Inadequate staff training on de-escalation techniques</li><li>Failure to conduct proper functional behavioral assessments (FBAs) before restraint use</li><li>Inadequate parent notification following restraint incidents</li></ul><h2>Current Status</h2><p>DESE accepted the complaint for investigation in October 2024. On-site monitoring visits are ongoing. A Letter of Finding is expected in Q2 2025.</p>',
    1, 1),

(2, 'SPR-2024-0089', 'Attleboro — SPR Appeal on Records Denial',
    'attleboro-spr-appeal-records-denial',
    'SPR', 'active', '2024-06-01', 1,
    'Appeal to the Massachusetts Supervisor of Public Records challenging Attleboro Public Schools'' denial of access to restraint incident reports and staff training records.',
    '<h2>Background</h2><p>On March 15, 2024, a public records request was submitted to Attleboro Public Schools seeking all restraint incident reports for the 2022-2023 school year, along with records of staff restraint training completion.</p><h2>The Denial</h2><p>The district denied the request, citing the student privacy exemption (MGL c. 66, Sec 10) and claiming that incident reports, even with student names redacted, could identify students due to small numbers.</p><h2>The Appeal</h2><p>An appeal was filed with the SPR on June 1, 2024, arguing that redaction of personally identifiable information should address privacy concerns, and that aggregate restraint data is already publicly reported by DESE.</p><h2>SPR Determination</h2><p>The SPR issued an order in August 2024 directing the district to provide: (1) redacted incident reports with student names, dates of birth, and MEPA-identifiable information removed; (2) aggregate staff training completion data.</p>',
    1, 1),

(3, 'PRR-2024-0042', 'Boston — Public Records: SPED Placement Data',
    'boston-public-records-sped-placement-data',
    'PRR', 'resolved', '2024-03-10', 2,
    'Public records request to Boston Public Schools for out-of-district special education placement data, including costs, placement types, and transportation expenditures for 2021-2023.',
    '<h2>Request Summary</h2><p>A comprehensive public records request was submitted to Boston Public Schools seeking detailed data on out-of-district special education placements for school years 2021-2022 and 2022-2023.</p><h2>Data Requested</h2><ul><li>Number of students in out-of-district placements by disability category</li><li>Per-pupil costs by placement type and school</li><li>Transportation costs associated with out-of-district placements</li><li>Number of placements initiated through IEP team decisions vs. BSEA orders</li></ul><h2>Outcome</h2><p>After an initial fee estimate of $4,200 for "search and segregation time," the request was narrowed in scope and responsive records were provided. The data revealed 1,847 students in out-of-district placements at a total cost of $78.3 million for 2022-2023.</p>',
    1, 1),

(4, 'OCR-2024-0012', 'Brockton — OCR Complaint: Disciplinary Disparities',
    'brockton-ocr-complaint-disciplinary-disparities',
    'OCR', 'open', '2024-11-01', 3,
    'Office for Civil Rights complaint alleging discriminatory discipline practices at Brockton Public Schools, with Black students and students with disabilities receiving disproportionately harsher disciplinary actions.',
    '<h2>Complaint Basis</h2><p>DESE discipline data from 2022-2023 shows that Black students in Brockton received out-of-school suspensions at 3.1 times the rate of white students, and students with IEPs were suspended at 2.4 times the rate of general education students.</p><h2>OCR Investigation</h2><p>The complaint, filed jointly by Parent Data Force and the Massachusetts Advocates for Children, requests an OCR investigation under Title VI of the Civil Rights Act and Section 504 of the Rehabilitation Act.</p>',
    1, 1),

(5, 'BSEA-2024-0052', 'In re: Student v. Newton Public Schools',
    'student-v-newton-public-schools-bsea',
    'BSEA', 'resolved', '2024-02-20', 4,
    'Bureau of Special Education Appeals case regarding denial of FAPE (Free Appropriate Public Education) for a student with autism at Newton Public Schools.',
    '<h2>Case Summary</h2><p>A BSEA hearing was requested after Newton Public Schools proposed to move a student with autism spectrum disorder from a substantially separate language-based program to a partial inclusion setting without adequate supports.</p><h2>Parent Position</h2><p>The parents argued that the proposed placement would result in regression and denied the student FAPE under IDEA. They sought continued placement in the substantially separate program with additional ABA services.</p><h2>Outcome</h2><p>The BSEA hearing officer found in favor of the parents in June 2024, ordering the district to maintain the current placement and provide compensatory services for the period of disruption.</p>',
    0, 1);

-- ============================================================================
-- ARTICLES — sample content
-- ============================================================================

INSERT IGNORE INTO articles (id, title, slug, author, excerpt, body, published_date, article_type, is_featured, is_active) VALUES
(1, 'Massachusetts Restraint Data: A Five-Year Analysis',
    'massachusetts-restraint-data-five-year-analysis',
    'Joey Ford',
    'A comprehensive analysis of DESE restraint and seclusion data from 2019-2024 reveals persistent disparities in the use of physical restraint on students with disabilities across Massachusetts school districts.',
    '<h2>Key Findings</h2><p>Analysis of five years of DESE restraint data (2019-2024) reveals several concerning trends:</p><ul><li><strong>Students with IEPs are restrained at 4-5x the rate</strong> of general education students across all school years analyzed</li><li><strong>20 districts account for 45% of all restraints</strong> statewide, with the top 5 districts alone accounting for 18%</li><li><strong>Restraint rates declined during COVID-19</strong> (2020-2021) but have rebounded to pre-pandemic levels in 2023-2024</li><li><strong>Injury rates during restraint</strong> have remained steady at approximately 5-7% of all restraint incidents</li></ul><h2>Methodology</h2><p>Data was obtained from the Massachusetts DESE Profiles website and analyzed using Python (pandas, matplotlib). All 400+ public school districts were included. Restraint rates were calculated per 100 students using DESE enrollment figures.</p><h2>District-Level Patterns</h2><p>The analysis identified three distinct patterns: (1) consistently high-restraint districts, (2) districts with declining restraint rates after implementing PBIS programs, and (3) districts with restraint rates near zero. Districts in category 1 were disproportionately urban Title 1 districts.</p><h2>Policy Implications</h2><p>The data supports three key policy recommendations: (1) mandatory PBIS implementation with fidelity monitoring in high-restraint districts, (2) enhanced DESE oversight of restraint reporting accuracy, and (3) targeted technical assistance for districts serving high concentrations of students with emotional/behavioral disabilities.</p>',
    '2024-08-15', 'analysis', 1, 1),

(2, 'How to File a PRS Complaint with DESE',
    'how-to-file-prs-complaint-dese',
    'Joey Ford',
    'A step-by-step guide to filing a Problem Resolution System (PRS) complaint with the Massachusetts Department of Elementary and Secondary Education. Includes templates, deadlines, and what to expect.',
    '<h2>What is PRS?</h2><p>The Problem Resolution System (PRS) is DESE''s process for investigating complaints that a school district is not complying with state or federal education laws. PRS covers special education (IDEA), civil rights, English learner education, and other areas.</p><h2>When to File a PRS Complaint</h2><p>You should consider filing a PRS complaint when: (1) a school district has violated special education law or regulations, (2) the violation affects more than one student (systemic issue), (3) you have already attempted to resolve the issue at the district level, or (4) the issue involves a pattern or practice rather than an isolated incident.</p><h2>Filing Process</h2><ol><li><strong>Gather documentation:</strong> IEPs, emails, evaluation reports, incident reports, meeting notes. Organize chronologically.</li><li><strong>Draft the complaint:</strong> Use DESE''s PRS intake form. Clearly state: what law/regulation was violated, the facts showing the violation, what resolution you are seeking.</li><li><strong>Submit to DESE:</strong> Email to compliance@doe.mass.edu or mail to PRS, DESE, 75 Pleasant St., Malden, MA 02148.</li><li><strong>Wait for acknowledgment:</strong> DESE typically acknowledges receipt within 2 weeks.</li><li><strong>Investigation phase:</strong> DESE may request additional information, interview staff, review records, or conduct an on-site visit.</li><li><strong>Letter of Finding:</strong> DESE issues findings within 60 days (may be extended). If violations are found, corrective actions are ordered.</li></ol><h2>Templates</h2><p>Download our PRS complaint template for IDEA violations and public records request template at our Resources page.</p>',
    '2024-07-22', 'guide', 1, 1),

(3, 'Understanding Massachusetts Public Records Law for Parents',
    'understanding-massachusetts-public-records-law-parents',
    'Joey Ford',
    'Massachusetts Public Records Law (MGL c. 66, Sec 10) is a powerful tool for parents advocating for their children. Learn how to use it effectively to obtain school records, data, and correspondence.',
    '<h2>The Basics</h2><p>Massachusetts Public Records Law gives every person the right to access government records. School districts are government entities, and most of their records are public — including emails, reports, data, contracts, and policies.</p><h2>What You Can Request</h2><ul><li>School committee meeting materials and minutes</li><li>Staff emails about your child or district policies (with personal info redacted)</li><li>Aggregate data about special education, discipline, and staffing</li><li>Contracts with outside vendors and service providers</li><li>Training records and curriculum materials</li></ul><h2>The Process</h2><ol><li>Submit a written request (email is fine) to the district''s Records Access Officer (RAO)</li><li>The district has 10 business days to respond — they can provide records, deny with reasons, or request an extension</li><li>If denied or ignored, appeal to the Supervisor of Public Records (SPR)</li><li>If the SPR order is not followed, you can file in Superior Court</li></ol><h2>Common Pitfalls</h2><p>Districts often cite overly broad requests or excessive cost. Counter this by: (1) being specific about date ranges and record types, (2) requesting electronic records (no copying fees), (3) asking for a fee waiver if the information serves the public interest.</p>',
    '2024-06-10', 'guide', 1, 1),

(4, 'The State of Special Education in Massachusetts: 2024',
    'state-of-special-education-massachusetts-2024',
    'Joey Ford',
    'An overview of special education in Massachusetts — enrollment trends, outcomes, funding, and systemic challenges facing the approximately 175,000 students with IEPs in the Commonwealth.',
    '<h2>By the Numbers</h2><ul><li><strong>175,000+ students</strong> with IEPs in Massachusetts (approximately 19% of total enrollment)</li><li><strong>$5.2 billion</strong> in special education spending statewide</li><li><strong>82% graduation rate</strong> for students with IEPs vs. 91% overall</li><li><strong>2.7% dropout rate</strong> for students with IEPs vs. 1.2% overall</li><li><strong>8,400+ students</strong> in out-of-district placements at a cost exceeding $400 million</li></ul><h2>Key Issues for 2024</h2><p>Several systemic challenges demand attention: (1) the growing out-of-district placement rate and associated costs, (2) persistent disparities in discipline and restraint for students with disabilities, (3) a critical shortage of special education teachers and related service providers, (4) inadequate state oversight of IEP implementation.</p><h2>Looking Forward</h2><p>The 2024 legislative session includes several bills that could impact special education, including proposals for increased circuit breaker funding, mandatory restraint reporting improvements, and enhanced parent notification requirements.</p>',
    '2024-05-01', 'report', 1, 1),

(5, 'District Spotlight: Attleboro Public Schools — A Case Study in Advocacy',
    'district-spotlight-attleboro-public-schools',
    'Joey Ford',
    'A deep dive into Attleboro Public Schools — restraint data trends, special education outcomes, district spending, and the impact of sustained parent advocacy on systemic change.',
    '<h2>District Overview</h2><p>Attleboro Public Schools serves approximately 6,200 students across 10 schools. The district has a diverse student body: 42% economically disadvantaged, 17% students with IEPs, and 9% English learners.</p><h2>Restraint Data Trends</h2><p>Attleboro''s restraint rate has been consistently above the state average for the past five years. However, recent data shows a modest decline after targeted advocacy and DESE intervention.</p><h2>Advocacy Impact</h2><p>Sustained advocacy efforts — including PRS complaints, public records requests, school committee testimony, and media coverage — have contributed to several concrete changes: (1) adoption of a district-wide PBIS framework, (2) enhanced staff training on de-escalation, (3) improved parent notification procedures for restraint incidents, (4) creation of a Special Education Parent Advisory Council (SEPAC) subcommittee on restraint.</p>',
    '2024-04-18', 'analysis', 0, 1);

-- ============================================================================
-- ARTICLE-ORGANIZATION LINKS
-- ============================================================================

INSERT IGNORE INTO article_org_links (article_id, org_id) VALUES
(1, 1), (1, 2), (1, 3),   -- Restraint analysis covers multiple districts
(2, 1),                      -- PRS guide references Attleboro
(3, 1), (3, 2),             -- Public records guide
(4, 1), (4, 2), (4, 3), (4, 4), (4, 5),  -- Statewide report
(5, 1);                      -- Attleboro spotlight

-- ============================================================================
-- ARTICLE-CASE LINKS
-- ============================================================================

INSERT IGNORE INTO article_case_links (article_id, case_id) VALUES
(1, 1),   -- Restraint analysis → Attleboro PRS complaint
(5, 1),   -- Attleboro spotlight → Attleboro PRS complaint
(5, 2);   -- Attleboro spotlight → SPR appeal

-- ============================================================================
-- UPDATES — site updates timeline
-- ============================================================================

INSERT IGNORE INTO updates (id, title, slug, update_type, body, published_date, is_active) VALUES
(1, 'New PRS Complaint Filed — Attleboro Restraint Investigation', 'new-prs-complaint-attleboro-restraint', 'case',
    'Parent Data Force has filed a systemic PRS complaint with DESE regarding restraint practices at Attleboro Public Schools. The complaint alleges systemic non-compliance with IDEA requirements for positive behavioral interventions, functional behavioral assessments, and parent notification.',
    '2024-09-16 10:00:00', 1),

(2, 'SPR Orders Attleboro to Release Restraint Records', 'spr-orders-attleboro-records-release', 'case',
    'The Massachusetts Supervisor of Public Records has ordered Attleboro Public Schools to release redacted restraint incident reports and aggregate staff training data, ruling that the public interest in transparency outweighs privacy concerns that can be addressed through redaction.',
    '2024-08-22 14:30:00', 1),

(3, 'New Guide: How to File a PRS Complaint', 'new-guide-prs-complaint', 'site',
    'We have published a comprehensive step-by-step guide to filing a Problem Resolution System (PRS) complaint with DESE. The guide includes templates, deadlines, and practical advice based on our experience with the process.',
    '2024-07-23 09:00:00', 1),

(4, 'Data Portal Updated with 2023-2024 Restraint Data', 'data-portal-updated-2024', 'data',
    'The Data Portal has been updated with the latest DESE restraint and seclusion data for the 2023-2024 school year. New features include district comparison charts and multi-year trend analysis.',
    '2024-11-15 11:00:00', 1);

-- ============================================================================
-- MEDIA APPEARANCES
-- ============================================================================

INSERT IGNORE INTO media_appearances (id, title, appearance_date, venue, url, description) VALUES
(1, 'Public Comment on Restraint Policy', '2024-10-15', 'Attleboro School Committee', NULL,
    'Testimony regarding proposed revisions to the district''s physical restraint policy, urging adoption of enhanced parent notification requirements.'),
(2, 'Interview: Special Education Advocacy in Massachusetts', '2024-09-20', 'WBUR Radio Boston', 'https://www.wbur.org/radioboston/2024/09/20/special-education-advocacy',
    'Discussion of systemic challenges in Massachusetts special education and the role of parent advocacy in driving accountability.'),
(3, 'Panel: Data-Driven Advocacy for Families', '2024-06-05', 'Massachusetts Advocates for Children Annual Conference', NULL,
    'Panel presentation on using DESE public data to identify systemic patterns and support individual advocacy cases.'),
(4, 'Public Comment: FY2025 School Budget', '2024-04-10', 'Attleboro City Council', NULL,
    'Testimony on the proposed FY2025 school budget, focusing on special education funding adequacy and out-of-district placement costs.');

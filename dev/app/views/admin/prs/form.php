<h1 class="admin-heading"><?= $prs_case ? 'Edit PRS Case' : 'New PRS Case' ?></h1>

<form method="POST" class="admin-form">
    <?= csrf_field() ?>

    <fieldset>
        <legend>Case Identification</legend>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">PRS Number</label>
                <input type="text" name="prs_number" class="form-input" value="<?= h($prs_case['prs_number'] ?? '') ?>" placeholder="PRS-YEAR-NNNN" required style="width:200px;">
            </div>
            <div class="form-group">
                <label class="form-label">District</label>
                <select name="org_id" class="form-select" style="min-width:280px;">
                    <option value="">— Select District —</option>
                    <?php foreach ($districts as $d): ?>
                    <option value="<?= (int)$d['id'] ?>" <?= ($prs_case['org_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= h($d['org_code'] . ' — ' . $d['org_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Case Title</label>
            <input type="text" name="case_title" class="form-input" value="<?= h($prs_case['case_title'] ?? '') ?>" style="width:100%;">
        </div>
        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="case_description" class="form-textarea" rows="4" style="width:100%;"><?= h($prs_case['case_description'] ?? '') ?></textarea>
        </div>
    </fieldset>

    <fieldset>
        <legend>Status &amp; Resolution</legend>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Current Status</label>
                <select name="current_status" class="form-select" style="width:180px;">
                    <option value="filed" <?= ($prs_case['current_status'] ?? 'filed') === 'filed' ? 'selected' : '' ?>>Filed</option>
                    <option value="accepted" <?= ($prs_case['current_status'] ?? '') === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                    <option value="investigating" <?= ($prs_case['current_status'] ?? '') === 'investigating' ? 'selected' : '' ?>>Investigating</option>
                    <option value="findings" <?= ($prs_case['current_status'] ?? '') === 'findings' ? 'selected' : '' ?>>Findings Issued</option>
                    <option value="closed" <?= ($prs_case['current_status'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed</option>
                    <option value="appealed" <?= ($prs_case['current_status'] ?? '') === 'appealed' ? 'selected' : '' ?>>Appealed</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Resolution Type</label>
                <select name="resolution_type" class="form-select" style="width:200px;">
                    <option value="">— Not Set —</option>
                    <option value="substantiated" <?= ($prs_case['resolution_type'] ?? '') === 'substantiated' ? 'selected' : '' ?>>Substantiated</option>
                    <option value="unsubstantiated" <?= ($prs_case['resolution_type'] ?? '') === 'unsubstantiated' ? 'selected' : '' ?>>Unsubstantiated</option>
                    <option value="partially_substantiated" <?= ($prs_case['resolution_type'] ?? '') === 'partially_substantiated' ? 'selected' : '' ?>>Partially Substantiated</option>
                    <option value="resolved" <?= ($prs_case['resolution_type'] ?? '') === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                    <option value="withdrawn" <?= ($prs_case['resolution_type'] ?? '') === 'withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
                    <option value="dismissed" <?= ($prs_case['resolution_type'] ?? '') === 'dismissed' ? 'selected' : '' ?>>Dismissed</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Complainant Type</label>
                <input type="text" name="complainant_type" class="form-input" value="<?= h($prs_case['complainant_type'] ?? '') ?>" placeholder="e.g. Parent, Advocate" style="width:180px;">
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Dates</legend>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Filing Date</label>
                <input type="date" name="filing_date" class="form-input" value="<?= h($prs_case['filing_date'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Acceptance Date</label>
                <input type="date" name="acceptance_date" class="form-input" value="<?= h($prs_case['acceptance_date'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Investigation Start</label>
                <input type="date" name="investigation_start" class="form-input" value="<?= h($prs_case['investigation_start'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Findings Issued Date</label>
                <input type="date" name="findings_issued_date" class="form-input" value="<?= h($prs_case['findings_issued_date'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Closure Date</label>
                <input type="date" name="closure_date" class="form-input" value="<?= h($prs_case['closure_date'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Actual Resolution Date</label>
                <input type="date" name="actual_resolution_date" class="form-input" value="<?= h($prs_case['actual_resolution_date'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Statutory Deadline</label>
                <input type="date" name="statutory_deadline" class="form-input" value="<?= h($prs_case['statutory_deadline'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Extended Deadline</label>
                <input type="date" name="extended_deadline" class="form-input" value="<?= h($prs_case['extended_deadline'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-group-check">
                    <input type="checkbox" name="overdue_at_filing" value="1" <?= !empty($prs_case['overdue_at_filing']) ? 'checked' : '' ?>>
                    Overdue at Filing
                </label>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Duration (days)</legend>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Days to Acceptance</label>
                <input type="number" name="days_to_acceptance" class="form-input" value="<?= h($prs_case['days_to_acceptance'] ?? '') ?>" style="width:120px;">
            </div>
            <div class="form-group">
                <label class="form-label">Days to Findings</label>
                <input type="number" name="days_to_findings" class="form-input" value="<?= h($prs_case['days_to_findings'] ?? '') ?>" style="width:120px;">
            </div>
            <div class="form-group">
                <label class="form-label">Total Days Open</label>
                <input type="number" name="total_days_open" class="form-input" value="<?= h($prs_case['total_days_open'] ?? '') ?>" style="width:120px;">
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Allegations</legend>
        <div class="form-group">
            <label class="form-label">List each allegation on a separate line</label>
            <textarea name="allegations" class="form-textarea" rows="6" style="width:100%;font-family:monospace;"><?php
                if (!empty($prs_case['allegations'])) {
                    $allegs = json_decode($prs_case['allegations'], true);
                    if (is_array($allegs)) {
                        echo h(implode("\n", $allegs));
                    } else {
                        echo h($prs_case['allegations']);
                    }
                }
            ?></textarea>
        </div>
    </fieldset>

    <fieldset>
        <legend>Findings Summary</legend>
        <div class="form-group">
            <textarea name="findings_summary" class="form-textarea" rows="4" style="width:100%;"><?= h($prs_case['findings_summary'] ?? '') ?></textarea>
        </div>
    </fieldset>

    <fieldset>
        <legend>Corrective Actions Summary</legend>
        <div class="form-group">
            <textarea name="corrective_actions" class="form-textarea" rows="4" style="width:100%;"><?= h($prs_case['corrective_actions'] ?? '') ?></textarea>
        </div>
    </fieldset>

    <!-- Dynamic Findings Sub-Form -->
    <fieldset>
        <legend>Detailed Findings <button type="button" class="btn btn-sm btn-outline" onclick="addFinding()" style="margin-left:1rem;">+ Add Finding</button></legend>
        <div id="findings-container">
            <?php
            $existingFindings = $findings ?? [];
            if (!empty($existingFindings)):
                foreach ($existingFindings as $f):
            ?>
            <div class="finding-row" style="border:1px solid var(--border);padding:1rem;margin-bottom:0.75rem;border-radius:4px;">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <input type="text" name="finding_category[]" class="form-input" value="<?= h($f['allegation_category'] ?? '') ?>" style="width:200px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subcategory</label>
                        <input type="text" name="finding_subcategory[]" class="form-input" value="<?= h($f['allegation_subcategory'] ?? '') ?>" style="width:200px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Finding</label>
                        <select name="finding_result[]" class="form-select">
                            <option value="">— Not Set —</option>
                            <option value="substantiated" <?= ($f['finding'] ?? '') === 'substantiated' ? 'selected' : '' ?>>Substantiated</option>
                            <option value="unsubstantiated" <?= ($f['finding'] ?? '') === 'unsubstantiated' ? 'selected' : '' ?>>Unsubstantiated</option>
                            <option value="partially_substantiated" <?= ($f['finding'] ?? '') === 'partially_substantiated' ? 'selected' : '' ?>>Partially Substantiated</option>
                        </select>
                    </div>
                    <div class="form-group" style="align-self:flex-end;">
                        <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.finding-row').remove()">Remove</button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Detail</label>
                    <textarea name="finding_detail[]" class="form-textarea" rows="2" style="width:100%;"><?= h($f['finding_detail'] ?? '') ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Cited Regulation</label>
                        <input type="text" name="finding_regulation[]" class="form-input" value="<?= h($f['cited_regulation'] ?? '') ?>" style="width:250px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Corrective Action</label>
                        <input type="text" name="finding_ca[]" class="form-input" value="<?= h($f['corrective_action_ordered'] ?? '') ?>" style="width:300px;">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">CA Status</label>
                        <select name="finding_ca_status[]" class="form-select">
                            <option value="pending" <?= ($f['corrective_action_status'] ?? 'pending') === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="in_progress" <?= ($f['corrective_action_status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="completed" <?= ($f['corrective_action_status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="overdue" <?= ($f['corrective_action_status'] ?? '') === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">CA Deadline</label>
                        <input type="date" name="finding_ca_deadline[]" class="form-input" value="<?= h($f['corrective_action_deadline'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </fieldset>

    <!-- Dynamic Events Sub-Form -->
    <fieldset>
        <legend>Timeline Events <button type="button" class="btn btn-sm btn-outline" onclick="addEvent()" style="margin-left:1rem;">+ Add Event</button></legend>
        <div id="events-container">
            <?php
            $existingEvents = $events ?? [];
            if (!empty($existingEvents)):
                foreach ($existingEvents as $e):
            ?>
            <div class="event-row" style="border:1px solid var(--border);padding:0.75rem 1rem;margin-bottom:0.5rem;border-radius:4px;">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="date" name="event_date[]" class="form-input" value="<?= h($e['event_date'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <select name="event_type[]" class="form-select" style="min-width:180px;">
                            <option value="filed" <?= ($e['event_type'] ?? '') === 'filed' ? 'selected' : '' ?>>Filed</option>
                            <option value="acknowledged" <?= ($e['event_type'] ?? '') === 'acknowledged' ? 'selected' : '' ?>>Acknowledged</option>
                            <option value="assigned" <?= ($e['event_type'] ?? '') === 'assigned' ? 'selected' : '' ?>>Assigned</option>
                            <option value="extension_requested" <?= ($e['event_type'] ?? '') === 'extension_requested' ? 'selected' : '' ?>>Extension Requested</option>
                            <option value="extension_granted" <?= ($e['event_type'] ?? '') === 'extension_granted' ? 'selected' : '' ?>>Extension Granted</option>
                            <option value="investigation_opened" <?= ($e['event_type'] ?? '') === 'investigation_opened' ? 'selected' : '' ?>>Investigation Opened</option>
                            <option value="interview_conducted" <?= ($e['event_type'] ?? '') === 'interview_conducted' ? 'selected' : '' ?>>Interview Conducted</option>
                            <option value="site_visit" <?= ($e['event_type'] ?? '') === 'site_visit' ? 'selected' : '' ?>>Site Visit</option>
                            <option value="preliminary_findings" <?= ($e['event_type'] ?? '') === 'preliminary_findings' ? 'selected' : '' ?>>Preliminary Findings</option>
                            <option value="district_response" <?= ($e['event_type'] ?? '') === 'district_response' ? 'selected' : '' ?>>District Response</option>
                            <option value="findings_issued" <?= ($e['event_type'] ?? '') === 'findings_issued' ? 'selected' : '' ?>>Findings Issued</option>
                            <option value="corrective_action_ordered" <?= ($e['event_type'] ?? '') === 'corrective_action_ordered' ? 'selected' : '' ?>>Corrective Action Ordered</option>
                            <option value="compliance_verified" <?= ($e['event_type'] ?? '') === 'compliance_verified' ? 'selected' : '' ?>>Compliance Verified</option>
                            <option value="closed" <?= ($e['event_type'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed</option>
                            <option value="appealed" <?= ($e['event_type'] ?? '') === 'appealed' ? 'selected' : '' ?>>Appealed</option>
                            <option value="reopened" <?= ($e['event_type'] ?? '') === 'reopened' ? 'selected' : '' ?>>Reopened</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Actor</label>
                        <input type="text" name="event_actor[]" class="form-input" value="<?= h($e['actor'] ?? '') ?>" style="width:160px;">
                    </div>
                    <div class="form-group" style="align-self:flex-end;">
                        <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.event-row').remove()">Remove</button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <input type="text" name="event_description[]" class="form-input" value="<?= h($e['event_description'] ?? '') ?>" style="width:100%;">
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </fieldset>

    <div class="admin-form-actions" style="margin-top:2rem;">
        <button type="submit" class="btn btn-primary btn-lg">Save</button>
        <a href="/admin/prs" class="btn btn-outline btn-lg" style="margin-left:1rem;">Cancel</a>
    </div>
</form>

<script>
function addFinding() {
    const container = document.getElementById('findings-container');
    const div = document.createElement('div');
    div.className = 'finding-row';
    div.style.cssText = 'border:1px solid var(--border);padding:1rem;margin-bottom:0.75rem;border-radius:4px;';
    div.innerHTML = `
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Category</label>
                <input type="text" name="finding_category[]" class="form-input" style="width:200px;">
            </div>
            <div class="form-group">
                <label class="form-label">Subcategory</label>
                <input type="text" name="finding_subcategory[]" class="form-input" style="width:200px;">
            </div>
            <div class="form-group">
                <label class="form-label">Finding</label>
                <select name="finding_result[]" class="form-select">
                    <option value="">— Not Set —</option>
                    <option value="substantiated">Substantiated</option>
                    <option value="unsubstantiated">Unsubstantiated</option>
                    <option value="partially_substantiated">Partially Substantiated</option>
                </select>
            </div>
            <div class="form-group" style="align-self:flex-end;">
                <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.finding-row').remove()">Remove</button>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Detail</label>
            <textarea name="finding_detail[]" class="form-textarea" rows="2" style="width:100%;"></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Cited Regulation</label>
                <input type="text" name="finding_regulation[]" class="form-input" style="width:250px;">
            </div>
            <div class="form-group">
                <label class="form-label">Corrective Action</label>
                <input type="text" name="finding_ca[]" class="form-input" style="width:300px;">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">CA Status</label>
                <select name="finding_ca_status[]" class="form-select">
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="overdue">Overdue</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">CA Deadline</label>
                <input type="date" name="finding_ca_deadline[]" class="form-input">
            </div>
        </div>
    `;
    container.appendChild(div);
}

function addEvent() {
    const container = document.getElementById('events-container');
    const div = document.createElement('div');
    div.className = 'event-row';
    div.style.cssText = 'border:1px solid var(--border);padding:0.75rem 1rem;margin-bottom:0.5rem;border-radius:4px;';
    div.innerHTML = `
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Date</label>
                <input type="date" name="event_date[]" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Type</label>
                <select name="event_type[]" class="form-select" style="min-width:180px;">
                    <option value="filed">Filed</option>
                    <option value="acknowledged">Acknowledged</option>
                    <option value="assigned">Assigned</option>
                    <option value="extension_requested">Extension Requested</option>
                    <option value="extension_granted">Extension Granted</option>
                    <option value="investigation_opened">Investigation Opened</option>
                    <option value="interview_conducted">Interview Conducted</option>
                    <option value="site_visit">Site Visit</option>
                    <option value="preliminary_findings">Preliminary Findings</option>
                    <option value="district_response">District Response</option>
                    <option value="findings_issued">Findings Issued</option>
                    <option value="corrective_action_ordered">Corrective Action Ordered</option>
                    <option value="compliance_verified">Compliance Verified</option>
                    <option value="closed">Closed</option>
                    <option value="appealed">Appealed</option>
                    <option value="reopened">Reopened</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Actor</label>
                <input type="text" name="event_actor[]" class="form-input" style="width:160px;">
            </div>
            <div class="form-group" style="align-self:flex-end;">
                <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.event-row').remove()">Remove</button>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Description</label>
            <input type="text" name="event_description[]" class="form-input" style="width:100%;">
        </div>
    `;
    container.appendChild(div);
}
</script>

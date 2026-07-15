<section class="section prs-detail">
    <div class="container">
        <p class="breadcrumb" data-animate><a href="/prs">PRS Cases</a> &rsaquo; <?= h($case['prs_number']) ?></p>

        <!-- Header -->
        <div class="section-header" data-animate>
            <?= prsStatusBadge($case['current_status'], true) ?>
            <h2 class="section-title"><?= h($case['case_title'] ?? $case['prs_number']) ?></h2>
            <p class="section-subtitle"><?= h($case['prs_number']) ?> &mdash; filed <?= format_date($case['filing_date']) ?></p>
        </div>

        <?php if (!empty($case['org_name'])): ?>
        <div class="meta-link" data-animate>
            <strong>District:</strong>
            <a href="/prs/district/<?= h($case['org_code']) ?>"><?= h($case['org_name']) ?></a>
            <?php if (!empty($case['org_code'])): ?>
            &middot; <a href="/districts/<?= h($case['org_code']) ?>">District Profile</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Deadline Countdown Widget -->
        <?php if ($deadline && $case['current_status'] !== 'closed'): ?>
        <div class="deadline-countdown <?=
            $isOverdue ? 'deadline-overdue' :
            ($daysUntilDeadline <= 14 ? 'deadline-red' :
            ($daysUntilDeadline <= 30 ? 'deadline-amber' : 'deadline-green'))
        ?>" data-animate>
            <div class="deadline-label">Statutory Deadline</div>
            <div class="deadline-value">
                <?= $isOverdue ? abs($daysUntilDeadline) : $daysUntilDeadline ?>
                <span class="deadline-value-unit"><?= $isOverdue ? 'days overdue' : 'days remaining' ?></span>
            </div>
            <div class="deadline-sub">
                Deadline: <?= format_date($deadline) ?>
            </div>
        </div>
        <?php elseif ($deadline && $case['current_status'] === 'closed'): ?>
        <div class="deadline-countdown deadline-green" data-animate>
            <div class="deadline-label">Statutory Deadline</div>
            <div class="deadline-value" style="color:var(--text-muted);">
                &#10003;<span class="deadline-value-unit">Closed</span>
            </div>
            <div class="deadline-sub">
                Deadline was <?= format_date($deadline) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Meta Grid -->
        <div class="meta-grid" data-animate>
            <div class="meta-cell">
                <div class="meta-cell-label">Filing Date</div>
                <div class="meta-cell-value"><?= format_date($case['filing_date']) ?: '—' ?></div>
            </div>
            <div class="meta-cell">
                <div class="meta-cell-label">Acceptance Date</div>
                <div class="meta-cell-value"><?= format_date($case['acceptance_date']) ?: '—' ?></div>
            </div>
            <div class="meta-cell">
                <div class="meta-cell-label">Investigation Opened</div>
                <div class="meta-cell-value"><?= format_date($case['investigation_start']) ?: '—' ?></div>
            </div>
            <div class="meta-cell">
                <div class="meta-cell-label">Findings Issued</div>
                <div class="meta-cell-value"><?= format_date($case['findings_issued_date']) ?: '—' ?></div>
            </div>
            <div class="meta-cell">
                <div class="meta-cell-label">Closure Date</div>
                <div class="meta-cell-value"><?= format_date($case['closure_date']) ?: '—' ?></div>
            </div>
            <div class="meta-cell">
                <div class="meta-cell-label">Statutory Deadline</div>
                <div class="meta-cell-value <?= $isOverdue ? 'text-danger' : '' ?>">
                    <?= $deadline ? format_date($deadline) : '—' ?>
                    <?php if ($isOverdue): ?><br><small style="color:var(--danger);">OVERDUE</small><?php endif; ?>
                </div>
            </div>
            <div class="meta-cell">
                <div class="meta-cell-label">Days Open</div>
                <div class="meta-cell-value"><?= $case['days_open'] !== null ? number_format((int)$case['days_open']) : '—' ?></div>
            </div>
            <div class="meta-cell">
                <div class="meta-cell-label">Resolution Type</div>
                <div class="meta-cell-value"><?= $case['resolution_type'] ? h(ucwords(str_replace('_', ' ', $case['resolution_type']))) : '—' ?></div>
            </div>
            <?php if (!empty($case['complainant_type'])): ?>
            <div class="meta-cell">
                <div class="meta-cell-label">Complainant Type</div>
                <div class="meta-cell-value"><?= h($case['complainant_type']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- District Context -->
        <?php if ($districtContext && isset($districtContext['rate_per_100'])): ?>
        <div class="district-context" data-animate>
            At the time this case was filed, <strong><?= h($case['org_name'] ?? 'this district') ?></strong> had a restraint rate of
            <strong class="context-stat"><?= number_format($districtContext['rate_per_100'], 1) ?> per 100</strong> students
            <?php if (isset($districtContext['state_avg'])): ?>
            (state average: <strong><?= number_format($districtContext['state_avg'], 1) ?> per 100</strong>)
            <?php endif; ?>.
        </div>
        <?php endif; ?>

        <!-- Allegations -->
        <?php if (!empty($allegations)): ?>
        <div class="card" data-animate>
            <h3 class="card-title">Allegations <span class="count"><?= is_array($allegations) && !array_is_list($allegations) ? 1 : count($allegations) ?></span></h3>
            <ul class="allegations-list">
                <?php
                // Handle both formats: array of strings, or object with category/subcategory
                if (!array_is_list($allegations)):
                    // Single object: {category: ..., subcategory: ...}
                ?>
                <li class="allegation-item">
                    <span class="allegation-cat"><?= h($allegations['category'] ?? 'Uncategorized') ?></span>
                    <span class="allegation-text"><?= h($allegations['subcategory'] ?? ($allegations['description'] ?? '')) ?></span>
                </li>
                <?php else: ?>
                    <?php foreach ($allegations as $a): ?>
                    <li class="allegation-item">
                        <?php if (is_array($a)): ?>
                        <span class="allegation-cat"><?= h($a['category'] ?? 'Uncategorized') ?></span>
                        <span class="allegation-text"><?= h($a['subcategory'] ?? ($a['description'] ?? ($a['text'] ?? ''))) ?></span>
                        <?php else: ?>
                        <span class="allegation-text"><?= h($a) ?></span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Case Description -->
        <?php if (!empty($case['case_description'])): ?>
        <div class="card" data-animate>
            <h3 class="card-title">Description</h3>
            <div class="prose"><?= nl2br(h($case['case_description'])) ?></div>
        </div>
        <?php endif; ?>

        <!-- Timeline -->
        <div class="card" data-animate>
            <h3 class="card-title">Timeline <span class="count"><?= count($events) ?></span></h3>
            <?= (new \App\Components\Timeline($events))->render() ?>
        </div>

        <!-- Findings -->
        <?php if (!empty($findings)): ?>
        <div class="card" data-animate>
            <h3 class="card-title">Findings <span class="count"><?= count($findings) ?></span></h3>
            <div class="findings-grid">
                <?php foreach ($findings as $f): ?>
                <div class="finding-card">
                    <div class="finding-card-header">
                        <span class="finding-num">#<?= (int)$f['finding_number'] ?></span>
                        <?php if (!empty($f['allegation_category'])): ?>
                        <span class="finding-category"><?= h($f['allegation_category']) ?></span>
                        <?php endif; ?>
                        <?= findingBadge($f['finding']) ?>
                    </div>
                    <?php if (!empty($f['finding_detail'])): ?>
                    <p class="finding-detail"><?= nl2br(h($f['finding_detail'])) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($f['cited_regulation'])): ?>
                    <p class="finding-meta"><strong>Regulation:</strong> <?= h($f['cited_regulation']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($f['corrective_action_ordered'])): ?>
                    <div class="finding-ca">
                        <strong>
                            Corrective Action
                            <span class="ca-status-badge ca-<?= h($f['corrective_action_status'] ?? 'pending') ?>"><?= h(ucwords(str_replace('_', ' ', $f['corrective_action_status'] ?? 'pending'))) ?></span>
                        </strong>
                        <p><?= nl2br(h($f['corrective_action_ordered'])) ?></p>
                        <?php if (!empty($f['corrective_action_deadline'])): ?>
                        <p class="finding-meta"><strong>Due:</strong> <?= format_date($f['corrective_action_deadline']) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Finding Substantiation Chart -->
        <?php if (!empty($findingStats) && count($findingStats) >= 1): ?>
        <div class="card" data-animate>
            <h3 class="card-title">Finding Outcomes by Category</h3>
            <div class="prs-chart-wrap">
                <canvas id="findingChart"></canvas>
            </div>
            <script>
            (function(){
                var ctx = document.getElementById('findingChart');
                var categories = <?= json_encode(array_column($findingStats, 'category')) ?>;
                var substantiated = <?= json_encode(array_column($findingStats, 'substantiated')) ?>;
                var unsubstantiated = <?= json_encode(array_column($findingStats, 'unsubstantiated')) ?>;
                var partial = <?= json_encode(array_column($findingStats, 'partially_substantiated')) ?>;
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: categories,
                        datasets: [
                            { label: 'Substantiated', data: substantiated, backgroundColor: '#ef4444', borderRadius: 3 },
                            { label: 'Partially Substantiated', data: partial, backgroundColor: '#f59e0b', borderRadius: 3 },
                            { label: 'Unsubstantiated', data: unsubstantiated, backgroundColor: '#22c55e', borderRadius: 3 }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { color: '#a0a0a0', font: { size: 12 }, padding: 16, usePointStyle: true }
                            }
                        },
                        scales: {
                            x: {
                                stacked: true,
                                ticks: { color: '#767676', font: { size: 11 }, stepSize: 1 },
                                grid: { color: '#2a2a2a' }
                            },
                            y: {
                                stacked: true,
                                ticks: { color: '#a0a0a0', font: { size: 11 } },
                                grid: { display: false }
                            }
                        }
                    }
                });
            })();
            </script>
        </div>
        <?php endif; ?>

        <!-- Documents — grouped by link_type -->
        <?php if (!empty($documentsByType)): ?>
        <div class="card" data-animate>
            <h3 class="card-title">Documents <span class="count"><?= count($documents) ?></span></h3>
            <?php foreach ($documentsByType as $type => $docs): ?>
            <div class="doc-group">
                <div class="doc-group-label"><?= h(ucwords(str_replace('_', ' ', $type))) ?></div>
                <ul class="doc-link-list">
                    <?php foreach ($docs as $d): ?>
                    <li>
                        <a href="<?= h($d['file_url'] ?: '/documents/' . $d['id']) ?>" class="doc-link">
                            <span class="doc-icon"><?= docIcon($d['file_mime'] ?? '') ?></span>
                            <span class="doc-name"><?= h($d['title'] ?: $d['file_name'] ?: 'Document') ?></span>
                            <?php if (!empty($d['document_date'])): ?>
                            <span class="doc-date"><?= format_date($d['document_date']) ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Findings Summary -->
        <?php if (!empty($case['findings_summary'])): ?>
        <div class="card" data-animate>
            <h3 class="card-title">Summary</h3>
            <div class="prose"><?= nl2br(h($case['findings_summary'])) ?></div>
        </div>
        <?php endif; ?>

        <!-- Corrective Actions (case-level) -->
        <?php if (!empty($case['corrective_actions'])): ?>
        <div class="card" data-animate>
            <h3 class="card-title">Corrective Actions</h3>
            <div class="prose"><?= nl2br(h($case['corrective_actions'])) ?></div>
        </div>
        <?php endif; ?>

        <!-- Related Cases -->
        <?php if (!empty($relatedCases)): ?>
        <div class="card" data-animate>
            <h3 class="card-title">Related Cases <span class="count"><?= count($relatedCases) ?></span></h3>
            <ul class="related-cases-list">
                <?php foreach ($relatedCases as $rc): ?>
                <li class="related-case-item">
                    <span class="related-case-num"><a href="/prs/<?= h($rc['prs_number']) ?>"><?= h($rc['prs_number']) ?></a></span>
                    <span class="related-case-title" title="<?= h($rc['case_title'] ?? '') ?>"><?= h($rc['case_title'] ?? $rc['prs_number']) ?></span>
                    <span class="related-case-date"><?= format_date($rc['filing_date']) ?></span>
                    <?= prsStatusBadge($rc['current_status']) ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <p class="back-link" data-animate><a href="/prs">&larr; Back to PRS Case List</a></p>
    </div>
</section>

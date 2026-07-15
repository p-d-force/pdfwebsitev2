<section class="section">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">PRS Tracker</span>
            <h2 class="section-title">Problem Resolution System Cases</h2>
            <p class="section-subtitle">Track PRS complaints filed with DESE's Problem Resolution System across Massachusetts school districts.</p>
        </div>

        <form method="GET" class="filter-bar" data-animate>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="filed" <?= ($filters['status'] ?? '') === 'filed' ? 'selected' : '' ?>>Filed</option>
                    <option value="accepted" <?= ($filters['status'] ?? '') === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                    <option value="investigating" <?= ($filters['status'] ?? '') === 'investigating' ? 'selected' : '' ?>>Investigating</option>
                    <option value="findings" <?= ($filters['status'] ?? '') === 'findings' ? 'selected' : '' ?>>Findings Issued</option>
                    <option value="closed" <?= ($filters['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed</option>
                    <option value="appealed" <?= ($filters['status'] ?? '') === 'appealed' ? 'selected' : '' ?>>Appealed</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Year</label>
                <select name="year" class="form-select" onchange="this.form.submit()">
                    <option value="">All Years</option>
                    <?php foreach ($years as $y): ?>
                    <option value="<?= h($y['yr']) ?>" <?= ($filters['year'] ?? '') === (string)$y['yr'] ? 'selected' : '' ?>><?= h($y['yr']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">District</label>
                <input type="text" name="district" class="form-input" placeholder="District code..." value="<?= h($filters['district'] ?? '') ?>" style="width:160px;">
            </div>
            <div class="form-group">
                <label class="form-label">Keyword</label>
                <input type="text" name="q" class="form-input" placeholder="Search..." value="<?= h($filters['q'] ?? '') ?>">
            </div>
            <div class="form-group" style="align-self:flex-end;">
                <button type="submit" class="btn btn-primary">Filter</button>
                <?php if (!empty($filters)): ?>
                <a href="/prs" class="btn btn-outline" style="margin-left:8px;">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (empty($cases)): ?>
            <div class="empty-state" data-animate>
                <h3>No PRS cases found</h3>
                <p>Try adjusting your filters or <a href="/prs/analytics">view the analytics dashboard</a>.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;" data-animate>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>PRS #</th>
                            <th>Title</th>
                            <th>District</th>
                            <th>Status</th>
                            <th>Filed</th>
                            <th>Days Open</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cases as $c): ?>
                        <tr style="cursor:pointer;" onclick="location.href='/prs/<?= h($c['prs_number']) ?>'">
                            <td><strong><?= h($c['prs_number']) ?></strong></td>
                            <td><?= h(truncate($c['case_title'] ?? 'Untitled', 40)) ?></td>
                            <td>
                                <a href="/prs/district/<?= h($c['org_code']) ?>" onclick="event.stopPropagation();"><?= h($c['district_name']) ?></a>
                            </td>
                            <td><?= prsStatusBadge($c['current_status']) ?></td>
                            <td><?= format_date($c['filing_date']) ?></td>
                            <td><span class="num"><?= $c['total_days_open'] !== null ? number_format((int)$c['total_days_open']) : '—' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?= pagination_links($pagination, '/prs') ?>
        <?php endif; ?>
    </div>
</section>

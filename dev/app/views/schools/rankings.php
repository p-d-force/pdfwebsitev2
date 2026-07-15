<section class="section">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">Rankings</span>
            <h1 class="section-title">School Rankings</h1>
            <p class="section-subtitle"><?= number_format($total) ?> schools ranked by restraint rate in <?= h($latest_year) ?>. Use filters to narrow by grade span or district.</p>
        </div>

        <!-- Filter Bar -->
        <form class="school-filter-bar ranking-filter-bar" method="GET" action="/schools/rankings" data-animate>
            <div class="filter-row">
                <div class="filter-group">
                    <label for="filter-district">District</label>
                    <select name="district" id="filter-district">
                        <option value="">All Districts</option>
                        <?php foreach ($districts as $d): ?>
                        <option value="<?= h($d['org_code']) ?>" <?= ($filters['district'] ?? '') === $d['org_code'] ? 'selected' : '' ?>><?= h($d['org_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter-grade">Grade Span</label>
                    <select name="grade" id="filter-grade">
                        <option value="">All Grades</option>
                        <option value="PK-5" <?= ($filters['grade'] ?? '') === 'PK-5' ? 'selected' : '' ?>>Elementary (PK–5)</option>
                        <option value="6-8" <?= ($filters['grade'] ?? '') === '6-8' ? 'selected' : '' ?>>Middle (6–8)</option>
                        <option value="9-12" <?= ($filters['grade'] ?? '') === '9-12' ? 'selected' : '' ?>>High (9–12)</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter-sort">Sort By</label>
                    <select name="sort" id="filter-sort">
                        <option value="restraint_rate" <?= ($filters['sort'] ?? 'restraint_rate') === 'restraint_rate' ? 'selected' : '' ?>>Restraint Rate</option>
                        <option value="enrollment" <?= ($filters['sort'] ?? '') === 'enrollment' ? 'selected' : '' ?>>Enrollment</option>
                        <option value="attendance" <?= ($filters['sort'] ?? '') === 'attendance' ? 'selected' : '' ?>>Attendance Rate</option>
                        <option value="name" <?= ($filters['sort'] ?? '') === 'name' ? 'selected' : '' ?>>School Name</option>
                    </select>
                    <select name="order" class="order-toggle">
                        <option value="desc" <?= ($filters['order'] ?? 'desc') === 'desc' ? 'selected' : '' ?>>Highest First</option>
                        <option value="asc" <?= ($filters['order'] ?? '') === 'asc' ? 'selected' : '' ?>>Lowest First</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter-view">Quick View</label>
                    <select name="view" id="filter-view">
                        <option value="">All Schools</option>
                        <option value="top10" <?= ($filters['view'] ?? '') === 'top10' ? 'selected' : '' ?>>Top 10</option>
                        <option value="bottom10" <?= ($filters['view'] ?? '') === 'bottom10' ? 'selected' : '' ?>>Bottom 10</option>
                    </select>
                </div>
                <div class="filter-group filter-actions">
                    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                    <?php if (!empty($filters['district']) || !empty($filters['grade']) || !empty($filters['view'])): ?>
                    <a href="/schools/rankings" class="btn btn-ghost btn-sm">Clear</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- Rankings Table -->
        <?php if (empty($rankings)): ?>
            <div class="empty-state"><h3>No schools found</h3><p>Try adjusting your filters or check back as data coverage expands.</p></div>
        <?php else: ?>
        <div class="ranking-table-wrapper table-responsive" data-animate>
            <table class="ranking-table">
                <thead>
                    <tr>
                        <th class="col-rank">#</th>
                        <th class="col-school">School</th>
                        <th class="col-district">District</th>
                        <th class="col-enrollment num">Enrollment</th>
                        <th class="col-rate num">Restraint Rate <span class="th-unit">per 100</span></th>
                        <th class="col-attendance num">Attendance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rankings as $r): ?>
                    <tr>
                        <td class="col-rank"><?= $r['rank'] ?></td>
                        <td class="col-school">
                            <a href="/schools/<?= h(strtolower($r['org_code'])) ?>/"><?= h($r['org_name']) ?></a>
                            <?php if (!empty($r['grade_span'])): ?>
                            <span class="ranking-grade"><?= h($r['grade_span']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="col-district">
                            <a href="/districts/<?= h(strtolower($r['district_code'])) ?>/"><?= h($r['district_name']) ?></a>
                        </td>
                        <td class="col-enrollment num"><?= number_format((int)($r['enrollment'] ?? 0)) ?></td>
                        <td class="col-rate num">
                            <span class="rate-badge <?= ($r['restraint_rate'] ?? 0) > 10 ? 'rate-high' : (($r['restraint_rate'] ?? 0) > 5 ? 'rate-medium' : 'rate-low') ?>">
                                <?= number_format($r['restraint_rate'] ?? 0, 2) ?>
                            </span>
                        </td>
                        <td class="col-attendance num"><?= isset($r['attendance_rate']) ? number_format($r['attendance_rate'], 1) . '%' : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= pagination_links($pagination, '/schools/rankings' . $qs) ?>
        <?php endif; ?>
    </div>
</section>

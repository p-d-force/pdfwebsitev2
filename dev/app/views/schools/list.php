<section class="section">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">Schools</span>
            <h1 class="section-title">Massachusetts Public Schools</h1>
            <p class="section-subtitle">Browse <?= number_format($total) ?> schools tracked by Parent Data Force. View restraint data, enrollment, and demographics by school.</p>
        </div>

        <!-- Filter Bar -->
        <form class="school-filter-bar" method="GET" action="/schools" data-animate>
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
                        <option value="PK-5" <?= ($filters['grade'] ?? '') === 'PK-5' ? 'selected' : '' ?>>PK–5</option>
                        <option value="6-8" <?= ($filters['grade'] ?? '') === '6-8' ? 'selected' : '' ?>>6–8</option>
                        <option value="9-12" <?= ($filters['grade'] ?? '') === '9-12' ? 'selected' : '' ?>>9–12</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter-type">School Type</label>
                    <select name="type" id="filter-type">
                        <option value="">All Types</option>
                        <option value="Public School" <?= ($filters['type'] ?? '') === 'Public School' ? 'selected' : '' ?>>Public School</option>
                        <option value="Charter School" <?= ($filters['type'] ?? '') === 'Charter School' ? 'selected' : '' ?>>Charter School</option>
                        <option value="Collaborative School" <?= ($filters['type'] ?? '') === 'Collaborative School' ? 'selected' : '' ?>>Collaborative</option>
                        <option value="Approved Special Education School" <?= ($filters['type'] ?? '') === 'Approved Special Education School' ? 'selected' : '' ?>>Approved SPED</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter-sort">Sort By</label>
                    <select name="sort" id="filter-sort">
                        <option value="name" <?= ($filters['sort'] ?? 'name') === 'name' ? 'selected' : '' ?>>Name</option>
                        <option value="enrollment" <?= ($filters['sort'] ?? '') === 'enrollment' ? 'selected' : '' ?>>Enrollment</option>
                        <option value="restraint_rate" <?= ($filters['sort'] ?? '') === 'restraint_rate' ? 'selected' : '' ?>>Restraint Count</option>
                    </select>
                    <select name="order" class="order-toggle">
                        <option value="asc" <?= ($filters['order'] ?? 'asc') === 'asc' ? 'selected' : '' ?>>Ascending</option>
                        <option value="desc" <?= ($filters['order'] ?? '') === 'desc' ? 'selected' : '' ?>>Descending</option>
                    </select>
                </div>
                <div class="filter-group filter-actions">
                    <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                    <?php if (!empty($filters['district']) || !empty($filters['grade']) || !empty($filters['type'])): ?>
                    <a href="/schools" class="btn btn-ghost btn-sm">Clear</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- School Cards Grid -->
        <?php if (empty($schools)): ?>
            <div class="empty-state"><h3>No schools found</h3><p>Try adjusting your filters or check back as we expand our data coverage.</p></div>
        <?php else: ?>
            <div class="resources-grid school-grid" data-animate>
                <?php foreach ($schools as $s): ?>
                <a href="/schools/<?= h(strtolower($s['org_code'])) ?>/" class="resource-card school-card">
                    <div class="school-card-header">
                        <span class="school-card-name"><?= h($s['org_name']) ?></span>
                        <?php if (!empty($s['org_type'])): ?>
                        <span class="badge badge-school-type"><?= h($s['org_type']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="school-card-meta">
                        <?php if (!empty($s['district_name'])): ?>
                        <span class="school-card-district"><?= h($s['district_name']) ?></span>
                        <?php endif; ?>
                        <span class="school-card-detail">
                            <?php if (!empty($s['town'])): ?><?= h($s['town']) ?>, MA<?php endif; ?>
                            <?php if (!empty($s['grade_span'])): ?> &middot; <?= h($s['grade_span']) ?><?php endif; ?>
                        </span>
                    </div>
                    <div class="school-card-stats">
                        <?php if (isset($s['enrollment'])): ?>
                        <div class="school-stat">
                            <span class="school-stat-value"><?= number_format($s['enrollment']) ?></span>
                            <span class="school-stat-label">Enrolled</span>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($s['total_restraints'])): ?>
                        <div class="school-stat">
                            <span class="school-stat-value"><?= number_format($s['total_restraints']) ?></span>
                            <span class="school-stat-label">Restraints</span>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($s['students_restrained'])): ?>
                        <div class="school-stat">
                            <span class="school-stat-value"><?= number_format($s['students_restrained']) ?></span>
                            <span class="school-stat-label">Students</span>
                        </div>
                        <?php endif; ?>
                        <?php if (!isset($s['enrollment']) && !isset($s['total_restraints'])): ?>
                        <div class="school-stat">
                            <span class="school-stat-value">—</span>
                            <span class="school-stat-label">No data</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?= pagination_links($pagination, '/schools' . $qs) ?>
        <?php endif; ?>
    </div>
</section>

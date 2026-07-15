<section class="section">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">Compare</span>
            <h2 class="section-title">District Comparison Tool</h2>
            <p class="section-subtitle">Compare restraint data across multiple Massachusetts districts.</p>
        </div>

        <form method="GET" action="/compare" class="filter-bar" data-animate>
            <div class="form-group" style="min-width:250px;flex:1;">
                <label class="form-label">Select Districts</label>
                <select name="districts[]" class="form-select" multiple style="min-height:150px;">
                    <?php foreach ($allDistricts as $d): ?>
                    <option value="<?= h($d['org_code']) ?>" <?= in_array($d['org_code'], $selectedCodes ?? []) ? 'selected' : '' ?>><?= h($d['org_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">School Year</label>
                <select name="school_year" class="form-select">
                    <?php foreach ($years as $y): ?>
                    <option value="<?= h($y) ?>" <?= ($selectedYear ?? '') === $y ? 'selected' : '' ?>><?= h($y) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Compare</button>
        </form>

        <?php if (!empty($comparisonData)): ?>
        <div class="compare-results" data-animate>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>District</th>
                            <th>Enrollment</th>
                            <th>Students Restrained</th>
                            <th>Total Restraints</th>
                            <th>Injuries</th>
                            <th>Rate per 100</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comparisonData as $row): ?>
                        <tr>
                            <td><?= h($row['org_name']) ?></td>
                            <td class="num"><?= number_format((int)($row['enrollment'] ?? 0)) ?></td>
                            <td class="num"><?= number_format((int)($row['students_restrained'] ?? 0)) ?></td>
                            <td class="num"><?= number_format((int)($row['total_restraints'] ?? 0)) ?></td>
                            <td class="num"><?= number_format((int)($row['total_injuries'] ?? 0)) ?></td>
                            <td class="num"><?= ($row['enrollment'] ?? 0) > 0 ? number_format(((int)($row['total_restraints'] ?? 0) / (int)$row['enrollment']) * 100, 1) : '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Radar Comparison Chart -->
        <?php if (!empty($comparisonData) && count($comparisonData) >= 2): ?>
        <div class="chart-card" data-animate style="margin-top:2rem;max-width:600px;margin-left:auto;margin-right:auto;">
            <h3 style="text-align:center;">Multi-Metric Comparison</h3>
            <?php
            $radar = new App\Components\Chart('compareRadar', 'radar');
            $radar->setLabels(['Restraint Rate', 'Enrollment (K)', 'Students Restrained %', 'Injury Rate']);
            $colors = ['#ff5a1f','#60a5fa','#22c55e','#f59e0b','#a78bfa'];
            $ci = 0;
            foreach ($comparisonData as $row):
                $enr = max((int)($row['enrollment'] ?? 0), 1);
                $radar->addDataset($row['org_name'], [
                    round(((int)($row['total_restraints'] ?? 0) / $enr) * 100, 1),
                    round($enr / 1000, 1),
                    round(((int)($row['students_restrained'] ?? 0) / $enr) * 100, 1),
                    round(((int)($row['total_injuries'] ?? 0) / $enr) * 100, 1),
                ], [
                    'borderColor' => $colors[$ci % count($colors)],
                    'backgroundColor' => 'transparent',
                ]);
                $ci++;
            endforeach;
            $radar->setHeight(400);
            echo $radar->render();
            ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

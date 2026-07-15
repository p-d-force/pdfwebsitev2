<section class="section">
    <div class="container">
        <p class="breadcrumb" data-animate><a href="/prs">PRS Cases</a> &rsaquo; Cross-Reference</p>

        <div class="section-header" data-animate>
            <span class="section-tag">Cross-Reference</span>
            <h2 class="section-title">PRS &larr;&rarr; DESE Data Correlation</h2>
            <p class="section-subtitle">Compare PRS complaint filings with restraint rates, discipline, and attendance data at district and statewide levels.</p>
        </div>

        <!-- District Selector -->
        <form method="get" action="/prs/cross-ref" class="filter-bar" data-animate style="margin-bottom:2rem;">
            <select name="district" onchange="this.form.submit()" style="background:var(--surface);color:var(--text-primary);border:1px solid var(--border);padding:0.5rem 1rem;border-radius:6px;font-size:0.9rem;min-width:300px;">
                <option value="">— Statewide Correlation (all districts) —</option>
                <?php foreach ($districts as $d): ?>
                <option value="<?= h($d['org_code']) ?>" <?= ($districtCode === $d['org_code']) ? 'selected' : '' ?>><?= h($d['org_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($selectedDistrict): ?>
            <!-- Single District: Merged Data -->
            <div class="card-grid" data-animate>
                <div class="stat-card">
                    <span class="stat-label">District</span>
                    <span class="stat-value"><?= h($selectedDistrict['org_name']) ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Code</span>
                    <span class="stat-value"><?= h($selectedDistrict['org_code']) ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Total PRS Cases</span>
                    <span class="stat-value"><?= number_format(array_sum(array_column($mergedData, 'prs_count'))) ?></span>
                </div>
            </div>

            <?php if (!empty($chartHtml)): ?>
            <div class="dashboard-panel dashboard-panel--wide" data-animate>
                <h3>PRS Cases vs. Restraints Over Time</h3>
                <?= $chartHtml ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($mergedData)): ?>
            <div style="overflow-x:auto;" data-animate>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>PRS Cases</th>
                            <th>Restraints</th>
                            <th>Enrollment</th>
                            <th>Restraint Rate (per 1K)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mergedData as $r): ?>
                        <tr>
                            <td><strong><?= h($r['year']) ?></strong></td>
                            <td><span class="num"><?= number_format($r['prs_count']) ?></span></td>
                            <td><span class="num"><?= number_format($r['restraints']) ?></span></td>
                            <td><span class="num"><?= number_format($r['enrollment']) ?></span></td>
                            <td><span class="num"><?= $r['enrollment'] > 0 ? number_format($r['restraints'] * 1000 / $r['enrollment'], 1) : '—' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if (!empty($correlationTable)): ?>
            <div class="dashboard-panel" data-animate>
                <h3>Local Correlation</h3>
                <table class="data-table">
                    <thead><tr><th>Metric X</th><th>Metric Y</th><th>Pearson r</th><th>N</th></tr></thead>
                    <tbody>
                        <?php foreach ($correlationTable as $c): ?>
                        <tr>
                            <td><?= h($c['metric_x']) ?></td>
                            <td><?= h($c['metric_y']) ?></td>
                            <td><span class="num" style="color:<?= abs($c['r']) > 0.5 ? ($c['r'] > 0 ? '#22c55e' : '#ef4444') : '#f59e0b' ?>"><?= number_format($c['r'], 3) ?></span></td>
                            <td><?= $c['n'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        <?php elseif (!empty($scatterPoints)): ?>
            <!-- Statewide: Scatter Correlation -->
            <?php if (!empty($chartHtml)): ?>
            <div class="dashboard-panel dashboard-panel--wide" data-animate>
                <h3>PRS Filing Rate vs. Restraint Rate (per 1,000 Students)</h3>
                <p style="color:var(--text-secondary);font-size:0.85rem;margin-bottom:0.5rem;">Each dot = one Massachusetts school district. <?= number_format(count($scatterPoints)) ?> districts shown.</p>
                <?= $chartHtml ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($correlationTable)): ?>
            <div class="dashboard-panel" data-animate style="margin-top:1.5rem;">
                <h3>Correlation Matrix</h3>
                <p style="color:var(--text-secondary);font-size:0.85rem;margin-bottom:1rem;">
                    Pearson r: <span style="color:#22c55e">positive correlation</span> &middot;
                    <span style="color:#ef4444">negative correlation</span> &middot;
                    <span style="color:#f59e0b">weak/no correlation</span>
                </p>
                <table class="data-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Metric X</th>
                            <th>Metric Y</th>
                            <th>Pearson r</th>
                            <th>Interpretation</th>
                            <th>N</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($correlationTable as $c):
                            $absR = abs($c['r']);
                            if ($absR > 0.7) $interp = 'Strong';
                            elseif ($absR > 0.5) $interp = 'Moderate';
                            elseif ($absR > 0.3) $interp = 'Weak';
                            else $interp = 'Negligible';
                            $interp .= $c['r'] > 0 ? ' positive' : ' negative';
                            $color = $absR > 0.5 ? ($c['r'] > 0 ? '#22c55e' : '#ef4444') : '#f59e0b';
                        ?>
                        <tr>
                            <td><?= h($c['metric_x']) ?></td>
                            <td><?= h($c['metric_y']) ?></td>
                            <td><span class="num" style="color:<?= $color ?>;font-weight:600;"><?= number_format($c['r'], 3) ?></span></td>
                            <td><span style="color:<?= $color ?>;"><?= $interp ?></span></td>
                            <td><?= $c['n'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Additional Scatters: Discipline & Attendance -->
            <?php
            $discScatter = [];
            $attScatter = [];
            foreach ($scatterPoints as $p) {
                if ($p['discipline_pct'] > 0) {
                    $discScatter[] = ['x' => $p['discipline_pct'], 'y' => $p['prs_rate'], 'name' => $p['name']];
                }
                if ($p['chronic_absent'] > 0) {
                    $attScatter[] = ['x' => $p['chronic_absent'], 'y' => $p['prs_rate'], 'name' => $p['name']];
                }
            }
            ?>
            <?php if (!empty($discScatter)): ?>
            <div class="dashboard-panel dashboard-panel--wide" data-animate style="margin-top:1.5rem;">
                <h3>PRS Filing Rate vs. Discipline Rate</h3>
                <?php
                $sc2 = new App\Components\Chart('cross-ref-disc-scatter', 'scatter');
                $sc2->setLabels([]);
                $sc2->addDataset('Districts', $discScatter, [
                    'backgroundColor'   => '#f59e0b',
                    'borderColor'       => '#f59e0b',
                    'pointRadius'       => 5,
                    'pointHoverRadius'  => 8,
                ]);
                $sc2->setOption('scales.x.title.text', 'Students Disciplined %');
                $sc2->setOption('scales.x.title.display', true);
                $sc2->setOption('scales.y.title.text', 'PRS Filings per 1,000 Students');
                $sc2->setOption('scales.y.title.display', true);
                $sc2->setHeight(380);
                echo $sc2->render();
                ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($attScatter)): ?>
            <div class="dashboard-panel dashboard-panel--wide" data-animate style="margin-top:1.5rem;">
                <h3>PRS Filing Rate vs. Chronic Absenteeism</h3>
                <?php
                $sc3 = new App\Components\Chart('cross-ref-att-scatter', 'scatter');
                $sc3->setLabels([]);
                $sc3->addDataset('Districts', $attScatter, [
                    'backgroundColor'   => '#a78bfa',
                    'borderColor'       => '#a78bfa',
                    'pointRadius'       => 5,
                    'pointHoverRadius'  => 8,
                ]);
                $sc3->setOption('scales.x.title.text', 'Chronic Absenteeism %');
                $sc3->setOption('scales.x.title.display', true);
                $sc3->setOption('scales.y.title.text', 'PRS Filings per 1,000 Students');
                $sc3->setOption('scales.y.title.display', true);
                $sc3->setHeight(380);
                echo $sc3->render();
                ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty-state" data-animate>
                <h3>Select a district or view statewide correlations</h3>
                <p>Choose a district from the dropdown above to see merged PRS and DESE data, or leave it blank for the statewide scatter plot.</p>
            </div>
        <?php endif; ?>

        <p class="back-link" data-animate style="margin-top:2rem;"><a href="/prs/analytics">&larr; Back to PRS Analytics</a></p>
    </div>
</section>

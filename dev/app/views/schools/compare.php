<section class="section">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">Compare</span>
            <h1 class="section-title">School Comparison</h1>
            <p class="section-subtitle">Select up to 5 Massachusetts public schools to compare restraint data side-by-side.</p>
        </div>

        <!-- School Selector -->
        <div class="compare-selector" data-animate>
            <form method="GET" action="/schools/compare" class="compare-form">
                <div class="compare-search">
                    <input type="text"
                           id="school-search"
                           class="compare-search-input"
                           placeholder="Search by school name..."
                           autocomplete="off"
                           aria-label="Search schools to compare">
                    <div id="search-results" class="compare-search-results"></div>
                </div>
                <div id="selected-schools" class="compare-selected">
                    <?php foreach ($schools as $s): ?>
                    <span class="compare-chip" data-code="<?= h(strtolower($s['org_code'])) ?>">
                        <?= h($s['org_name']) ?>
                        <button type="button" class="compare-chip-remove" aria-label="Remove <?= h($s['org_name']) ?>">&times;</button>
                    </span>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="codes" id="compare-codes" value="<?= h(implode(',', $codes)) ?>">
                <button type="submit" class="btn btn-primary" id="compare-btn" <?= count($schools) < 2 ? 'disabled' : '' ?>>
                    Compare Schools (<?= count($schools) ?>/5)
                </button>
            </form>
        </div>

        <?php if (!empty($schools)): ?>
        <!-- Best Performer Summary -->
        <div class="compare-best" data-animate>
            <h3>Best Performer Summary</h3>
            <div class="compare-best-grid">
                <div class="compare-best-card">
                    <span class="compare-best-label">Lowest Restraint Rate</span>
                    <span class="compare-best-value"><?= h($best_performer['restraint_rate']['name']) ?></span>
                    <span class="compare-best-stat"><?= number_format($best_performer['restraint_rate']['value'], 2) ?> per 100</span>
                </div>
                <div class="compare-best-card">
                    <span class="compare-best-label">Lowest Injury Rate</span>
                    <span class="compare-best-value"><?= h($best_performer['injury_rate']['name']) ?></span>
                    <span class="compare-best-stat"><?= number_format($best_performer['injury_rate']['value'], 2) ?> per 100</span>
                </div>
                <div class="compare-best-card">
                    <span class="compare-best-label">Fewest Students Restrained</span>
                    <span class="compare-best-value"><?= h($best_performer['students_restrained_pct']['name']) ?></span>
                    <span class="compare-best-stat"><?= number_format($best_performer['students_restrained_pct']['value'], 2) ?>%</span>
                </div>
            </div>
        </div>

        <!-- Radar Chart -->
        <div class="school-chart-panel radar-chart-container" data-animate>
            <h3>Performance Radar</h3>
            <div class="school-chart" id="radar-chart-wrapper">
                <canvas id="compare-radar-chart" height="400"></canvas>
            </div>
            <script>
            (function() {
                var canvas = document.getElementById('compare-radar-chart');
                if (!canvas || typeof Chart === 'undefined') return;
                new Chart(canvas, {
                    type: 'radar',
                    data: {
                        labels: <?= json_encode($radar_labels) ?>,
                        datasets: <?= json_encode($radar_datasets) ?>
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            r: {
                                beginAtZero: true,
                                max: 100,
                                grid: { color: 'rgba(42,42,42,0.5)' },
                                angleLines: { color: 'rgba(42,42,42,0.5)' },
                                pointLabels: { color: '#a0a0a0', font: { family: 'Inter', size: 11 } },
                                ticks: { color: '#767676', backdropColor: 'transparent', stepSize: 20 }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { color: '#a0a0a0', font: { family: 'Inter', size: 11 }, padding: 16 }
                            },
                            tooltip: {
                                backgroundColor: '#1d1d1d',
                                titleColor: '#e0e0e0',
                                bodyColor: '#a0a0a0',
                                borderColor: '#2a2a2a',
                                borderWidth: 1
                            }
                        }
                    }
                });
            })();
            </script>
        </div>

        <!-- Side-by-Side Comparison Table -->
        <div class="compare-table-wrapper" data-animate>
            <h3>Side-by-Side Comparison</h3>
            <div class="table-responsive">
                <table class="compare-table">
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <?php foreach ($schools as $s): ?>
                            <th class="compare-col-header">
                                <a href="/schools/<?= h(strtolower($s['org_code'])) ?>/"><?= h($s['org_name']) ?></a>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>District</td>
                            <?php foreach ($schools as $s): ?>
                            <td><a href="/districts/<?= h(strtolower($s['district_code'])) ?>/"><?= h($s['district_name']) ?></a></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Grade Span</td>
                            <?php foreach ($schools as $s): ?>
                            <td><?= h($s['grade_span'] ?? '—') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Enrollment</td>
                            <?php foreach ($schools as $s): ?>
                            <td><?= number_format((int)($s['enrollment'] ?? 0)) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Total Restraints</td>
                            <?php foreach ($schools as $s): ?>
                            <td><?= number_format((int)($s['total_restraints'] ?? 0)) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Restraint Rate (per 100)</td>
                            <?php foreach ($schools as $s): ?>
                            <td class="compare-rate"><?= number_format($s['restraint_rate'] ?? 0, 2) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Students Restrained (%)</td>
                            <?php foreach ($schools as $s): ?>
                            <td><?= number_format($s['students_restrained_pct'] ?? 0, 2) ?>%</td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Injuries</td>
                            <?php foreach ($schools as $s): ?>
                            <td><?= number_format((int)($s['total_injuries'] ?? 0)) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Injury Rate (per 100)</td>
                            <?php foreach ($schools as $s): ?>
                            <td><?= number_format($s['injury_rate'] ?? 0, 2) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Title I Status</td>
                            <?php foreach ($schools as $s): ?>
                            <td><?= h($s['title_1_status'] ?? '—') ?></td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
(function() {
    var searchInput = document.getElementById('school-search');
    var resultsDiv = document.getElementById('search-results');
    var selectedDiv = document.getElementById('selected-schools');
    var codesInput = document.getElementById('compare-codes');
    var compareBtn = document.getElementById('compare-btn');
    var selected = <?= json_encode($codes) ?>;
    var selectedNames = <?= json_encode(array_map(function($s) { return $s['org_name']; }, $schools)) ?>;
    var selectedCodes = <?= json_encode(array_map('strtolower', $codes)) ?>;

    function updateUI() {
        var html = '';
        for (var i = 0; i < selected.length; i++) {
            html += '<span class="compare-chip" data-code="' + selected[i].toLowerCase() + '">'
                 + selectedNames[i]
                 + '<button type="button" class="compare-chip-remove" aria-label="Remove ' + selectedNames[i] + '">&times;</button>'
                 + '</span>';
        }
        selectedDiv.innerHTML = html;
        codesInput.value = selected.join(',');
        compareBtn.textContent = 'Compare Schools (' + selected.length + '/5)';
        compareBtn.disabled = selected.length < 2;
        bindRemoveButtons();
    }

    function bindRemoveButtons() {
        var buttons = selectedDiv.querySelectorAll('.compare-chip-remove');
        buttons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var chip = btn.parentElement;
                var code = chip.getAttribute('data-code');
                var idx = selected.findIndex(function(c) { return c.toLowerCase() === code; });
                if (idx >= 0) {
                    selected.splice(idx, 1);
                    selectedNames.splice(idx, 1);
                    selectedCodes.splice(idx, 1);
                }
                updateUI();
            });
        });
    }

    bindRemoveButtons();

    var debounceTimer;
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        var q = searchInput.value.trim();
        if (q.length < 2) { resultsDiv.innerHTML = ''; resultsDiv.style.display = 'none'; return; }
        debounceTimer = setTimeout(function() {
            fetch('/api/search?q=' + encodeURIComponent(q) + '&type=school')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!Array.isArray(data) || data.length === 0) {
                        resultsDiv.innerHTML = '<div class="compare-search-empty">No schools found</div>';
                    } else {
                        var html = '';
                        data.forEach(function(item) {
                            var code = item.slug.toUpperCase();
                            if (selectedCodes.indexOf(item.slug) >= 0) {
                                html += '<div class="compare-search-item already-selected">' + item.title + ' <span>already selected</span></div>';
                            } else {
                                html += '<div class="compare-search-item" data-code="' + item.slug + '" data-name="' + item.title + '">' + item.title + '</div>';
                            }
                        });
                        resultsDiv.innerHTML = html;
                    }
                    resultsDiv.style.display = 'block';
                })
                .catch(function() {
                    resultsDiv.innerHTML = '<div class="compare-search-empty">Search unavailable</div>';
                    resultsDiv.style.display = 'block';
                });
        }, 200);
    });

    resultsDiv.addEventListener('click', function(e) {
        var item = e.target.closest('.compare-search-item:not(.already-selected)');
        if (!item) return;
        if (selected.length >= 5) return;
        var code = item.getAttribute('data-code');
        var name = item.getAttribute('data-name');
        selected.push(code);
        selectedNames.push(name);
        selectedCodes.push(code.toLowerCase());
        updateUI();
        resultsDiv.style.display = 'none';
        searchInput.value = '';
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
            resultsDiv.style.display = 'none';
        }
    });
})();
</script>

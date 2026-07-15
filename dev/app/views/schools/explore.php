<section class="section">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">School Explorer</span>
            <h1 class="section-title">Interactive School Scatter Plot</h1>
            <p class="section-subtitle">Compare <?= number_format(count($schools)) ?> Massachusetts schools. Select axes, filter by grade span and district, and scroll through years.</p>
        </div>

        <!-- Controls Bar -->
        <div class="explore-controls" data-animate>
            <div class="explore-axis-selectors">
                <div class="explore-axis-group">
                    <label for="x-axis">X-Axis</label>
                    <select id="x-axis">
                        <option value="enrollment">Enrollment</option>
                        <option value="sped_pct">SPED %</option>
                        <option value="low_income_pct">Low-Income %</option>
                        <option value="el_pct">English Learner %</option>
                    </select>
                </div>
                <div class="explore-axis-group">
                    <label for="y-axis">Y-Axis</label>
                    <select id="y-axis">
                        <option value="restraint_rate" selected>Restraint Rate (%)</option>
                        <option value="discipline_rate">Discipline Rate (% out-of-school)</option>
                        <option value="attendance_pct">Attendance Rate (%)</option>
                        <option value="students_restrained">Students Restrained</option>
                    </select>
                </div>
            </div>
            <div class="explore-filters">
                <div class="explore-filter-group">
                    <label for="filter-district">District</label>
                    <select id="filter-district">
                        <option value="">All Districts</option>
                        <?php foreach ($districts as $d): ?>
                        <option value="<?= h($d['org_code']) ?>"><?= h($d['org_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="explore-filter-group">
                    <label for="filter-grade">Grade Span</label>
                    <select id="filter-grade">
                        <option value="">All Grades</option>
                        <option value="PK-5">Elementary (PK–5)</option>
                        <option value="6-8">Middle (6–8)</option>
                        <option value="9-12">High (9–12)</option>
                    </select>
                </div>
                <div class="explore-filter-group">
                    <label for="filter-min-enroll">Min Enrollment</label>
                    <input type="number" id="filter-min-enroll" value="0" min="0" step="50" style="width:100px">
                </div>
            </div>
        </div>

        <!-- Chart Area -->
        <div class="explore-chart-container" data-animate>
            <canvas id="explore-scatter" height="520"></canvas>
            <div id="explore-loading" class="explore-loading" style="display:none;">Loading&hellip;</div>
        </div>

        <!-- Year Slider -->
        <div class="explore-year-controls" data-animate>
            <button id="year-prev" class="btn btn-sm" title="Previous year">&larr;</button>
            <button id="year-play" class="btn btn-sm" title="Auto-play">&#9654;</button>
            <div class="explore-year-slider-wrap">
                <input type="range" id="year-slider" min="0" max="<?= count($years) - 1 ?>" value="<?= count($years) - 1 ?>" step="1">
                <div class="explore-year-ticks">
                    <?php foreach ($years as $i => $y): ?>
                    <span class="explore-year-tick" style="left:<?= ($i / max(1, count($years) - 1)) * 100 ?>%"><?= h(substr($y, 2)) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <button id="year-next" class="btn btn-sm" title="Next year">&rarr;</button>
            <span id="year-label" class="explore-year-label"><?= h($latest_year) ?></span>
        </div>

        <!-- Insights Panel -->
        <?php if (!empty($insights)): ?>
        <div class="explore-insights" data-animate>
            <h3>Auto-Generated Insights</h3>
            <ul>
                <?php foreach ($insights as $ins): ?>
                <li><?= h($ins) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
(function() {
    var canvas = document.getElementById('explore-scatter');
    if (!canvas || typeof Chart === 'undefined') return;

    // All data from server — embedded
    var allSchools = <?= json_encode($schools, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var allYears = <?= json_encode($years, JSON_UNESCAPED_UNICODE) ?>;
    var chart = null;
    var playInterval = null;
    var currentYearIdx = allYears.length - 1;

    // Color palette for districts
    var districtColors = {};
    var paletteIdx = 0;
    var palettes = [
        '#ff5a1f','#f59e0b','#3b82f6','#10b981','#8b5cf6','#ec4899','#06b6d4',
        '#ef4444','#84cc16','#f97316','#6366f1','#14b8a6','#d946ef','#0ea5e9',
        '#eab308','#22c55e','#a855f7','#64748b','#fb923c','#2dd4bf'
    ];

    function getDistrictColor(code) {
        if (!districtColors[code]) {
            districtColors[code] = palettes[paletteIdx % palettes.length];
            paletteIdx++;
        }
        return districtColors[code];
    }

    function computeAxis(axis, s) {
        switch (axis) {
            case 'enrollment': return parseInt(s.enrollment) || 0;
            case 'sped_pct': return parseFloat(s.sped_pct) || 0;
            case 'low_income_pct': return parseFloat(s.low_income_pct) || 0;
            case 'el_pct': return parseFloat(s.el_pct) || 0;
            case 'restraint_rate': return parseFloat(s.restraint_rate) || 0;
            case 'discipline_rate': return parseFloat(s.pct_out_school_susp) || 0;
            case 'attendance_pct': return parseFloat(s.attendance_rate) || 0;
            case 'students_restrained': return parseInt(s.students_restrained) || 0;
            default: return 0;
        }
    }

    function axisLabel(axis) {
        var map = {
            enrollment: 'Enrollment',
            sped_pct: 'SPED %',
            low_income_pct: 'Low-Income %',
            el_pct: 'English Learner %',
            restraint_rate: 'Restraint Rate (%)',
            discipline_rate: 'Discipline Rate (% out-of-school)',
            attendance_pct: 'Attendance Rate (%)',
            students_restrained: 'Students Restrained'
        };
        return map[axis] || axis;
    }

    function buildDatasets(year) {
        // Filter by current filters + year
        var districtFilter = document.getElementById('filter-district').value;
        var gradeFilter = document.getElementById('filter-grade').value;
        var minEnroll = parseInt(document.getElementById('filter-min-enroll').value) || 0;

        // Group by district
        var groups = {};
        allSchools.forEach(function(s) {
            if (year && s.school_year !== year) return;
            if (districtFilter && s.district_code !== districtFilter) return;
            if (gradeFilter && s.grade_span !== gradeFilter) return;
            if (minEnroll > 0 && (parseInt(s.enrollment) || 0) < minEnroll) return;
            if (!groups[s.district_code]) {
                groups[s.district_code] = { name: s.district_name, code: s.district_code, points: [] };
            }
            groups[s.district_code].points.push(s);
        });

        var xAxis = document.getElementById('x-axis').value;
        var yAxis = document.getElementById('y-axis').value;
        var datasets = [];

        Object.keys(groups).sort().forEach(function(code) {
            var g = groups[code];
            var color = getDistrictColor(code);
            var data = g.points.map(function(s) {
                return { x: computeAxis(xAxis, s), y: computeAxis(yAxis, s), school: s };
            });
            if (data.length > 0) {
                datasets.push({
                    label: g.name,
                    data: data,
                    backgroundColor: color + '99',
                    borderColor: color,
                    borderWidth: 1,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                });
            }
        });

        return datasets;
    }

    function updateChart(animate) {
        var year = allYears[currentYearIdx];
        document.getElementById('year-label').textContent = year;
        document.getElementById('year-slider').value = currentYearIdx;

        var xAxis = document.getElementById('x-axis').value;
        var yAxis = document.getElementById('y-axis').value;

        var datasets = buildDatasets(year);

        var config = {
            type: 'scatter',
            data: { datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: animate !== false ? { duration: 600 } : false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: '#1d1d1d',
                        titleColor: '#e0e0e0',
                        bodyColor: '#a0a0a0',
                        borderColor: '#2a2a2a',
                        borderWidth: 1,
                        callbacks: {
                            title: function(ctx) {
                                return ctx[0].raw.school.org_name;
                            },
                            label: function(ctx) {
                                var s = ctx.raw.school;
                                var lines = [];
                                lines.push('District: ' + s.district_name);
                                lines.push('Enrollment: ' + (parseInt(s.enrollment) || 0).toLocaleString());
                                lines.push(axisLabel(xAxis) + ': ' + ctx.raw.x.toLocaleString());
                                lines.push(axisLabel(yAxis) + ': ' + ctx.raw.y.toLocaleString());
                                if (s.restraint_rate) lines.push('Restraint Rate: ' + s.restraint_rate + '%');
                                return lines;
                            }
                        }
                    },
                },
                scales: {
                    x: {
                        title: { display: true, text: axisLabel(xAxis), color: '#a0a0a0', font: { family: 'Inter', size: 12 } },
                        ticks: { color: '#a0a0a0', font: { family: 'Inter', size: 11 } },
                        grid: { color: 'rgba(42,42,42,0.5)' },
                    },
                    y: {
                        title: { display: true, text: axisLabel(yAxis), color: '#a0a0a0', font: { family: 'Inter', size: 12 } },
                        ticks: { color: '#a0a0a0', font: { family: 'Inter', size: 11 } },
                        grid: { color: 'rgba(42,42,42,0.5)' },
                    }
                },
                onClick: function(e, elements) {
                    if (elements.length > 0) {
                        var idx = elements[0].datasetIndex;
                        var ptIdx = elements[0].index;
                        var school = chart.data.datasets[idx].data[ptIdx].school;
                        if (school && school.org_code) {
                            window.location.href = '/schools/' + school.org_code.toLowerCase();
                        }
                    }
                },
            }
        };

        if (chart) {
            chart.destroy();
        }
        chart = new Chart(canvas, config);
    }

    // Axis change handlers
    document.getElementById('x-axis').addEventListener('change', function() { updateChart(); });
    document.getElementById('y-axis').addEventListener('change', function() { updateChart(); });
    document.getElementById('filter-district').addEventListener('change', function() { updateChart(); });
    document.getElementById('filter-grade').addEventListener('change', function() { updateChart(); });
    document.getElementById('filter-min-enroll').addEventListener('change', function() { updateChart(); });

    // Year slider
    document.getElementById('year-slider').addEventListener('input', function() {
        currentYearIdx = parseInt(this.value);
        updateChart();
    });

    document.getElementById('year-prev').addEventListener('click', function() {
        if (currentYearIdx > 0) {
            currentYearIdx--;
            updateChart();
        }
    });

    document.getElementById('year-next').addEventListener('click', function() {
        if (currentYearIdx < allYears.length - 1) {
            currentYearIdx++;
            updateChart();
        }
    });

    // Auto-play
    var playBtn = document.getElementById('year-play');
    playBtn.addEventListener('click', function() {
        if (playInterval) {
            clearInterval(playInterval);
            playInterval = null;
            playBtn.innerHTML = '&#9654;';
            playBtn.classList.remove('playing');
        } else {
            playBtn.innerHTML = '&#9612;&#9612;';
            playBtn.classList.add('playing');
            playInterval = setInterval(function() {
                if (currentYearIdx < allYears.length - 1) {
                    currentYearIdx++;
                } else {
                    currentYearIdx = 0;
                }
                updateChart();
            }, 1200);
        }
    });

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') return;
        if (e.key === 'ArrowLeft') {
            if (currentYearIdx > 0) { currentYearIdx--; updateChart(); }
        } else if (e.key === 'ArrowRight') {
            if (currentYearIdx < allYears.length - 1) { currentYearIdx++; updateChart(); }
        } else if (e.key === ' ') {
            e.preventDefault();
            playBtn.click();
        }
    });

    // Initial render
    updateChart();
})();
</script>

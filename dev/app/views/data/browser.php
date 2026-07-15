<section class="section">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">Data Browser</span>
            <h2 class="section-title"><?= h($page_title) ?></h2>
        </div>

        <?php if (!empty($charts_html)): ?>
        <div data-animate>
            <?= $charts_html ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($sparkline_data)): ?>
        <div class="sparkline-container" data-animate>
            <canvas id="sparklineCanvas" width="600" height="60" data-sparkline='<?= json_encode($sparkline_data) ?>'></canvas>
        </div>
        <script>
        (function(){
            var c = document.getElementById('sparklineCanvas');
            if (!c || !c.dataset.sparkline) return;
            var vals = JSON.parse(c.dataset.sparkline);
            if (!vals.length) return;
            var ctx = c.getContext('2d');
            var w = c.width, h = c.height, pad = 2;
            var max = Math.max.apply(null, vals), min = Math.min.apply(null, vals);
            if (max === min) { max += 1; min -= 1; }
            var range = max - min;
            ctx.strokeStyle = '#ff5a1f';
            ctx.lineWidth = 1.5;
            ctx.beginPath();
            vals.forEach(function(v, i){
                var x = pad + (i / (vals.length - 1)) * (w - 2*pad);
                var y = h - pad - ((v - min) / range) * (h - 2*pad);
                if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
            });
            ctx.stroke();
            // dot at last value
            var lx = pad + ((vals.length-1) / (vals.length-1)) * (w - 2*pad);
            var ly = h - pad - ((vals[vals.length-1] - min) / range) * (h - 2*pad);
            ctx.fillStyle = '#ff5a1f';
            ctx.beginPath(); ctx.arc(lx, ly, 3, 0, Math.PI*2); ctx.fill();
        })();
        </script>
        <?php endif; ?>

        <form method="GET" class="filter-bar" data-animate>
            <?php if (!empty($schoolYears)): ?>
            <div class="form-group">
                <label class="form-label">School Year</label>
                <select name="school_year" class="form-select" onchange="this.form.submit()">
                    <option value="">All Years</option>
                    <?php foreach ($schoolYears as $sy): ?>
                    <option value="<?= h($sy) ?>" <?= ($selectedYear ?? '') === $sy ? 'selected' : '' ?>><?= h($sy) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <?php if (!empty($districts)): ?>
            <div class="form-group" style="min-width:250px;">
                <label class="form-label">District</label>
                <select name="district" class="form-select" onchange="this.form.submit()">
                    <option value="">All Districts</option>
                    <?php foreach ($districts as $d): ?>
                    <option value="<?= h($d['org_code'] ?? '') ?>" <?= ($selectedDistrict ?? '') === ($d['org_code'] ?? '') ? 'selected' : '' ?>><?= h($d['org_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </form>

        <?php if (!empty($csv_api_type)): ?>
        <div style="text-align:right;margin-bottom:0.5rem" data-animate>
            <a href="/api/data?type=<?= h($csv_api_type) ?>&format=csv<?= !empty($selectedYear) ? '&school_year=' . h($selectedYear) : '' ?><?= !empty($selectedDistrict) ? '&district=' . h($selectedDistrict) : '' ?>" class="btn btn-ghost btn-sm" download>&#8615; Download CSV</a>
        </div>
        <?php endif; ?>

        <?php if (!empty($state_averages)): ?>
        <button type="button" class="heatmap-toggle" id="heatmapToggle" data-animate onclick="toggleHeatmap()">
            <span id="heatmapIcon">&#9783;</span> Color-Coded View
        </button>
        <script>
        window.__stateAverages = <?= json_encode($state_averages) ?>;
        window.__heatmapOn = false;
        function toggleHeatmap(){
            window.__heatmapOn = !window.__heatmapOn;
            var btn = document.getElementById('heatmapToggle');
            var icon = document.getElementById('heatmapIcon');
            var table = document.querySelector('.data-table');
            if (!table) return;
            if (window.__heatmapOn) {
                btn.classList.add('active');
                btn.innerHTML = '<span id="heatmapIcon">&#9783;</span> Raw View';
                applyHeatmap(table);
            } else {
                btn.classList.remove('active');
                btn.innerHTML = '<span id="heatmapIcon">&#9783;</span> Color-Coded View';
                clearHeatmap(table);
            }
        }
        function applyHeatmap(table){
            var avgs = window.__stateAverages;
            var rows = table.querySelectorAll('tbody tr');
            rows.forEach(function(row){
                var cells = row.querySelectorAll('td');
                cells.forEach(function(cell){
                    var colKey = cell.dataset.colKey;
                    var val = parseFloat(cell.dataset.value);
                    if (!colKey || isNaN(val) || !avgs[colKey]) return;
                    var avg = avgs[colKey];
                    if (avg === 0) return;
                    var ratio = val / avg;
                    cell.classList.remove('cell-good','cell-warn','cell-caution','cell-bad');
                    if (ratio <= 1) cell.classList.add('cell-good');
                    else if (ratio <= 2) cell.classList.add('cell-warn');
                    else if (ratio <= 3) cell.classList.add('cell-caution');
                    else cell.classList.add('cell-bad');
                });
            });
        }
        function clearHeatmap(table){
            table.querySelectorAll('td').forEach(function(td){
                td.classList.remove('cell-good','cell-warn','cell-caution','cell-bad');
            });
        }
        </script>
        <?php endif; ?>

        <?php if (empty($rows)): ?>
            <div class="empty-state"><h3>No data found</h3><p>Try selecting a different school year or district.</p></div>
        <?php else: ?>
            <div style="overflow-x:auto;" data-animate>
                <table class="data-table">
                    <thead>
                        <tr>
                            <?php foreach ($columns as $i => $col): ?>
                            <th><?= h($col) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php
                            $colKeys = $column_keys ?? [];
                            $ci = 0;
                            foreach ($row as $key => $val):
                                $colKey = $colKeys[$ci] ?? '';
                                $ci++;
                            ?>
                            <td data-col-key="<?= h($colKey) ?>" data-value="<?= h((string)$val) ?>"><?= is_numeric($val) ? '<span class="num">' . number_format((float)$val, is_float((float)$val) ? 1 : 0) . '</span>' : h((string)$val) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?= pagination_links($pagination, $_SERVER['REQUEST_URI']) ?>
        <?php endif; ?>

        <?php if (!empty($extra_html)): ?>
        <div data-animate style="margin-top:1.5rem;">
            <?= $extra_html ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="section">
<div class="container">
    <h2 class="section-title" data-animate>PRS Filing Calendar</h2>
    <p class="section-subtitle" data-animate>Daily PRS complaint filings — darker = more cases filed that day</p>

    <?php
    $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    ?>

    <form method="GET" style="text-align:center;margin-bottom:1.5rem" data-animate>
        <select name="year" onchange="this.form.submit()" class="form-select" style="max-width:150px;display:inline">
            <?php foreach ($years as $y): ?>
            <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <div class="calendar-heatmap" data-animate>
        <div class="cal-months">
            <?php foreach ($months as $m): ?>
            <span class="cal-month"><?= $m ?></span>
            <?php endforeach; ?>
        </div>
        <div class="cal-grid">
            <?php
            $start = strtotime("$year-01-01");
            $end = strtotime("$year-12-31");
            $firstDay = (int)date('w', $start);
            for ($i = 0; $i < $firstDay; $i++) {
                echo '<div class="cal-cell cal-empty"></div>';
            }
            for ($d = $start; $d <= $end; $d += 86400) {
                $dateStr = date('Y-m-d', $d);
                $cnt = $byDay[$dateStr] ?? 0;
                $intensity = $cnt / $maxCnt;
                if ($cnt == 0) {
                    $bg = 'var(--bg-elevated)';
                } elseif ($intensity < 0.25) {
                    $bg = '#3b1f0a';
                } elseif ($intensity < 0.5) {
                    $bg = '#7a3d13';
                } elseif ($intensity < 0.75) {
                    $bg = '#c45c1b';
                } else {
                    $bg = '#ff5a1f';
                }
                $title = date('M j', $d) . ': ' . $cnt . ' case' . ($cnt != 1 ? 's' : '');
                echo '<div class="cal-cell" style="background:' . $bg . '" title="' . $title . '"></div>';
            }
            ?>
        </div>
        <div class="cal-legend">
            <span>Less</span>
            <span class="cal-swatch" style="background:var(--bg-elevated)"></span>
            <span class="cal-swatch" style="background:#3b1f0a"></span>
            <span class="cal-swatch" style="background:#7a3d13"></span>
            <span class="cal-swatch" style="background:#c45c1b"></span>
            <span class="cal-swatch" style="background:#ff5a1f"></span>
            <span>More</span>
        </div>
    </div>
</section>

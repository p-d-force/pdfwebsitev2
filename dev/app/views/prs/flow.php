<section class="section">
<div class="container">
    <h2 class="section-title" data-animate>PRS Case Flow</h2>
    <p class="section-subtitle" data-animate>How cases move through the PRS pipeline from filing to resolution</p>

    <?php
    $statusCounts = [];
    foreach ($statusData as $r) $statusCounts[$r['current_status']] = (int)$r['cnt'];

    $stages = [
        'filed' => ['label' => 'Filed', 'color' => '#60a5fa', 'icon' => '📄'],
        'accepted' => ['label' => 'Accepted', 'color' => '#22c55e', 'icon' => '✓'],
        'investigating' => ['label' => 'Investigating', 'color' => '#f59e0b', 'icon' => '🔍'],
        'findings' => ['label' => 'Findings', 'color' => '#ff5a1f', 'icon' => '📋'],
        'closed' => ['label' => 'Closed', 'color' => '#767676', 'icon' => '🔒'],
        'appealed' => ['label' => 'Appealed', 'color' => '#a78bfa', 'icon' => '⚖️'],
    ];

    $resColors = [
        'substantiated' => '#ef4444', 'unsubstantiated' => '#767676',
        'partially_substantiated' => '#f59e0b', 'resolved' => '#22c55e',
        'withdrawn' => '#a78bfa', 'dismissed' => '#60a5fa'
    ];
    ?>

    <div class="status-flow" data-animate>
        <?php foreach ($stages as $key => $stage): 
            $count = $statusCounts[$key] ?? 0;
            $pct = $totalCases > 0 ? round($count / $totalCases * 100, 1) : 0;
            $width = max(5, $pct);
        ?>
        <div class="flow-stage">
            <div class="flow-bar" style="background:<?= $stage['color'] ?>;width:<?= $width ?>%;"></div>
            <div class="flow-info">
                <span class="flow-icon"><?= $stage['icon'] ?></span>
                <span class="flow-label"><?= $stage['label'] ?></span>
                <span class="flow-count"><?= number_format($count) ?></span>
                <span class="flow-pct"><?= $pct ?>%</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="chart-card" data-animate style="margin-top:2rem;">
        <h3>Resolution Outcomes</h3>
        <div class="resolution-grid">
            <?php foreach ($resolutionData as $r): 
                $color = $resColors[$r['resolution_type']] ?? '#767676';
                $rpct = $totalCases > 0 ? round($r['cnt'] / $totalCases * 100, 1) : 0;
            ?>
            <div class="resolution-item">
                <div class="resolution-bar" style="background:<?= $color ?>;width:<?= max(3, $rpct*3) ?>%;"></div>
                <div class="resolution-info">
                    <span style="color:<?= $color ?>;font-weight:600;"><?= ucwords(str_replace('_',' ',$r['resolution_type'])) ?></span>
                    <span><?= number_format($r['cnt']) ?> cases (<?= $rpct ?>%)</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</section>

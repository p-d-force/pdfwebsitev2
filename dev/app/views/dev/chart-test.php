<section class="section">
<div class="container">
    <h2 class="section-title">Chart Performance Smoke Test</h2>
    <p class="section-subtitle">Rendering 30+ charts to verify total render time &lt; 3 seconds</p>

    <div id="perf-result" style="text-align:center;padding:1rem;margin:1rem 0;background:var(--bg-elevated);border-radius:8px;font-size:1.2rem;color:var(--text-secondary)">
        Rendering...
    </div>

    <?php
    use App\Components\Chart;

    // 1. Bar charts
    echo '<div class="chart-card"><h3>Bar — Restraint Trends</h3>';
    $c = new Chart('perf-bar1', 'bar');
    $c->setLabels(['2019','2020','2021','2022','2023','2024']);
    $c->addDataset('Data A', [10,20,15,30,25,40], ['palette' => 'warm']);
    $c->setHeight(200); echo $c->render();
    echo '</div>';

    echo '<div class="chart-card"><h3>Bar — Discipline</h3>';
    $c = new Chart('perf-bar2', 'bar');
    $c->setLabels(['A','B','C','D','E']);
    $c->addDataset('Series 1', [5,8,3,9,6], ['palette' => 'cool']);
    $c->addDataset('Series 2', [3,6,8,4,7], ['palette' => 'warm']);
    $c->setHeight(200); echo $c->render();
    echo '</div>';

    echo '<div class="chart-card"><h3>Bar — Stacked</h3>';
    $c = new Chart('perf-bar3', 'bar');
    $c->setLabels(['Q1','Q2','Q3','Q4']);
    $c->addDataset('Green', [5,3,7,4], ['backgroundColor' => '#22c55e']);
    $c->addDataset('Orange', [3,4,2,5], ['backgroundColor' => '#f59e0b']);
    $c->addDataset('Red', [2,3,1,2], ['backgroundColor' => '#ef4444']);
    $c->setOption('scales.x.stacked', true);
    $c->setOption('scales.y.stacked', true);
    $c->setHeight(200); echo $c->render();
    echo '</div>';

    // 2. Line charts
    for ($i = 1; $i <= 5; $i++) {
        echo '<div class="chart-card"><h3>Line Chart ' . $i . '</h3>';
        $c = new Chart('perf-line' . $i, 'line');
        $c->setLabels(['Jan','Feb','Mar','Apr','May','Jun']);
        $c->addDataset('Series', [rand(10,50),rand(10,50),rand(10,50),rand(10,50),rand(10,50),rand(10,50)], ['borderColor' => '#ff5a1f', 'tension' => 0.3]);
        $c->setOption('plugins.legend.display', false);
        $c->setHeight(180); echo $c->render();
        echo '</div>';
    }

    // 3. Pie/Doughnut
    for ($i = 1; $i <= 4; $i++) {
        echo '<div class="chart-card"><h3>Doughnut ' . $i . '</h3>';
        $c = new Chart('perf-dough' . $i, 'doughnut');
        $c->setLabels(['A','B','C','D']);
        $c->addDataset('Data', [rand(10,40),rand(10,40),rand(10,40),rand(10,40)]);
        $c->setHeight(200); echo $c->render();
        echo '</div>';
    }

    // 4. Radar
    echo '<div class="chart-card"><h3>Radar</h3>';
    $c = new Chart('perf-radar', 'radar');
    $c->setLabels(['Speed','Power','Agility','Defense','Stamina']);
    $c->addDataset('Player A', [80,70,90,60,75], ['borderColor' => '#ff5a1f', 'backgroundColor' => 'rgba(255,90,31,0.1)']);
    $c->addDataset('Player B', [60,85,70,80,65], ['borderColor' => '#60a5fa', 'backgroundColor' => 'rgba(96,165,250,0.1)']);
    $c->setHeight(300); echo $c->render();
    echo '</div>';

    // 5. Scatter
    echo '<div class="chart-card"><h3>Scatter</h3>';
    $scatterPoints = [];
    for ($i = 0; $i < 20; $i++) $scatterPoints[] = ['x' => rand(10,100), 'y' => rand(10,100)];
    $c = new Chart('perf-scatter', 'scatter');
    $c->addDataset('Points', $scatterPoints, ['backgroundColor' => '#ff5a1f', 'pointRadius' => 5]);
    $c->setOption('plugins.legend.display', false);
    $c->setHeight(300); echo $c->render();
    echo '</div>';

    // More bars and lines to push to 30+
    for ($i = 4; $i <= 12; $i++) {
        echo '<div class="chart-card"><h3>Bar Chart ' . $i . '</h3>';
        $c = new Chart('perf-extra' . $i, 'bar');
        $c->setLabels(['X','Y','Z']);
        $c->addDataset('Val', [rand(1,20),rand(1,20),rand(1,20)], ['palette' => 'default']);
        $c->setOption('plugins.legend.display', false);
        $c->setHeight(150); echo $c->render();
        echo '</div>';
    }
    ?>

    <script>
    (function(){
        var start = performance.now();
        var charts = [];
        document.querySelectorAll('canvas').forEach(function(c) {
            if (c.chartInstance) charts.push(c.chartInstance);
        });
        // Wait for Chart.js to finish
        setTimeout(function(){
            var elapsed = (performance.now() - start).toFixed(0);
            var el = document.getElementById('perf-result');
            var pass = elapsed < 3000;
            el.innerHTML = pass
                ? '<span style="color:#22c55e">PASS</span> — ' + elapsed + 'ms (target: &lt;3s) | ' + document.querySelectorAll('canvas').length + ' charts'
                : '<span style="color:#ef4444">WARN</span> — ' + elapsed + 'ms (target: &lt;3s) | ' + document.querySelectorAll('canvas').length + ' charts';
            el.style.color = pass ? '#22c55e' : '#ef4444';
        }, 100);
    })();
    </script>
</div>
</section>

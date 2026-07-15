<section class="section">
<div class="container">
    <h2 class="section-title" data-animate>MA Restraint Rate by Town — <?= h($school_year) ?></h2>

    <form method="GET" style="text-align:center;margin-bottom:1.5rem" data-animate>
        <label class="form-label" style="display:inline;margin-right:0.5rem">School Year:</label>
        <select name="school_year" onchange="this.form.submit()" class="form-select" style="max-width:200px;display:inline">
            <?php foreach ($years as $y): ?>
            <option value="<?= h($y) ?>" <?= $y === $school_year ? 'selected' : '' ?>><?= h($y) ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <div class="map-container" data-animate>
        <?php readfile(__DIR__ . '/../../../public/assets/images/ma-towns.svg'); ?>
    </div>

    <div class="map-legend">
        <span style="background:#2a2a2a">None</span>
        <span style="background:#22c55e">Low</span>
        <span style="background:#f59e0b">Medium</span>
        <span style="background:#ef4444">High</span>
    </div>

    <div id="map-tooltip" style="display:none;position:fixed;background:var(--bg-elevated);border:1px solid var(--border);padding:0.5rem 0.75rem;border-radius:8px;font-size:0.8rem;pointer-events:none;z-index:1000"></div>

    <script>
    (function(){
        var colors = <?= json_encode($town_colors, JSON_UNESCAPED_UNICODE) ?>;
        var tip = document.getElementById('map-tooltip');
        var slugMap = {};
        for (var slug in colors) {
            slugMap[colors[slug].name] = slug;
        }
        for (var slug in colors) {
            var el = document.getElementById('town-' + slug);
            if (!el) continue;
            el.setAttribute('fill', colors[slug].color);
            el.addEventListener('mouseenter', function(e) {
                var name = e.currentTarget.getAttribute('data-town');
                var slug = slugMap[name];
                var c = colors[slug];
                if (!c) return;
                tip.innerHTML = '<strong>' + c.name + '</strong><br>Rate: ' + c.rate + ' per 100<br>Restraints: ' + c.restraints.toLocaleString() + '<br>Enrollment: ' + c.enrollment.toLocaleString();
                tip.style.display = 'block';
            });
            el.addEventListener('mousemove', function(e) {
                tip.style.left = (e.clientX + 12) + 'px';
                tip.style.top = (e.clientY - 60) + 'px';
            });
            el.addEventListener('mouseleave', function() {
                tip.style.display = 'none';
            });
        }
    })();
    </script>
</div>
</section>

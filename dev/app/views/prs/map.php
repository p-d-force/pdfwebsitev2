<section class="section">
<div class="container">
    <h2 class="section-title" data-animate>PRS Complaints by County</h2>
    <p class="section-subtitle" data-animate>Problem Resolution System cases aggregated by Massachusetts county</p>

    <div style="text-align:center;margin-bottom:1.5rem" data-animate>
        <button id="modeToggle" class="btn btn-ghost btn-sm" onclick="toggleMapMode()">Smooth Gradient</button>
    </div>

    <div class="map-container" data-animate>
        <?php readfile(__DIR__ . '/../../../public/assets/images/ma-counties.svg'); ?>
    </div>

    <div class="map-legend" id="legendDiscrete">
        <span style="background:#22c55e">Low</span>
        <span style="background:#f59e0b">Medium</span>
        <span style="background:#ef4444">High</span>
    </div>
    <div class="map-legend" id="legendSmooth" style="display:none">
        <span style="background:hsl(120,70%,45%)">Low</span>
        <span style="background:hsl(60,70%,45%)">Mid</span>
        <span style="background:hsl(0,70%,45%)">High</span>
    </div>

    <div id="map-tooltip" style="display:none;position:fixed;background:var(--bg-elevated);border:1px solid var(--border);padding:0.5rem 0.75rem;border-radius:8px;font-size:0.8rem;pointer-events:none;z-index:1000"></div>

    <script>
    (function(){
        var colors = <?= json_encode($county_colors, JSON_UNESCAPED_UNICODE) ?>;
        var tip = document.getElementById('map-tooltip');
        var slugMap = {};
        for (var slug in colors) { slugMap[colors[slug].name] = slug; }

        var smoothMode = false;
        window.toggleMapMode = function() {
            smoothMode = !smoothMode;
            var btn = document.getElementById('modeToggle');
            document.getElementById('legendDiscrete').style.display = smoothMode ? 'none' : 'flex';
            document.getElementById('legendSmooth').style.display = smoothMode ? 'flex' : 'none';
            btn.textContent = smoothMode ? 'Discrete Colors' : 'Smooth Gradient';
            applyColors();
        };

        function applyColors() {
            for (var slug in colors) {
                var el = document.getElementById('cty-' + slug);
                if (!el) continue;
                el.setAttribute('fill', smoothMode ? (colors[slug].smooth || colors[slug].color) : colors[slug].color);
            }
        }

        for (var slug in colors) {
            var el = document.getElementById('cty-' + slug);
            if (!el) continue;
            el.setAttribute('fill', colors[slug].color);
            el.addEventListener('mouseenter', function(e) {
                var name = e.currentTarget.getAttribute('data-county');
                var slug = slugMap[name];
                var c = colors[slug];
                if (!c) return;
                tip.innerHTML = '<strong>' + c.name + '</strong><br>Total Cases: ' + c.cases.toLocaleString() + '<br>Open Cases: ' + c.open.toLocaleString();
                tip.style.display = 'block';
            });
            el.addEventListener('mousemove', function(e) {
                tip.style.left = (e.clientX + 12) + 'px';
                tip.style.top = (e.clientY - 60) + 'px';
            });
            el.addEventListener('mouseleave', function() { tip.style.display = 'none'; });
            el.addEventListener('click', function(e) {
                window.location = '/counties/' + e.currentTarget.getAttribute('data-county').toLowerCase();
            });
        }
    })();
    </script>
</div>
</section>

<section class="section">
<div class="container">
    <h2 class="section-title" data-animate>Bulk Data Export</h2>
    <p class="section-subtitle" data-animate>Download multiple datasets at once. CSV format with Excel-compatible encoding.</p>

    <form method="GET" action="/api/data" target="_blank" data-animate style="max-width:600px;margin:2rem auto;">
        <div class="chart-card" style="padding:1.5rem;">
            <h3 style="margin-bottom:1rem;">Select Datasets</h3>
            <?php
            $datasets = [
                'restraint-trends' => 'Restraint Trends (statewide, by year)',
                'discipline-breakdown' => 'Discipline Breakdown (by type, by year)',
                'enrollment-demographics' => 'Enrollment Demographics (SPED, EL, income)',
                'attendance-trends' => 'Attendance Trends (rate, chronic absenteeism)',
                'sped-outcomes' => 'SPED Outcomes (grad rate, dropout, inclusion)',
                'prs-categories' => 'PRS Categories (complaint types)',
                'district-comparison' => 'District Comparison (requires district codes)',
            ];
            foreach ($datasets as $type => $label): ?>
            <label style="display:flex;align-items:center;gap:0.5rem;padding:0.5rem 0;cursor:pointer;border-bottom:1px solid var(--border-soft);">
                <input type="checkbox" name="datasets[]" value="<?= $type ?>" style="accent-color:var(--accent);">
                <span><?= h($label) ?></span>
            </label>
            <?php endforeach; ?>

            <div style="margin-top:1rem;">
                <label class="form-label">Year Filter (optional)</label>
                <input type="text" name="school_year" placeholder="e.g. 2023-24" class="form-input" style="width:100%;background:var(--bg-secondary);border:1px solid var(--border);color:var(--text-primary);padding:0.5rem;border-radius:6px;">
            </div>

            <button type="button" onclick="downloadSelected()" class="btn btn-primary" style="margin-top:1rem;width:100%;">Download Selected</button>
        </div>
    </form>

    <script>
    function downloadSelected() {
        var checks = document.querySelectorAll('input[name="datasets[]"]:checked');
        if (checks.length === 0) { alert('Select at least one dataset.'); return; }
        checks.forEach(function(cb) {
            var url = '/api/data?type=' + cb.value + '&format=csv';
            var year = document.querySelector('input[name="school_year"]').value;
            if (year) url += '&school_year=' + encodeURIComponent(year);
            window.open(url, '_blank');
        });
    }
    </script>
</div>
</section>

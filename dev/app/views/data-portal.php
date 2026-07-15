<section class="section" id="data-browser">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">Data &amp; Analytics</span>
            <h2 class="section-title">Data Portal</h2>
            <p class="section-subtitle">Interactive exploration of DESE data across all Massachusetts public schools.</p>
        </div>

        <?php $activeTab = $_GET['tab'] ?? 'portal'; ?>

        <?php if ($activeTab !== 'portal'): ?>
        <div class="data-browser-tabs" data-animate>
            <a href="?tab=portal" class="data-tab">← Data Portal</a>
            <a href="?tab=restraint" class="data-tab<?= $activeTab === 'restraint' ? ' active' : '' ?>">Restraint</a>
            <a href="?tab=compare" class="data-tab<?= $activeTab === 'compare' ? ' active' : '' ?>">Compare</a>
        </div>
        <?php endif; ?>

        <?php if ($activeTab === 'portal'): ?>
        <div class="data-tab-content active" data-animate>
            <div class="data-browser-intro">
                <h3>Explore Our Datasets</h3>
                <p>All data sourced directly from the <a href="https://profiles.doe.mass.edu/" target="_blank" rel="noopener" style="color:var(--accent-glow);">Massachusetts DESE Profiles</a> website.</p>
            </div>
            <div class="resources-grid" style="margin-top:1.5rem;">
                <article class="resource-card" style="border-left:3px solid var(--accent);">
                    <h3 class="resource-title">Student Restraint &amp; Seclusion</h3>
                    <p class="resource-excerpt">School-level physical restraint and seclusion data across multiple school years. Track injuries, rates, and patterns.</p>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                        <a href="?tab=restraint" class="btn btn-primary btn-sm">Explore</a>
                        <a href="/data/restraint" class="btn btn-ghost btn-sm">Full Browser</a>
                    </div>
                </article>
                <article class="resource-card" style="border-left:3px solid #f59e0b;">
                    <h3 class="resource-title">Student Discipline</h3>
                    <p class="resource-excerpt">Suspensions, expulsions, and disciplinary actions by district and demographic group.</p>
                    <a href="/data/discipline" class="btn btn-primary btn-sm">Browse Data</a>
                </article>
                <article class="resource-card" style="border-left:3px solid #06b6d4;">
                    <h3 class="resource-title">Enrollment Demographics</h3>
                    <p class="resource-excerpt">Enrollment by SPED status, economic disadvantage, English learner, and high-needs.</p>
                    <a href="/data/enrollment" class="btn btn-primary btn-sm">Browse Data</a>
                </article>
                <article class="resource-card" style="border-left:3px solid #10b981;">
                    <h3 class="resource-title">Attendance &amp; Absenteeism</h3>
                    <p class="resource-excerpt">Attendance rates and chronic absenteeism data by district.</p>
                    <a href="/data/attendance" class="btn btn-primary btn-sm">Browse Data</a>
                </article>
                <article class="resource-card" style="border-left:3px solid #8b5cf6;">
                    <h3 class="resource-title">Special Education Outcomes</h3>
                    <p class="resource-excerpt">Graduation rates, dropout rates, and inclusion data for students with IEPs.</p>
                    <a href="/data/sped-results" class="btn btn-primary btn-sm">Browse Data</a>
                </article>
                <article class="resource-card" style="border-left:3px solid #ec4899;">
                    <h3 class="resource-title">PRS Intake Browser</h3>
                    <p class="resource-excerpt">Problem Resolution System complaints tracked across districts.</p>
                    <a href="/data/prs" class="btn btn-primary btn-sm">Browse Data</a>
                </article>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($activeTab === 'restraint'): ?>
        <div class="data-tab-content active" data-animate>
            <div class="restraint-charts">
                <div class="restraint-chart-card" style="min-height:400px;">
                    <h3 class="restraint-chart-title">Statewide Restraint Trends</h3>
                    <div style="position:relative;height:400px;">
                        <?php
                        $stChart = new \App\Components\Chart('restraintTrendsChart', 'bar');
                        echo $stChart->renderAsync('/api/data?type=restraint-trends');
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($activeTab === 'compare'): ?>
        <div class="data-tab-content active" data-animate>
            <p style="text-align:center;margin:3rem 0;">
                <a href="/compare" class="btn btn-primary">Open Full Comparison Tool</a>
            </p>
        </div>
        <?php endif; ?>
    </div>
</section>

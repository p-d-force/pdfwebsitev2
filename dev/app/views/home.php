<!-- Hero -->
<section class="hero" id="hero">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <div class="hero-tagline"><?= SITE_TAGLINE ?></div>
        <h1 class="hero-title">
            <span class="hero-title-line">Data-Driven Advocacy</span>
            <span class="hero-title-accent">for Families</span>
        </h1>
        <p class="hero-subtitle">
            Independent special education and public accountability advocacy.
            Tracking complaints, records, outcomes, and systemic patterns across Massachusetts districts.
        </p>
        <div class="hero-cta">
            <a href="/cases/" class="btn btn-primary">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                View Cases
            </a>
            <a href="/articles/" class="btn btn-secondary">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                Read Articles
            </a>
            <a href="/submit/" class="btn btn-tip">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Submit a Tip
            </a>
            <a href="/data/" class="btn btn-ghost">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Explore Data
            </a>
        </div>
        <div class="hero-stats">
            <div class="stat" data-animate>
                <span class="stat-value"><?= number_format($districtCount ?? 0) ?></span>
                <span class="stat-label">Districts Tracked</span>
            </div>
            <div class="stat" data-animate>
                <span class="stat-value"><?= number_format($caseCount ?? 0) ?></span>
                <span class="stat-label">Active Cases</span>
            </div>
            <div class="stat" data-animate>
                <span class="stat-value"><?= number_format($articleCount ?? 0) ?></span>
                <span class="stat-label">Articles</span>
            </div>
            <div class="stat" data-animate>
                <span class="stat-value"><?= number_format($orgCount ?? 0) ?></span>
                <span class="stat-label">Organizations</span>
            </div>
        </div>
    </div>
</section>

<!-- Featured Articles -->
<?php if (!empty($featured)): ?>
<section class="section" id="featured">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">Latest Articles</span>
            <h2 class="section-title">Research, Analysis &amp; Advocacy</h2>
            <p class="section-subtitle">Data-driven reporting on special education, public records, and systemic accountability across Massachusetts.</p>
        </div>
        <div class="articles-grid" data-animate>
            <?php foreach ($featured as $a): ?>
            <article class="article-card">
                <div class="article-card-body">
                    <div class="article-card-meta">
                        <span class="article-category"><?= h($a['article_type'] ?? 'Article') ?></span>
                        <span class="article-date"><?= h(format_date($a['published_date'])) ?></span>
                    </div>
                    <h3 class="article-card-title"><a href="/articles/<?= h($a['slug']) ?>"><?= h($a['title']) ?></a></h3>
                    <p class="article-card-excerpt"><?= h(truncate($a['excerpt'] ?? '', 200)) ?></p>
                    <div class="article-card-footer">
                        <span class="article-read-time"><?= read_time($a['excerpt'] ?? '') ?> min read</span>
                        <a href="/articles/<?= h($a['slug']) ?>" class="resource-link">Read Article</a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Explore -->
<section class="section section-dark" id="quick-links">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">Explore</span>
            <h2 class="section-title">What We Track</h2>
        </div>
        <div class="resources-grid" data-animate>
            <article class="resource-card">
                <h3 class="resource-title">Case Directory</h3>
                <p class="resource-excerpt">Active investigations, public records requests, appeals, and state determinations with full timelines.</p>
                <a href="/cases/" class="resource-link">View Cases</a>
            </article>
            <article class="resource-card">
                <h3 class="resource-title">Data Browser</h3>
                <p class="resource-excerpt">Interactive exploration of DESE restraint data, district analytics, and statewide patterns.</p>
                <a href="/data/" class="resource-link">Explore Data</a>
            </article>
            <article class="resource-card">
                <h3 class="resource-title">District Profiles</h3>
                <p class="resource-excerpt">Per-district pages aggregating cases, data summaries, and advocacy activity.</p>
                <a href="/districts/" class="resource-link">View Districts</a>
            </article>
            <article class="resource-card">
                <h3 class="resource-title">Appearances &amp; Media</h3>
                <p class="resource-excerpt">Public comments, school committee testimony, press coverage, and advocacy appearances.</p>
                <a href="/appearances/" class="resource-link">View Appearances</a>
            </article>
        </div>
    </div>
</section>

<!-- Recent Cases -->
<?php if (!empty($recentCases)): ?>
<section class="section" id="updates-ticker">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">Recent Activity</span>
            <h2 class="section-title">Latest Cases</h2>
        </div>
        <div class="updates-list" data-animate>
            <?php foreach ($recentCases as $c): ?>
            <div class="update-item">
                <div class="update-item-head">
                    <span class="update-date"><?= h(format_date($c['filed_date'])) ?></span>
                    <?= status_badge($c['status'] ?? 'open') ?>
                    <?php if (!empty($c['case_number'])): ?>
                    <a href="/cases/<?= h($c['slug'] ?? $c['case_number']) ?>" class="update-case-link"><?= h($c['case_number']) ?></a>
                    <?php endif; ?>
                </div>
                <h4 class="update-title"><?= h($c['title']) ?></h4>
                <?php if (!empty($c['summary'])): ?>
                <p class="update-body"><?= h(truncate($c['summary'], 200)) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Statewide Restraint Chart -->
<section class="section" id="data-glance">
    <div class="container">
        <div class="section-header" data-animate>
            <span class="section-tag">Data at a Glance</span>
            <h2 class="section-title">Statewide Restraint Trends</h2>
            <p class="section-subtitle">Physical restraints and injuries across all Massachusetts public schools, computed live from DESE data.</p>
        </div>
        <div style="max-width:900px;margin:0 auto;" data-animate>
            <div style="background:var(--bg-elevated);border-radius:16px;padding:1.5rem;border:1px solid var(--border);">
                <canvas id="homeRestraintChart" style="max-height:380px;"></canvas>
                <p style="text-align:center;color:var(--text-muted);font-size:0.85rem;margin-top:0.75rem;">
                    <a href="/data/" style="color:var(--accent-glow);">Explore full data portal &rarr;</a>
                </p>
            </div>
        </div>
    </div>
</section>

<script>
fetch('/api/data?type=restraint-trends')
  .then(r => r.json())
  .then(data => {
    if (!data.length) return;
    var ctx = document.getElementById('homeRestraintChart');
    if (!ctx) return;
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: data.map(d => d.school_year),
        datasets: [{
          label: 'Total Restraints',
          data: data.map(d => parseInt(d.restraints)),
          backgroundColor: 'rgba(255,90,31,0.6)',
          borderColor: 'rgba(255,90,31,1)',
          borderWidth: 1,
          borderRadius: 4,
        }, {
          label: 'Injuries During Restraint',
          data: data.map(d => parseInt(d.injuries)),
          type: 'line',
          borderColor: '#f59e0b',
          backgroundColor: 'rgba(245,158,11,0.1)',
          borderWidth: 2,
          pointRadius: 4,
          pointBackgroundColor: '#f59e0b',
          tension: 0.3,
          yAxisID: 'y1',
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            labels: { color: '#a0a0a0', font: { family: 'Inter' }, padding: 20 }
          }
        },
        scales: {
          x: {
            ticks: { color: '#767676', font: { family: 'Inter', size: 11 } },
            grid: { color: 'rgba(42,42,42,0.5)' }
          },
          y: {
            beginAtZero: true,
            ticks: { color: '#767676', font: { family: 'Inter', size: 11 } },
            grid: { color: 'rgba(42,42,42,0.5)' },
            title: { display: true, text: 'Total Restraints', color: '#a0a0a0' }
          },
          y1: {
            position: 'right',
            beginAtZero: true,
            ticks: { color: '#f59e0b', font: { family: 'Inter', size: 11 } },
            grid: { display: false },
            title: { display: true, text: 'Injuries', color: '#f59e0b' }
          }
        }
      }
    });
  });
</script>
<?php endif; ?>

<!-- CTA -->
<section class="section section-accent" id="cta-bottom">
    <div class="container" data-animate>
        <div class="cta-banner">
            <h2 class="section-title">Have Information to Share?</h2>
            <p class="section-subtitle">Tips, documents, or data about special education practices, public records concerns, or systemic issues in Massachusetts school districts — we want to hear from you.</p>
            <div class="hero-cta">
                <a href="/submit/" class="btn btn-primary">Submit a Tip</a>
                <a href="/donate/" class="btn btn-secondary">Support Our Work</a>
            </div>
        </div>
    </div>
</section>

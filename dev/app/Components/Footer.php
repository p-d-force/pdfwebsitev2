<?php declare(strict_types=1);
namespace App\Components;

/**
 * Footer component — logo, links, copyright.
 */
class Footer
{
    public function render(): void
    {
        ?>
        <footer style="background:var(--bg-secondary);border-top:1px solid var(--border);padding:3rem 0 2rem;margin-top:4rem;">
            <div class="container">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:2rem;">
                    <div>
                        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;">
                            <img src="<?= asset('images/logo.png') ?>" alt="" class="footer-logo-img" width="28" height="28" style="border-radius:6px;">
                            <span style="font-weight:700;font-size:0.95rem;">Parent Data Force</span>
                        </div>
                        <p style="color:var(--text-muted);font-size:0.85rem;">Independent special education and public accountability advocacy. Data-driven, parent-powered, Massachusetts-focused.</p>
                    </div>
                    <div>
                        <h4 style="font-size:0.85rem;margin-bottom:0.75rem;color:var(--text-secondary);">Navigate</h4>
                        <ul style="list-style:none;padding:0;margin:0;font-size:0.85rem;">
                            <li style="margin-bottom:0.35rem;"><a href="/articles/" style="color:var(--text-muted);">Articles</a></li>
                            <li style="margin-bottom:0.35rem;"><a href="/cases/" style="color:var(--text-muted);">Cases</a></li>
                            <li style="margin-bottom:0.35rem;"><a href="/districts/" style="color:var(--text-muted);">Districts</a></li>
                            <li style="margin-bottom:0.35rem;"><a href="/data/" style="color:var(--text-muted);">Data Portal</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 style="font-size:0.85rem;margin-bottom:0.75rem;color:var(--text-secondary);">Resources</h4>
                        <ul style="list-style:none;padding:0;margin:0;font-size:0.85rem;">
                            <li style="margin-bottom:0.35rem;"><a href="/about/" style="color:var(--text-muted);">About</a></li>
                            <li style="margin-bottom:0.35rem;"><a href="/submit/" style="color:var(--text-muted);">Submit a Tip</a></li>
                            <li style="margin-bottom:0.35rem;"><a href="/resources/" style="color:var(--text-muted);">Resources</a></li>
                            <li style="margin-bottom:0.35rem;"><a href="/appearances/" style="color:var(--text-muted);">Appearances</a></li>
                            <li style="margin-bottom:0.35rem;"><a href="/rss" style="color:var(--text-muted);">RSS Feed</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 style="font-size:0.85rem;margin-bottom:0.75rem;color:var(--text-secondary);">Connect</h4>
                        <ul style="list-style:none;padding:0;margin:0;font-size:0.85rem;">
                            <li style="margin-bottom:0.35rem;"><a href="/donate/" style="color:var(--text-muted);">Donate</a></li>
                            <li style="margin-bottom:0.35rem;"><a href="mailto:admin@parentdataforce.com" style="color:var(--text-muted);">Contact</a></li>
                        </ul>
                    </div>
                </div>
                <div style="border-top:1px solid var(--border);margin-top:2rem;padding-top:1.5rem;text-align:center;font-size:0.8rem;color:var(--text-muted);">
                    &copy; <?= date('Y') ?> Parent Data Force. All rights reserved.
                </div>
            </div>
        </footer>
        <?php
    }
}

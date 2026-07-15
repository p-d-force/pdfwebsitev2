<?php declare(strict_types=1);
namespace App\Components;

/**
 * Nav component — fixed dark navigation bar with logo and links.
 */
class Nav
{
    public function render(): void
    {
        $current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $current = rtrim($current ?? '/', '/') ?: '/';
        ?>
        <nav class="nav">
            <div class="nav-inner">
                <a href="/" class="nav-logo">
                    <img src="<?= asset('images/logo.png') ?>" alt="Parent Data Force" class="nav-logo-img" width="36" height="36">
                    <span class="nav-logo-text">PARENT DATA FORCE</span>
                </a>

                <button class="nav-toggle" aria-label="Toggle navigation" onclick="this.classList.toggle('open'); document.querySelector('.nav-links').classList.toggle('open')">
                    <span></span><span></span><span></span>
                </button>

                <ul class="nav-links">
                    <li><a href="/articles/" class="nav-link<?= str_starts_with($current, '/articles') ? ' active' : '' ?>">Articles</a></li>
                    <li><a href="/cases/" class="nav-link<?= str_starts_with($current, '/cases') ? ' active' : '' ?>">Cases</a></li>
                    <li><a href="/districts/" class="nav-link<?= str_starts_with($current, '/districts') ? ' active' : '' ?>">Districts</a></li>
                    <li><a href="/data/" class="nav-link<?= str_starts_with($current, '/data') ? ' active' : '' ?>">Data</a></li>
                    <li><a href="/data/map" class="nav-link<?= str_starts_with($current, '/data/map') || str_starts_with($current, '/prs/map') || str_starts_with($current, '/data/town-map') || str_starts_with($current, '/prs/town-map') ? ' active' : '' ?>">Maps</a></li>
                    <li><a href="/about/" class="nav-link<?= $current === '/about' ? ' active' : '' ?>">About</a></li>
                </ul>
            </div>
        </nav>
        <?php
    }
}

<?php declare(strict_types=1);
/**
 * Route definitions — URL pattern → Controller::method.
 */

use App\Controllers\{
    HomeController,
    ArticleController,
    CaseController,
    DistrictController,
    SchoolController,
    DataPortalController,
    DataSubController,
    SearchController,
    ApiController,
    AdminController,
    AdminArticlesController,
    AdminCasesController,
    AdminOrganizationsController,
    AdminDocumentsController,
    AdminSubmissionsController,
    AdminUpdatesController,
    AdminMediaController,
    PrsController,
    AdminPrsController,
};

return [
    // ── Public Pages ──
    '/'                        => [HomeController::class, 'index'],
    '/about'                   => [HomeController::class, 'about'],
    '/submit'                  => [HomeController::class, 'submit'],
    '/updates'                 => [HomeController::class, 'updates'],
    '/appearances'             => [HomeController::class, 'appearances'],
    '/resources'               => [HomeController::class, 'resources'],
    '/donate'                  => [HomeController::class, 'donate'],
    '/documents/{id}'          => [HomeController::class, 'document'],

    // ── Articles ──
    '/articles'                => [ArticleController::class, 'list'],
    '/articles/{slug}'         => [ArticleController::class, 'show'],

    // ── Cases ──
    '/cases'                   => [CaseController::class, 'list'],
    '/cases/{slug}'            => [CaseController::class, 'show'],

    // ── Districts ──
    '/districts'               => [DistrictController::class, 'list'],
    '/districts/{slug}'        => [DistrictController::class, 'show'],


    // ── Counties ──
    '/counties'                => [DistrictController::class, 'countiesList'],
    '/counties/{slug}'         => [DistrictController::class, 'countyShow'],
    // ── Schools ──
    // ── Schools (static routes BEFORE wildcard) ──
    '/schools/compare'         => [SchoolController::class, 'compare'],
    '/schools/rankings'        => [SchoolController::class, 'rankings'],
    '/schools/equity'          => [SchoolController::class, 'equity'],
    '/schools/trends'          => [SchoolController::class, 'trends'],
    '/schools/integrity'       => [SchoolController::class, 'integrity'],
    '/schools/explore'         => [SchoolController::class, 'explore'],
    '/schools/{slug}/report-card' => [SchoolController::class, 'reportCard'],
    '/schools'                 => [SchoolController::class, 'list'],
    '/schools/{slug}'          => [SchoolController::class, 'show'],

    // ── PRS Tracker ──
    '/prs/analytics'           => [PrsController::class, 'analytics'],
    '/prs/district/{code}'     => [PrsController::class, 'districtView'],
    '/prs/calendar'            => [PrsController::class, 'calendar'],
    '/prs'                     => [PrsController::class, 'list'],

    '/prs/cross-ref'          => [PrsController::class, 'crossRef'],
    '/prs/map'                 => [PrsController::class, 'map'],
    '/prs/town-map'            => [PrsController::class, 'townMap'],
    '/prs/{prs_number}'        => [PrsController::class, 'show'],

    // ── Data Portal ──
    '/data'                    => [DataPortalController::class, 'index'],
    '/compare'                 => [DataPortalController::class, 'compare'],
    '/data/dashboard'          => [DataPortalController::class, 'dashboard'],
    '/data/restraint'          => [DataSubController::class, 'restraint'],
    '/data/prs'                => [DataSubController::class, 'prs'],
    '/data/discipline'         => [DataSubController::class, 'discipline'],
    '/data/enrollment'         => [DataSubController::class, 'enrollment'],

    '/data/combined'           => [DataPortalController::class, 'combined'],
    '/data/town-map'           => [DataPortalController::class, 'townMap'],

    '/data/map'                => [DataPortalController::class, 'map'],
    '/data/attendance'         => [DataSubController::class, 'attendance'],
    '/data/export'             => [DataPortalController::class, 'export'],
    '/data/help'               => [DataPortalController::class, 'help'],
    '/dev/chart-test'          => [DataPortalController::class, 'chartTest'],
    '/data/sped-results'       => [DataSubController::class, 'spedResults'],

    // ── Search ──
    '/search'                  => [SearchController::class, 'index'],

    // ── API ──
    '/api/data'                => [ApiController::class, 'data'],
    '/api/cases'               => [ApiController::class, 'cases'],
    '/api/articles'            => [ApiController::class, 'articles'],
    '/api/search'              => [ApiController::class, 'search'],
    '/api/submit'              => ['POST', ApiController::class, 'submit'],
    '/api/subscribe'           => ['POST', ApiController::class, 'subscribe'],
    '/api/prs/cases'           => [PrsController::class, 'casesApi'],
    '/api/prs/cases/{id}'      => [PrsController::class, 'caseDetailApi'],
    '/api/prs/analytics'       => [PrsController::class, 'analyticsApi'],
    '/api/prs/timeline'        => [PrsController::class, 'timelineApi'],
    '/api/prs/cross-ref'       => [PrsController::class, 'crossRefApi'],

    // ── Feeds ──
    '/rss'                     => [HomeController::class, 'rss'],
    '/sitemap.xml'             => [HomeController::class, 'sitemap'],

    // ── Admin ──
    '/admin'                   => [AdminController::class, 'dashboard'],
    '/admin/login'             => [AdminController::class, 'login'],
    '/admin/logout'            => [AdminController::class, 'logout'],
    '/admin/articles'          => [AdminArticlesController::class, 'list'],
    '/admin/articles/new'      => [AdminArticlesController::class, 'create'],
    '/admin/articles/{id}/edit'=> [AdminArticlesController::class, 'edit'],
    '/admin/cases'             => [AdminCasesController::class, 'list'],
    '/admin/cases/new'         => [AdminCasesController::class, 'create'],
    '/admin/cases/{id}/edit'   => [AdminCasesController::class, 'edit'],
    '/admin/organizations'     => [AdminOrganizationsController::class, 'list'],
    '/admin/organizations/{id}/edit' => [AdminOrganizationsController::class, 'edit'],
    '/admin/documents'         => [AdminDocumentsController::class, 'list'],
    '/admin/documents/{id}/edit' => [AdminDocumentsController::class, 'edit'],
    '/admin/submissions'       => [AdminSubmissionsController::class, 'list'],
    '/admin/submissions/{id}'  => [AdminSubmissionsController::class, 'review'],
    '/admin/updates'           => [AdminUpdatesController::class, 'list'],
    '/admin/updates/new'       => [AdminUpdatesController::class, 'create'],
    '/admin/updates/{id}/edit' => [AdminUpdatesController::class, 'edit'],
    '/admin/media'             => [AdminMediaController::class, 'list'],
    '/admin/media/new'         => [AdminMediaController::class, 'create'],
    '/admin/media/{id}/edit'   => [AdminMediaController::class, 'edit'],
    '/admin/prs'               => [AdminPrsController::class, 'list'],
    '/admin/prs/import'        => [AdminPrsController::class, 'import'],
    '/admin/prs/quality'       => [AdminPrsController::class, 'quality'],
    '/admin/prs/new'           => [AdminPrsController::class, 'create'],
    '/admin/prs/{id}/edit'     => [AdminPrsController::class, 'edit'],
    '/admin/prs/{id}/delete'   => ['POST', AdminPrsController::class, 'delete'],
];

<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/content.php';
/** Public <head> metadata. Falls back to the original static values if the DB is unavailable (never leaks errors). */
function public_meta(): array {
    $d = ['title' => 'Riel Akuonza — Informatics Student & Developer', 'description' => 'Personal portfolio of Riel Akuonza: biodata, skills, projects, and learning journey.',
        'og_title' => 'Riel Akuonza — Portfolio', 'og_description' => 'Informatics student and developer. Projects, skills, and journey.', 'theme_color' => '#f6f7f5',
        'favicon' => 'assets/images/favicon.svg', 'gsv' => 'LU7APQYFrLwWOgprYc2R_SQsKXa_kDBpWbjGVyHI-K4'];
    try {
        $m = ['title' => setting('seo_title', $d['title']), 'description' => setting('seo_description', $d['description']), 'og_title' => setting('og_title', $d['og_title']),
            'og_description' => setting('og_description', $d['og_description']), 'theme_color' => setting('theme_color', $d['theme_color']), 'favicon' => setting('favicon_url', $d['favicon']),
            'gsv' => setting('google_site_verification', $d['gsv'])];
    } catch (Throwable $e) { $m = $d; }
    $m['favicon_type'] = preg_match('/\.svg(\?|$)/i', $m['favicon']) ? 'image/svg+xml' : '';
    return $m;
}

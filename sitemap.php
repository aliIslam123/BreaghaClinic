<?php
header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');

require_once __DIR__ . '/config/connection.php';

$base = 'https://breaghaclinic.com';

$pages = [
    ['loc' => '/',                    'priority' => '1.0',   'changefreq' => 'weekly',  'lastmod' => date('Y-m-d')],
    ['loc' => '/about',               'priority' => '0.8',   'changefreq' => 'monthly', 'lastmod' => date('Y-m-d')],
    ['loc' => '/services',            'priority' => '0.9',   'changefreq' => 'weekly',  'lastmod' => date('Y-m-d')],
    ['loc' => '/offers',              'priority' => '0.9',   'changefreq' => 'weekly',  'lastmod' => date('Y-m-d')],
];

$services_result = $conn->query("SELECT id, updated_at FROM services WHERE visible = 1 ORDER BY id ASC");
if ($services_result) {
    while ($svc = $services_result->fetch_assoc()) {
        $lastmod = !empty($svc['updated_at']) ? date('Y-m-d', strtotime($svc['updated_at'])) : date('Y-m-d');
        $pages[] = [
            'loc' => '/services?id=' . intval($svc['id']),
            'priority' => '0.7',
            'changefreq' => 'monthly',
            'lastmod' => $lastmod,
        ];
    }
}

$offers_result = $conn->query("SELECT id, updated_at FROM offers WHERE visible = 1 ORDER BY id ASC");
if ($offers_result) {
    while ($off = $offers_result->fetch_assoc()) {
        $lastmod = !empty($off['updated_at']) ? date('Y-m-d', strtotime($off['updated_at'])) : date('Y-m-d');
        $pages[] = [
            'loc' => '/offers?id=' . intval($off['id']),
            'priority' => '0.7',
            'changefreq' => 'weekly',
            'lastmod' => $lastmod,
        ];
    }
}
?>
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
<?php foreach ($pages as $pg): ?>
    <url>
        <loc><?= htmlspecialchars($base . $pg['loc'], ENT_QUOTES, 'UTF-8') ?></loc>
        <lastmod><?= $pg['lastmod'] ?></lastmod>
        <changefreq><?= $pg['changefreq'] ?></changefreq>
        <priority><?= $pg['priority'] ?></priority>
    </url>
<?php endforeach; ?>
</urlset>

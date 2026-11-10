<?php
/**
 * Dynamic Insight / Blog Detail Page
 * Uses the exact design, branding and layout of templates/article-template.html
 */

require_once __DIR__ . '/config/db.php';

$slug = trim($_GET['slug'] ?? '');
$id = (int)($_GET['id'] ?? 0);

$article = null;

try {
    $db = getDB();

    if (!empty($slug)) {
        $stmt = $db->prepare("SELECT * FROM insights WHERE slug = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$slug]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$article && $id > 0) {
        $stmt = $db->prepare("SELECT * FROM insights WHERE id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$id]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $article = null;
}

// If external insight accessed directly, redirect to source link
if ($article && ($article['insight_type'] === 'external' || !empty($article['is_external']))) {
    $externalUrl = $article['link_url'];
    if (!empty($externalUrl) && strpos($externalUrl, 'insight.php') === false) {
        header('Location: ' . $externalUrl, true, 302);
        exit;
    }
}

// 404 handler if article not found
if (!$article) {
    http_response_code(404);
}

// Metadata preparations
$pageTitle = $article ? ($article['seo_title'] ?: $article['title']) : 'Article Not Found';
$metaDescription = $article ? ($article['seo_description'] ?: ($article['headline'] . ' - ' . $article['description'])) : 'The requested article could not be found.';
$bannerBg = !empty($article['banner_bg']) ? $article['banner_bg'] : '#005B94';
$bannerTextColor = !empty($article['banner_text_color']) ? $article['banner_text_color'] : '#FFFFFF';
$badgeColor = !empty($article['badge_color']) ? $article['badge_color'] : '#FFD200';
$authorName = !empty($article['author_name']) ? $article['author_name'] : 'Nilesh Biniwale';
$authorRole = !empty($article['author_role']) ? $article['author_role'] : 'Co-author, The ALT HQ';
$authorPhoto = !empty($article['author_photo']) ? $article['author_photo'] : 'assets/author-biniwale.jpg';
$authorBio = !empty($article['author_bio']) ? $article['author_bio'] : '';
$authorLinkedin = !empty($article['author_linkedin']) ? $article['author_linkedin'] : 'https://www.linkedin.com/in/nileshbiniwale/';
$readTime = !empty($article['read_time']) ? $article['read_time'] : '6 min read';
$topBadge = !empty($article['top_badge']) ? $article['top_badge'] : (!empty($article['category']) ? $article['category'] : 'Insights');
$subtitle = !empty($article['subtitle']) ? $article['subtitle'] : (!empty($article['headline']) ? $article['headline'] : '');
$ogImage = !empty($article['featured_image']) ? $article['featured_image'] : 'assets/cover-front-flat.jpg';
?>
<!doctype html>
<html lang="en-IN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> | The ALT HQ</title>
<meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
<meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?> | The ALT HQ">
<meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
<meta property="og:type" content="article">
<meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
<meta name="theme-color" content="#005B94">
<link rel="icon" href="assets/seal.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/article.css?v=<?= filemtime(__DIR__ . '/css/article.css') ?>">
</head>
<body>
<div style="font-family: 'Inter', -apple-system, sans-serif; color: #1E2229; background: #FFFFFF; width: 100%; min-height: 100vh; display: flex; flex-direction: column;">

<header class="art-pad" style="position: sticky; top: 0; z-index: 10; background: #FFFFFF; border-bottom: 1px solid #E2E8F0; padding: 0 64px">
<nav style="max-width: 1240px; margin: 0 auto; height: 76px; display: flex; align-items: center; justify-content: space-between; gap: 24px" aria-label="Main">
<a href="index.php" aria-label="The ALT HQ home" style="display: flex; align-items: center; text-decoration: none"><img src="assets/wordmark.png" alt="The ALT HQ" class="art-logo" style="height: 46px; width: auto; display: block"></a>
<div class="art-nav-links" style="display: flex; align-items: center; gap: 32px; font-size: 15px; font-weight: 500">
<a href="index.php#book" style="color: #1E2229; text-decoration: none">The Book</a>
<a href="index.php#authors" style="color: #1E2229; text-decoration: none">Authors</a>
<a href="index.php#insights" style="color: #005B94; text-decoration: none; font-weight: 600">Insights</a>
<a href="index.php#work" style="color: #1E2229; text-decoration: none">Work With Us</a>
</div>
<a href="index.php#buy" style="display: inline-flex; align-items: center; min-height: 44px; padding: 0 22px; border-radius: 999px; background: #FFD200; color: #1E2229; font-weight: 600; font-size: 15px; text-decoration: none">Buy the book</a>
</nav>
</header>

<?php if (!$article): ?>
<!-- 404 Not Found Display -->
<main style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 80px 24px;">
    <div style="max-width: 600px; text-align: center;">
        <span style="font-size: 13px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #005B94;">404 · Not Found</span>
        <h1 style="font-family: 'Montserrat', sans-serif; font-weight: 900; font-size: 38px; color: #1E2229; margin: 16px 0;">Insight Article Not Found</h1>
        <p style="font-size: 17px; line-height: 1.6; color: #4A5568; margin-bottom: 28px;">The insight or article you are looking for may have been moved, unpublished, or is no longer available.</p>
        <a href="index.php#insights" style="display: inline-flex; align-items: center; min-height: 46px; padding: 0 24px; border-radius: 999px; background: #005B94; color: #FFFFFF; font-weight: 600; font-size: 15px; text-decoration: none;">← Back to All Insights</a>
    </div>
</main>
<?php else: ?>
<!-- Article Full Content -->
<article style="flex: 1;">
<div class="art-pad" style="background: <?= htmlspecialchars($bannerBg) ?>; color: <?= htmlspecialchars($bannerTextColor) ?>; padding: 72px 64px 64px">
<div style="max-width: 760px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px">
<a href="index.php#insights" style="color: <?= ($bannerBg === '#FFD200') ? '#005B94' : '#CBE6F5' ?>; font-size: 14px; font-weight: 500; text-decoration: none">← All insights</a>
<span style="font-size: 13px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: <?= htmlspecialchars($badgeColor) ?>"><?= htmlspecialchars($topBadge) ?></span>
<h1 class="art-h1" style="margin: 0; font-family: 'Montserrat', sans-serif; font-weight: 900; font-size: 54px; line-height: 1.08; letter-spacing: -0.01em; color: <?= htmlspecialchars($bannerTextColor) ?>"><?= htmlspecialchars($article['title']) ?></h1>
<?php if (!empty($subtitle)): ?>
<p style="margin: 0; font-size: 22px; line-height: 1.45; color: <?= ($bannerBg === '#FFD200') ? '#1E2229' : '#E2EEF6' ?>"><?= htmlspecialchars($subtitle) ?></p>
<?php endif; ?>
<div style="display: flex; align-items: center; gap: 16px; padding-top: 12px">
<img src="<?= htmlspecialchars($authorPhoto) ?>" alt="<?= htmlspecialchars($authorName) ?>" style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid #FFD200; display: block">
<div style="display: flex; flex-direction: column; gap: 2px">
<span style="font-weight: 600; font-size: 16px; color: <?= htmlspecialchars($bannerTextColor) ?>"><?= htmlspecialchars($authorName) ?></span>
<span style="font-size: 14px; color: <?= ($bannerBg === '#FFD200') ? '#4A5568' : '#CBE6F5' ?>"><?= htmlspecialchars($authorRole) ?><?php if (!empty($readTime)): ?>  ·  <?= htmlspecialchars($readTime) ?><?php endif; ?></span>
</div>
</div>
</div>
</div>

<div class="art-pad art-body" style="padding: 56px 64px 24px">
<div style="max-width: 720px; margin: 0 auto">
<?php if (!empty($article['featured_image']) && strpos($article['content'] ?? '', $article['featured_image']) === false): ?>
<figure style="margin: 0 0 36px">
<img src="<?= htmlspecialchars($article['featured_image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" style="width: 100%; height: auto; display: block; border-radius: 12px; border: 1px solid #E2E8F0">
</figure>
<?php endif; ?>

<?= $article['content'] ?>
</div>
</div>

<div class="art-pad" style="padding: 24px 64px 88px">
<div style="max-width: 720px; margin: 0 auto; display: flex; flex-direction: column; gap: 28px">
<?php if (!empty($authorBio)): ?>
<div class="art-author-card" style="display: flex; gap: 20px; align-items: flex-start; padding: 28px; border-radius: 14px; border: 1px solid #E2E8F0">
<img src="<?= htmlspecialchars($authorPhoto) ?>" alt="<?= htmlspecialchars($authorName) ?>" style="width: 88px; height: 110px; object-fit: cover; border: 3px solid #FFD200; border-radius: 6px; flex-shrink: 0; display: block">
<div style="display: flex; flex-direction: column; gap: 8px">
<span style="font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 19px"><?= htmlspecialchars($authorName) ?></span>
<p style="margin: 0; font-size: 15px; line-height: 1.6; color: #4A5568"><?= htmlspecialchars($authorBio) ?></p>
<?php if (!empty($authorLinkedin)): ?>
<a href="<?= htmlspecialchars($authorLinkedin) ?>" target="_blank" rel="noopener" style="font-size: 15px; font-weight: 600">Follow on LinkedIn →</a>
<?php endif; ?>
</div>
</div>
<?php endif; ?>

<div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 20px; padding: 28px 32px; border-radius: 14px; background: #005B94; color: #FFFFFF">
<div style="display: flex; flex-direction: column; gap: 6px; max-width: 420px">
<span style="font-family: 'Montserrat', sans-serif; font-weight: 800; font-size: 21px">The ALT HQ: GCC Beyond The Obvious</span>
<span style="font-size: 15px; line-height: 1.5; color: #DCEBF5">How Global Capability Centres can become true alternate headquarters.</span>
</div>
<a href="index.php#buy" style="display: inline-flex; align-items: center; min-height: 48px; padding: 0 24px; border-radius: 999px; background: #FFD200; color: #1E2229; font-weight: 600; font-size: 15px; text-decoration: none">Get the book</a>
</div>
</div>
</div>
</article>
<?php endif; ?>

<footer class="art-pad" style="background: #005B94; color: #FFFFFF; padding: 40px 64px">
<div style="max-width: 1240px; margin: 0 auto; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 20px">
<img src="assets/seal.png" alt="The ALT HQ seal" style="width: 72px; height: 72px; display: block">
<span style="font-size: 14px; color: #DCEBF5">© 2026 Nilesh Biniwale and Nilesh Kulkarni.</span>
</div>
</footer>

</div>
</body>
</html>

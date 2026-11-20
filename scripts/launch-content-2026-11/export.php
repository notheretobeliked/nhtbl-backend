<?php
/**
 * Writes content.json: the content edited locally since the 16 November 2026
 * sync from live, for import.php to apply on live. Run locally:
 *
 *   wp eval-file scripts/launch-content-2026-11/export.php
 *
 * Needs the scratch database nhtbl_live_20261116 (live as it was at the sync),
 * which gives each existing post's "baseline" modified time. import.php
 * refuses to overwrite a post that has been edited on live since.
 */
global $wpdb;
$baselineDb = 'nhtbl_live_20261116';
$local = 'http://nhtbl-backend.test';
$site = fn ($s) => str_replace($local, '{{SITE}}', (string) $s);

// The six About images, by their local attachment id.
$media = [];
foreach ([3008, 3009, 3010, 3011, 3012, 3013] as $id) {
    $file = basename(get_attached_file($id));
    $media[] = [
        'key' => pathinfo($file, PATHINFO_FILENAME),
        'file' => pathinfo($file, PATHINFO_FILENAME) . '.jpg',
        'local_id' => $id,
        'local_url' => $site(wp_get_attachment_url($id)),
        'title' => get_the_title($id),
        'alt' => get_post_meta($id, '_wp_attachment_image_alt', true),
    ];
}

$terms = fn ($id, $tax) => array_map(fn ($t) => ['slug' => $t->slug, 'name' => $t->name], wp_get_post_terms($id, $tax));
$baseline = fn ($id) => $wpdb->get_var($wpdb->prepare("SELECT post_modified_gmt FROM $baselineDb.wp_posts WHERE ID = %d", $id));

$posts = [];

// Pages whose text changed. Privacy policy was a draft of WordPress's sample text.
foreach (['about', 'portfolio', 'support-us', 'workshops', 'digital', 'privacy-policy'] as $slug) {
    // By slug alone: some of these sit under Services, and a draft has no path yet.
    $p = get_posts(['post_type' => 'page', 'name' => $slug, 'post_status' => 'any', 'numberposts' => 1])[0];
    $posts[] = [
        'type' => 'page', 'slug' => $slug, 'id' => $p->ID, 'title' => $p->post_title, 'status' => $p->post_status,
        'set' => ['post_content' => $site($p->post_content), 'post_status' => $p->post_status, 'post_name' => $slug],
        'baseline_modified_gmt' => $baseline($p->ID),
    ];
}

// Existing projects: a typo fix and the Sainsbury Centre as client on one, the exhibition type on two.
foreach ([1179 => ['post_content'], 1044 => []] as $id => $fields) {
    $p = get_post($id);
    $set = [];
    foreach ($fields as $f) $set[$f] = $site($p->$f);
    $posts[] = [
        'type' => 'project', 'slug' => $p->post_name, 'title' => $p->post_title, 'status' => $p->post_status,
        'set' => $set,
        'terms' => ['client' => $terms($id, 'client'), 'project_type' => $terms($id, 'project_type')],
        'baseline_modified_gmt' => $baseline($id),
    ];
}

// New list-only entries (talks, workshops, exhibitions): the published ones.
$new = get_posts(['post_type' => 'project', 'post_status' => 'publish', 'numberposts' => -1, 'meta_key' => '_nhtbl_prototype_entry', 'orderby' => 'ID', 'order' => 'ASC']);
foreach ($new as $p) {
    $posts[] = [
        'type' => 'project', 'slug' => $p->post_name, 'title' => $p->post_title, 'status' => 'publish', 'new' => true,
        'set' => ['post_title' => $p->post_title, 'post_excerpt' => $p->post_excerpt, 'post_content' => $site($p->post_content), 'post_status' => 'publish', 'post_date' => $p->post_date, 'post_date_gmt' => $p->post_date_gmt],
        'terms' => ['client' => $terms($p->ID, 'client'), 'project_type' => $terms($p->ID, 'project_type'), 'service' => $terms($p->ID, 'service')],
        'meta' => [
            'start_date' => get_post_meta($p->ID, 'start_date', true), '_start_date' => 'field_project_data_start_date',
            'end_date' => get_post_meta($p->ID, 'end_date', true), '_end_date' => 'field_project_data_end_date',
        ],
        'baseline_modified_gmt' => null,
    ];
}

$out = ['exported_at' => gmdate('c'), 'media' => $media, 'posts' => $posts];
file_put_contents(__DIR__ . '/content.json', json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
echo count($posts) . ' posts and ' . count($media) . " images written to content.json\n";

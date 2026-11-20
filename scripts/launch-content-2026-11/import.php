<?php
/**
 * Applies content.json (written by export.php) to this site: new list-only
 * projects, edited pages, their terms and the About page's images.
 *
 *   wp eval-file scripts/launch-content-2026-11/import.php            # dry run: lists what it would do
 *   wp eval-file scripts/launch-content-2026-11/import.php write      # does it
 *   wp eval-file scripts/launch-content-2026-11/import.php write force  # also overwrites posts edited on live since the sync
 *
 * Posts are matched by type and slug. An existing post is only overwritten if
 * it hasn't been edited on live since the sync the local copy was made from
 * (or was last written by this script). Safe to run twice.
 */
$write = in_array('write', $args ?? [], true);
$force = in_array('force', $args ?? [], true);
const NHTBL_IMPORT_MARK = '_nhtbl_import_2026_11';

$data = json_decode(file_get_contents(__DIR__ . '/content.json'), true);
if (!$data) { WP_CLI::error('content.json is missing or not valid JSON.'); }
foreach (['project_type', 'client'] as $tax) {
    if (!taxonomy_exists($tax)) { WP_CLI::error("The $tax taxonomy isn't registered here. Deploy the backend code first."); }
}
// Content goes in as written: no HTML filtering for a script with no signed-in user.
kses_remove_filters();
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$say = fn ($verb, $what) => WP_CLI::log(sprintf('%-12s %s', ($write ? '' : 'would ') . $verb, $what));
$counts = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0, 'images' => 0];

// 1. Images. Each is found again by its key, so a second run uploads nothing.
$swap = [];
foreach ($data['media'] as $m) {
    $found = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'meta_key' => NHTBL_IMPORT_MARK, 'meta_value' => $m['key'], 'fields' => 'ids']);
    $id = $found[0] ?? 0;
    if (!$id) {
        $say('upload', $m['file']);
        $counts['images']++;
        if ($write) {
            $tmp = wp_tempnam($m['file']);
            copy(__DIR__ . '/media/' . $m['file'], $tmp);
            $id = media_handle_sideload(['name' => $m['file'], 'tmp_name' => $tmp], 0, $m['title']);
            if (is_wp_error($id)) { WP_CLI::error("Couldn't upload {$m['file']}: " . $id->get_error_message()); }
            update_post_meta($id, '_wp_attachment_image_alt', $m['alt']);
            update_post_meta($id, NHTBL_IMPORT_MARK, $m['key']);
        }
    }
    if ($id) {
        $swap[$m['local_url']] = wp_get_attachment_url($id);
        $swap['"id":' . $m['local_id'] . ','] = '"id":' . $id . ',';
        $swap['wp-image-' . $m['local_id'] . '"'] = 'wp-image-' . $id . '"';
    }
}
$home = untrailingslashit(home_url());
$localise = fn ($s) => str_replace('{{SITE}}', $home, strtr((string) $s, $swap));

// 2. Posts.
$announced = [];
foreach ($data['posts'] as $p) {
    $label = "{$p['type']} {$p['slug']}";
    $found = get_posts(['post_type' => $p['type'], 'name' => $p['slug'], 'post_status' => 'any', 'numberposts' => 1]);
    $post = $found[0] ?? null;
    // A draft has no slug yet (the privacy page): fall back to the same id, if it's the same page.
    if (!$post && !empty($p['id'])) {
        $byId = get_post($p['id']);
        if ($byId && $byId->post_type === $p['type'] && $byId->post_title === $p['title']) { $post = $byId; }
    }
    $ours = $post && get_post_meta($post->ID, NHTBL_IMPORT_MARK, true);

    if (!$post && empty($p['new'])) { WP_CLI::warning("$label isn't on this site; skipped."); $counts['skipped']++; continue; }
    if ($post && !$ours && !$force) {
        if (!empty($p['new'])) { WP_CLI::warning("$label already exists here and wasn't made by this script; skipped."); $counts['skipped']++; continue; }
        if ($post->post_modified_gmt !== $p['baseline_modified_gmt']) {
            WP_CLI::warning("$label was edited on this site ({$post->post_modified_gmt} GMT) after the sync ({$p['baseline_modified_gmt']}); skipped. Add 'force' to overwrite.");
            $counts['skipped']++; continue;
        }
    }

    $set = array_map($localise, $p['set'] ?: []);
    $changes = [];
    if ($post) {
        foreach ($set as $field => $value) { if ((string) $post->$field !== $value) { $changes[] = $field; } }
    }
    $termIds = [];
    foreach ($p['terms'] ?? [] as $tax => $terms) {
        $termIds[$tax] = [];
        foreach ($terms as $t) {
            $term = get_term_by('slug', $t['slug'], $tax);
            if (!$term) {
                if (empty($announced["$tax/{$t['slug']}"])) { $say('add term', "$tax: {$t['name']}"); $announced["$tax/{$t['slug']}"] = true; }
                if ($write) { $made = wp_insert_term($t['name'], $tax, ['slug' => $t['slug']]); $term = get_term($made['term_id'], $tax); }
            }
            if ($term) { $termIds[$tax][] = (int) $term->term_id; }
        }
        if ($post) {
            $now = wp_get_object_terms($post->ID, $tax, ['fields' => 'slugs']);
            $want = array_column($terms, 'slug');
            sort($now); sort($want);
            if ($now !== $want) { $changes[] = "$tax (" . (implode(', ', $now) ?: 'none') . ' → ' . implode(', ', $want) . ')'; }
        }
    }
    foreach ($p['meta'] ?? [] as $key => $value) {
        if ($post && (string) get_post_meta($post->ID, $key, true) !== (string) $value) { $changes[] = $key; }
    }

    if (!$post) {
        $say('create', "$label — {$p['title']}");
        $counts['created']++;
        if ($write) {
            $id = wp_insert_post(wp_slash($set + ['post_type' => $p['type'], 'post_name' => $p['slug']]), true);
            if (is_wp_error($id)) { WP_CLI::error("Couldn't create $label: " . $id->get_error_message()); }
        }
    } elseif ($changes) {
        $say('update', "$label: " . implode(', ', $changes));
        $counts['updated']++;
        $id = $post->ID;
        if ($write && $set) {
            $r = wp_update_post(wp_slash(['ID' => $id] + $set), true);
            if (is_wp_error($r)) { WP_CLI::error("Couldn't update $label: " . $r->get_error_message()); }
        }
    } else {
        $counts['unchanged']++;
        continue;
    }
    if ($write) {
        foreach ($termIds as $tax => $ids) { wp_set_object_terms($id, $ids, $tax); }
        foreach ($p['meta'] ?? [] as $key => $value) { update_post_meta($id, $key, $value); }
        update_post_meta($id, NHTBL_IMPORT_MARK, $data['exported_at']);
    }
}

WP_CLI::log('');
WP_CLI::log(sprintf('%s%d created, %d updated, %d already up to date, %d skipped, %d images uploaded.', $write ? '' : 'Dry run: ', $counts['created'], $counts['updated'], $counts['unchanged'], $counts['skipped'], $counts['images']));
if (!$write) { WP_CLI::log("Nothing was changed. Run again with 'write' to apply."); }

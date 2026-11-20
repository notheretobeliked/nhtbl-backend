<?php
/**
 * Merges the project types "Workshop" and "Talk or teaching" into one,
 * "Talks and workshops", and points the Work page's two filter links at it.
 * Nothing needs re-tagging by hand. Dry run unless given 'write':
 *
 *   wp eval-file scripts/launch-content-2026-11/merge-talks-workshops.php
 *   wp eval-file scripts/launch-content-2026-11/merge-talks-workshops.php write
 *
 * Safe to run twice.
 */
$write = in_array('write', $args ?? [], true);
$tax = 'project_type';
$name = 'Talks and workshops';
$slug = 'talks-and-workshops';
$say = fn ($s) => WP_CLI::log(($write ? '' : 'would ') . $s);
kses_remove_filters();

$keep = get_term_by('slug', $slug, $tax) ?: get_term_by('slug', 'talk-or-teaching', $tax);
$drop = get_term_by('slug', 'workshop', $tax);
if (!$keep) { WP_CLI::error('Neither "talk-or-teaching" nor "talks-and-workshops" exists here.'); }

if ($drop) {
    $ids = get_objects_in_term($drop->term_id, $tax);
    $say('move ' . count($ids) . ' entries from "Workshop" to "' . $keep->name . '", and delete "Workshop"');
    if ($write) {
        foreach ($ids as $id) { wp_set_object_terms((int) $id, [(int) $keep->term_id], $tax, true); }
        wp_delete_term($drop->term_id, $tax);
    }
}
if ($keep->name !== $name || $keep->slug !== $slug) {
    $say("rename \"{$keep->name}\" to \"$name\"");
    if ($write) { wp_update_term($keep->term_id, $tax, ['name' => $name, 'slug' => $slug]); }
}

$work = get_posts(['post_type' => 'page', 'name' => 'portfolio', 'numberposts' => 1])[0] ?? null;
if ($work) {
    $content = str_replace(['#filter=workshop"', '#filter=talk-or-teaching"'], "#filter=$slug\"", $work->post_content, $n);
    if ($n) {
        $say("point $n filter links on the Work page at the merged type");
        if ($write) { wp_update_post(wp_slash(['ID' => $work->ID, 'post_content' => $content])); }
    }
}
WP_CLI::log($write ? 'Done.' : "Dry run: nothing was changed. Add 'write' to apply.");

<?php
/**
 * One-time migration: wrap each top-level block of every `project` (portfolio)
 * post in a full-bleed CoreGroup "section", per the portfolio layout spec.
 *
 * Rules (per top-level block):
 *   - First block  → sticky full-bleed section. Group:
 *       {align:full, behavior:stick, minHeight:screen, contentAlign:stretch,
 *        layout:{type:default}}  (no background); enclosed content → align:full.
 *   - core/image   → grey section. Group:
 *       {align:full, minHeight:screen, backgroundColor:nhtbl-grey-base,
 *        layout:{type:constrained}}; image → align:wide.
 *   - anything else → black section. Group:
 *       {align:full, minHeight:screen, backgroundColor:black,
 *        layout:{type:constrained}}; content left untouched (assumed to already
 *        carry its own inner styled group, e.g. black bg / white text / padding).
 *
 * USAGE (run from the backend, where `wp` resolves the install):
 *   wp eval-file scripts/wrap-portfolio-blocks.php            # DRY RUN (prints, no writes)
 *   wp eval-file scripts/wrap-portfolio-blocks.php apply       # write changes
 *   wp eval-file scripts/wrap-portfolio-blocks.php apply 123   # only post 123
 *   wp eval-file scripts/wrap-portfolio-blocks.php 123         # dry run, only post 123
 *
 * Back up the DB first (e.g. `wp db export`). Idempotent: posts whose top-level
 * blocks are already wrapped groups (carry our `minHeight` attr) are skipped.
 */

if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) {
    fwrite(STDERR, "Run this with: wp eval-file scripts/wrap-portfolio-blocks.php\n");
    exit(1);
}

$args    = isset($args) && is_array($args) ? $args : [];
$apply   = in_array('apply', $args, true);
$only_id = 0;
foreach ($args as $a) {
    if (ctype_digit((string) $a)) {
        $only_id = (int) $a;
    }
}

$GREY = 'nhtbl-grey-base';

/** Build a core/group wrapper around a single child block. */
$make_group = function (array $attrs, string $wrapper_class, array $child): array {
    return [
        'blockName'    => 'core/group',
        'attrs'        => $attrs,
        'innerBlocks'  => [$child],
        'innerHTML'    => "\n<div class=\"{$wrapper_class}\"></div>\n",
        'innerContent' => ["\n<div class=\"{$wrapper_class}\">", null, "</div>\n"],
    ];
};

/**
 * Set a core/image block's alignment attribute AND its figure class, keeping
 * WP's class order ("wp-block-image align{X} size-..."), so the block stays
 * valid in the editor.
 */
$set_image_align = function (array $block, string $align): array {
    $block['attrs']['align'] = $align;
    $fix = function ($html) use ($align) {
        if (preg_match('/wp-block-image\s+align(full|wide|left|right|center)/', $html)) {
            return preg_replace(
                '/(wp-block-image\s+)align(full|wide|left|right|center)/',
                '${1}align' . $align,
                $html
            );
        }
        return preg_replace('/wp-block-image/', 'wp-block-image align' . $align, $html, 1);
    };
    $block['innerHTML']    = $fix($block['innerHTML']);
    $block['innerContent'] = array_map(fn ($c) => is_string($c) ? $fix($c) : $c, $block['innerContent']);
    return $block;
};

$ids = get_posts([
    'post_type'      => 'project',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'orderby'        => 'ID',
    'order'          => 'ASC',
] + ($only_id ? ['p' => $only_id] : []));

WP_CLI::log(sprintf(
    '%s %d project post(s)%s',
    $apply ? 'Processing' : '[DRY RUN] Inspecting',
    count($ids),
    $only_id ? " (id {$only_id})" : ''
));

$changed = 0;

foreach ($ids as $post_id) {
    $post   = get_post($post_id);
    $blocks = parse_blocks($post->post_content);

    // Bail out on classic / freeform content rather than risk losing it.
    foreach ($blocks as $b) {
        if (empty($b['blockName']) && trim((string) $b['innerHTML']) !== '') {
            WP_CLI::warning("#{$post_id} \"{$post->post_title}\": contains non-block (classic) content — skipped for safety");
            continue 2;
        }
    }

    // Real top-level blocks only (drop whitespace separators).
    $real = array_values(array_filter($blocks, fn ($b) => !empty($b['blockName'])));

    if (empty($real)) {
        WP_CLI::log("  - #{$post_id} \"{$post->post_title}\": no blocks, skipped");
        continue;
    }

    // Already wrapped? (every top-level block is a group carrying our minHeight)
    $already = array_reduce(
        $real,
        fn ($carry, $b) => $carry && $b['blockName'] === 'core/group' && isset($b['attrs']['minHeight']),
        true
    );
    if ($already) {
        WP_CLI::log("  - #{$post_id} \"{$post->post_title}\": already wrapped, skipped");
        continue;
    }

    $wrapped = [];
    foreach ($real as $i => $block) {
        $is_image = $block['blockName'] === 'core/image';

        if ($i === 0) {
            $child = $is_image ? $set_image_align($block, 'full') : $block;
            $wrapped[] = $make_group(
                [
                    'align'        => 'full',
                    'behavior'     => 'stick',
                    'minHeight'    => 'screen',
                    'contentAlign' => 'stretch',
                    'layout'       => ['type' => 'default'],
                ],
                'wp-block-group alignfull',
                $child
            );
        } elseif ($is_image) {
            $wrapped[] = $make_group(
                [
                    'align'           => 'full',
                    'minHeight'       => 'screen',
                    'backgroundColor' => $GREY,
                    'layout'          => ['type' => 'constrained'],
                ],
                "wp-block-group alignfull has-{$GREY}-background-color has-background",
                $set_image_align($block, 'wide')
            );
        } else {
            $wrapped[] = $make_group(
                [
                    'align'           => 'full',
                    'minHeight'       => 'screen',
                    'backgroundColor' => 'black',
                    'layout'          => ['type' => 'constrained'],
                ],
                'wp-block-group alignfull has-black-background-color has-background',
                $block
            );
        }
    }

    // Join top-level sections with a blank line, matching WP's own spacing.
    $new_content = implode("\n\n", array_map('serialize_block', $wrapped));

    WP_CLI::log(sprintf(
        '  - #%d "%s": %d block(s) → %d section(s)',
        $post_id,
        $post->post_title,
        count($real),
        count($wrapped)
    ));

    if ($apply) {
        // wp_update_post expects slashed data; disable kses so it doesn't strip
        // the <!-- wp:... --> block delimiters (HTML comments).
        kses_remove_filters();
        $result = wp_update_post(['ID' => $post_id, 'post_content' => wp_slash($new_content)], true);
        kses_init_filters();

        if (is_wp_error($result)) {
            WP_CLI::warning("    failed to update #{$post_id}: " . $result->get_error_message());
        } else {
            $changed++;
        }
    } else {
        WP_CLI::log("    ----- new content -----\n{$new_content}\n    -----------------------");
    }
}

WP_CLI::log($apply
    ? "Done. Updated {$changed} post(s)."
    : 'Dry run complete. Re-run with `apply` to write changes.');

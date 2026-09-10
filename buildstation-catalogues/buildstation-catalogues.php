<?php
/**
 * Plugin Name: Build Station Catalogues
 * Description: Manage brand catalogues with segment filters, PDF viewing, downloads, and one-click duplication.
 * Version: 1.0.0
 * Author: Build Station
 * Text Domain: buildstation-catalogues
 */

if (!defined('ABSPATH')) { exit; }

define('BSC_VERSION', '1.0.0');
define('BSC_DIR', plugin_dir_path(__FILE__));
define('BSC_URL', plugin_dir_url(__FILE__));

function bsc_register_content() {
    register_post_type('bs_catalogue', [
        'labels' => [
            'name' => 'Catalogues', 'singular_name' => 'Catalogue',
            'add_new_item' => 'Add New Catalogue', 'edit_item' => 'Edit Catalogue',
            'new_item' => 'New Catalogue', 'view_item' => 'View Catalogue',
            'search_items' => 'Search Catalogues', 'not_found' => 'No catalogues found',
        ],
        'public' => true, 'show_in_rest' => true, 'menu_icon' => 'dashicons-book-alt',
        'supports' => ['title', 'editor', 'thumbnail', 'page-attributes'],
        'rewrite' => ['slug' => 'catalogue'], 'has_archive' => false,
    ]);

    register_taxonomy('bs_segment', 'bs_catalogue', [
        'labels' => ['name' => 'Segments', 'singular_name' => 'Segment'],
        'public' => true, 'show_in_rest' => true, 'hierarchical' => true,
        'rewrite' => ['slug' => 'catalogue-segment'],
    ]);
}
add_action('init', 'bsc_register_content');

function bsc_meta_boxes() {
    add_meta_box('bsc_details', 'Brand & Catalogue Details', 'bsc_details_box', 'bs_catalogue', 'normal', 'high');
}
add_action('add_meta_boxes', 'bsc_meta_boxes');

function bsc_details_box($post) {
    wp_nonce_field('bsc_save_catalogue', 'bsc_nonce');
    $brand = get_post_meta($post->ID, '_bsc_brand', true);
    $logo = (int) get_post_meta($post->ID, '_bsc_logo_id', true);
    $pdf = (int) get_post_meta($post->ID, '_bsc_pdf_id', true);
    $edition = get_post_meta($post->ID, '_bsc_edition', true);
    $logo_url = $logo ? wp_get_attachment_image_url($logo, 'medium') : '';
    $pdf_url = $pdf ? wp_get_attachment_url($pdf) : '';
    ?>
    <div class="bsc-admin-grid">
        <p><label><strong>Brand name</strong></label><input type="text" name="bsc_brand" value="<?php echo esc_attr($brand); ?>" class="widefat" placeholder="e.g. Duravit"></p>
        <p><label><strong>Edition / subtitle</strong></label><input type="text" name="bsc_edition" value="<?php echo esc_attr($edition); ?>" class="widefat" placeholder="e.g. Edition 03"></p>
    </div>
    <p><strong>Brand logo</strong></p>
    <div class="bsc-media-field">
        <input type="hidden" name="bsc_logo_id" id="bsc_logo_id" value="<?php echo esc_attr($logo); ?>">
        <div id="bsc_logo_preview"><?php if ($logo_url): ?><img src="<?php echo esc_url($logo_url); ?>" alt=""><?php endif; ?></div>
        <button type="button" class="button bsc-select-media" data-target="logo" data-type="image">Choose Logo</button>
        <button type="button" class="button bsc-remove-media" data-target="logo">Remove</button>
    </div>
    <p><strong>Catalogue PDF</strong></p>
    <div class="bsc-media-field">
        <input type="hidden" name="bsc_pdf_id" id="bsc_pdf_id" value="<?php echo esc_attr($pdf); ?>">
        <div id="bsc_pdf_preview"><?php if ($pdf_url): ?><a href="<?php echo esc_url($pdf_url); ?>" target="_blank" rel="noopener"><?php echo esc_html(basename($pdf_url)); ?></a><?php endif; ?></div>
        <button type="button" class="button bsc-select-media" data-target="pdf" data-type="application/pdf">Choose PDF</button>
        <button type="button" class="button bsc-remove-media" data-target="pdf">Remove</button>
    </div>
    <p class="description">Use the Featured Image panel for the catalogue cover. Select a Segment before publishing.</p>
    <?php
}

function bsc_save_catalogue($post_id) {
    if (!isset($_POST['bsc_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bsc_nonce'])), 'bsc_save_catalogue')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    $text_fields = ['bsc_brand' => '_bsc_brand', 'bsc_edition' => '_bsc_edition'];
    foreach ($text_fields as $input => $key) {
        update_post_meta($post_id, $key, isset($_POST[$input]) ? sanitize_text_field(wp_unslash($_POST[$input])) : '');
    }
    foreach (['bsc_logo_id' => '_bsc_logo_id', 'bsc_pdf_id' => '_bsc_pdf_id'] as $input => $key) {
        update_post_meta($post_id, $key, isset($_POST[$input]) ? absint($_POST[$input]) : 0);
    }
}
add_action('save_post_bs_catalogue', 'bsc_save_catalogue');

function bsc_admin_assets($hook) {
    global $post_type;
    if ($post_type !== 'bs_catalogue') return;
    wp_enqueue_media();
    wp_enqueue_script('bsc-admin', BSC_URL . 'assets/admin.js', ['jquery'], BSC_VERSION, true);
    wp_enqueue_style('bsc-admin', BSC_URL . 'assets/admin.css', [], BSC_VERSION);
}
add_action('admin_enqueue_scripts', 'bsc_admin_assets');

function bsc_duplicate_link($actions, $post) {
    if ($post->post_type === 'bs_catalogue' && current_user_can('edit_posts')) {
        $url = wp_nonce_url(admin_url('admin.php?action=bsc_duplicate_catalogue&post=' . $post->ID), 'bsc_duplicate_' . $post->ID);
        $actions['bsc_duplicate'] = '<a href="' . esc_url($url) . '">Duplicate</a>';
    }
    return $actions;
}
add_filter('post_row_actions', 'bsc_duplicate_link', 10, 2);

function bsc_duplicate_catalogue() {
    $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
    if (!$post_id || !current_user_can('edit_post', $post_id)) wp_die('You do not have permission to duplicate this catalogue.');
    check_admin_referer('bsc_duplicate_' . $post_id);
    $source = get_post($post_id);
    if (!$source || $source->post_type !== 'bs_catalogue') wp_die('Catalogue not found.');
    $new_id = wp_insert_post([
        'post_type' => 'bs_catalogue', 'post_status' => 'draft',
        'post_title' => $source->post_title . ' - Copy', 'post_content' => $source->post_content,
        'post_excerpt' => $source->post_excerpt, 'menu_order' => $source->menu_order,
    ]);
    if (is_wp_error($new_id)) wp_die(esc_html($new_id->get_error_message()));
    foreach (get_post_meta($post_id) as $key => $values) {
        foreach ($values as $value) add_post_meta($new_id, $key, maybe_unserialize($value));
    }
    foreach (wp_get_object_terms($post_id, 'bs_segment', ['fields' => 'ids']) as $term_id) wp_set_object_terms($new_id, (int)$term_id, 'bs_segment', true);
    wp_safe_redirect(admin_url('post.php?action=edit&post=' . $new_id)); exit;
}
add_action('admin_action_bsc_duplicate_catalogue', 'bsc_duplicate_catalogue');

function bsc_catalogue_query($segment = '') {
    $args = ['post_type' => 'bs_catalogue', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC']];
    if ($segment) $args['tax_query'] = [['taxonomy' => 'bs_segment', 'field' => 'slug', 'terms' => sanitize_title($segment)]];
    return new WP_Query($args);
}

function bsc_catalogues_shortcode($atts) {
    $atts = shortcode_atts(['segment' => '', 'title' => 'Our Brands & Catalogues'], $atts, 'buildstation_catalogues');
    $query = bsc_catalogue_query($atts['segment']);
    wp_enqueue_style('bsc-front', BSC_URL . 'assets/front.css', [], BSC_VERSION);
    ob_start(); ?>
    <section class="bsc-library">
        <?php if ($atts['title']): ?><div class="bsc-heading"><span>BUILD STATION COLLECTION</span><h2><?php echo esc_html($atts['title']); ?></h2></div><?php endif; ?>
        <div class="bsc-grid">
        <?php if ($query->have_posts()): while ($query->have_posts()): $query->the_post();
            $id = get_the_ID(); $brand = get_post_meta($id, '_bsc_brand', true) ?: get_the_title();
            $logo_id = (int)get_post_meta($id, '_bsc_logo_id', true); $pdf_id = (int)get_post_meta($id, '_bsc_pdf_id', true);
            $pdf_url = $pdf_id ? wp_get_attachment_url($pdf_id) : '';
            ?>
            <article class="bsc-card">
                <a class="bsc-cover" href="<?php the_permalink(); ?>">
                    <?php if (has_post_thumbnail()): the_post_thumbnail('large', ['loading' => 'lazy']); else: ?><span class="bsc-placeholder">CATALOGUE</span><?php endif; ?>
                </a>
                <div class="bsc-card-body">
                    <?php if ($logo_id): echo wp_get_attachment_image($logo_id, 'medium', false, ['class' => 'bsc-logo', 'alt' => $brand]); endif; ?>
                    <p class="bsc-brand"><?php echo esc_html($brand); ?></p>
                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    <div class="bsc-actions"><a class="bsc-button bsc-primary" href="<?php the_permalink(); ?>">View Catalogue</a>
                    <?php if ($pdf_url): ?><a class="bsc-button" href="<?php echo esc_url($pdf_url); ?>" download>Download PDF</a><?php endif; ?></div>
                </div>
            </article>
        <?php endwhile; else: ?><p class="bsc-empty">No catalogues are available yet.</p><?php endif; wp_reset_postdata(); ?>
        </div>
    </section><?php return ob_get_clean();
}
add_shortcode('buildstation_catalogues', 'bsc_catalogues_shortcode');

function bsc_single_template($template) {
    if (is_singular('bs_catalogue')) return BSC_DIR . 'templates/single-bs_catalogue.php';
    return $template;
}
add_filter('single_template', 'bsc_single_template');

function bsc_activate() {
    bsc_register_content();
    foreach (['Sanitary Ware','Tiles & Surfaces','Lighting','Home Automation','Engineered Flooring','Water Solutions','Outdoor Living','Counters & Vanities','Building Solutions'] as $segment) {
        if (!term_exists($segment, 'bs_segment')) wp_insert_term($segment, 'bs_segment');
    }
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'bsc_activate');

function bsc_deactivate() { flush_rewrite_rules(); }
register_deactivation_hook(__FILE__, 'bsc_deactivate');

function bsc_columns($columns) {
    return ['cb'=>$columns['cb'], 'title'=>'Catalogue', 'brand'=>'Brand', 'segment'=>'Segment', 'pdf'=>'PDF', 'date'=>$columns['date']];
}
add_filter('manage_bs_catalogue_posts_columns', 'bsc_columns');
function bsc_column_content($column, $post_id) {
    if ($column === 'brand') echo esc_html(get_post_meta($post_id, '_bsc_brand', true));
    if ($column === 'segment') echo wp_kses_post(get_the_term_list($post_id, 'bs_segment', '', ', '));
    if ($column === 'pdf') echo get_post_meta($post_id, '_bsc_pdf_id', true) ? '<span style="color:#177245">Ready</span>' : '<span style="color:#a00">Missing</span>';
}
add_action('manage_bs_catalogue_posts_custom_column', 'bsc_column_content', 10, 2);

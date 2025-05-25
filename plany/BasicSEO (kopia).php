<?php
/**
 * Basic SEO
 * 
 * @package   BasicSEO
 * @author    https://github.com/Torwald45
 * @version   1.8.2
 * @pluginURI https://github.com/Torwald45/basic-seo
 * @changelog https://github.com/Torwald45/basic-seo/blob/main/CHANGELOG.md
 * Features:
 * - Custom Title Tag for pages, posts and WooCommerce (shop page & categories)
 * - Meta Description for pages, posts and WooCommerce (shop page & categories)
 * - Open Graph support (title, description, image)
 * - XML Sitemap with HTML display (pages, posts, products, categories)
 * - Quick Edit support in admin panel
 * - Breadcrumbs support via [basicseo-breadcrumb] shortcode
 * - Media attachments redirect (prevents duplicate content)
 */

// CONFIGURATION SECTION
// ======================
define('BSTV1_POST_TITLE', '_basicseotorvald_v1_post_title');
define('BSTV1_POST_DESC', '_basicseotorvald_v1_post_desc');
define('BSTV1_TERM_TITLE', '_basicseotorvald_v1_term_title');
define('BSTV1_TERM_DESC', '_basicseotorvald_v1_term_desc');


// SEO META TAGS OUTPUT (frontend only)
// =====================================
add_action('wp_head', 'basicseotorvald_v1_output_meta_tags');
function basicseotorvald_v1_output_meta_tags() {
    if (is_singular()) {
        global $post;
        $title = get_post_meta($post->ID, BSTV1_POST_TITLE, true);
        $desc  = get_post_meta($post->ID, BSTV1_POST_DESC, true);
        if ($title) echo "<title>" . esc_html($title) . "</title>\n";
        if ($desc) echo '<meta name="description" content="' . esc_attr($desc) . '" />\n';
        // Open Graph (optional image fallback)
        echo '<meta property="og:title" content="' . esc_attr($title ? $title : get_the_title()) . '" />\n';
        if ($desc) echo '<meta property="og:description" content="' . esc_attr($desc) . '" />\n';
        if (has_post_thumbnail($post->ID)) {
            $img = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
            if (!empty($img[0])) echo '<meta property="og:image" content="' . esc_url($img[0]) . '" />\n';
        }
    }
    if (is_tax() || is_category() || is_tag()) {
        $term = get_queried_object();
        $title = get_term_meta($term->term_id, BSTV1_TERM_TITLE, true);
        $desc  = get_term_meta($term->term_id, BSTV1_TERM_DESC, true);
        if ($title) echo "<title>" . esc_html($title) . "</title>\n";
        if ($desc) echo '<meta name="description" content="' . esc_attr($desc) . '" />\n';
        echo '<meta property="og:title" content="' . esc_attr($title ? $title : single_term_title('', false)) . '" />\n';
        if ($desc) echo '<meta property="og:description" content="' . esc_attr($desc) . '" />\n';
    }
}


// BREADCRUMBS SHORTCODE
// =======================
add_shortcode('basicseo-breadcrumb', 'basicseotorvald_v1_breadcrumb_shortcode');
function basicseotorvald_v1_breadcrumb_shortcode() {
    global $post;
    $output = '<nav class="basicseo-breadcrumb"><a href="' . home_url() . '">Home</a>';
    if (is_singular('post')) {
        $cats = get_the_category();
        if (!empty($cats)) {
            $output .= ' &raquo; <a href="' . get_category_link($cats[0]->term_id) . '">' . esc_html($cats[0]->name) . '</a>';
        }
        $output .= ' &raquo; ' . get_the_title();
    } elseif (is_page() && $post->post_parent) {
        $ancestors = array_reverse(get_post_ancestors($post));
        foreach ($ancestors as $ancestor) {
            $output .= ' &raquo; <a href="' . get_permalink($ancestor) . '">' . get_the_title($ancestor) . '</a>';
        }
        $output .= ' &raquo; ' . get_the_title();
    } elseif (is_category() || is_tag() || is_tax()) {
        $output .= ' &raquo; ' . single_term_title('', false);
    } elseif (is_search()) {
        $output .= ' &raquo; Search results for: ' . get_search_query();
    } elseif (is_404()) {
        $output .= ' &raquo; 404 Not Found';
    } else {
        $output .= ' &raquo; ' . get_the_title();
    }
    $output .= '</nav>';
    return $output;
}


// ATTACHMENT REDIRECT
// =====================
add_action('template_redirect', function () {
    if (is_attachment()) {
        global $post;
        if ($post && $post->post_parent) {
            wp_redirect(get_permalink($post->post_parent), 301);
            exit;
        } else {
            wp_redirect(home_url(), 301);
            exit;
        }
    }
});


// SITEMAP (XML & HTML)
// =====================
add_action('init', function () {
    if (isset($_GET['sitemap']) && $_GET['sitemap'] === '1') {
        header('Content-Type: text/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $posts = get_posts(['numberposts' => -1, 'post_type' => ['post', 'page', 'product'], 'post_status' => 'publish']);
        foreach ($posts as $post) {
            echo '<url><loc>' . get_permalink($post) . '</loc></url>';
        }
        $terms = get_terms(['taxonomy' => ['category', 'product_cat'], 'hide_empty' => false]);
        foreach ($terms as $term) {
            echo '<url><loc>' . get_term_link($term) . '</loc></url>';
        }
        echo '</urlset>';
        exit;
    }
});

add_action('template_redirect', function () {
    if (isset($_GET['sitemap']) && $_GET['sitemap'] === 'html') {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Sitemap</title></head><body>';
        echo '<h1>Sitemap</h1><ul>';
        $posts = get_posts(['numberposts' => -1, 'post_type' => ['post', 'page', 'product'], 'post_status' => 'publish']);
        foreach ($posts as $post) {
            echo '<li><a href="' . get_permalink($post) . '">' . esc_html(get_the_title($post)) . '</a></li>';
        }
        $terms = get_terms(['taxonomy' => ['category', 'product_cat'], 'hide_empty' => false]);
        foreach ($terms as $term) {
            echo '<li><a href="' . get_term_link($term) . '">' . esc_html($term->name) . '</a></li>';
        }
        echo '</ul></body></html>';
        exit;
    }
});


// ADMIN-ONLY SECTION
// ===================
if (is_admin()) {

    // POSTS & PAGES SEO FIELDS
    add_action('add_meta_boxes', function () {
        add_meta_box('basicseotorvald_v1_seo', 'SEO Settings', 'basicseotorvald_v1_post_meta_box', ['post', 'page'], 'normal', 'default');
    });

    function basicseotorvald_v1_post_meta_box($post) {
        $title = get_post_meta($post->ID, BSTV1_POST_TITLE, true);
        $desc  = get_post_meta($post->ID, BSTV1_POST_DESC, true);
        wp_nonce_field('basicseotorvald_v1_save_post', 'basicseotorvald_v1_nonce');
        echo '<p><label>Title Tag:</label><br />';
        echo '<input type="text" name="basicseotorvald_v1_post_title" value="' . esc_attr($title) . '" style="width:100%" /></p>';
        echo '<p><label>Meta Description:</label><br />';
        echo '<textarea name="basicseotorvald_v1_post_desc" style="width:100%">' . esc_textarea($desc) . '</textarea></p>';
    }

    add_action('save_post', function ($post_id) {
        if (!isset($_POST['basicseotorvald_v1_nonce']) || !wp_verify_nonce($_POST['basicseotorvald_v1_nonce'], 'basicseotorvald_v1_save_post')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (isset($_POST['basicseotorvald_v1_post_title'])) {
            update_post_meta($post_id, BSTV1_POST_TITLE, sanitize_text_field($_POST['basicseotorvald_v1_post_title']));
        }
        if (isset($_POST['basicseotorvald_v1_post_desc'])) {
            update_post_meta($post_id, BSTV1_POST_DESC, sanitize_textarea_field($_POST['basicseotorvald_v1_post_desc']));
        }
    });

    // ADMIN COLUMNS FOR POSTS
    add_filter('manage_post_posts_columns', function ($columns) {
        $columns['seo_title'] = 'SEO Title';
        $columns['seo_desc'] = 'Meta Description';
        return $columns;
    });

    add_action('manage_post_posts_custom_column', function ($column, $post_id) {
        if ($column === 'seo_title') echo esc_html(get_post_meta($post_id, BSTV1_POST_TITLE, true));
        if ($column === 'seo_desc') echo esc_html(get_post_meta($post_id, BSTV1_POST_DESC, true));
    }, 10, 2);

    // WOO COMMERCE CATEGORY FIELDS
    add_action('product_cat_add_form_fields', function () {
        echo '<div class="form-field">
                <label for="basicseotorvald_v1_term_title">Title Tag</label>
                <input type="text" name="basicseotorvald_v1_term_title" value="" />
              </div>
              <div class="form-field">
                <label for="basicseotorvald_v1_term_desc">Meta Description</label>
                <textarea name="basicseotorvald_v1_term_desc"></textarea>
              </div>';
    });

    add_action('product_cat_edit_form_fields', function ($term) {
        $title = get_term_meta($term->term_id, BSTV1_TERM_TITLE, true);
        $desc = get_term_meta($term->term_id, BSTV1_TERM_DESC, true);
        echo '<tr class="form-field">
                <th><label for="basicseotorvald_v1_term_title">Title Tag</label></th>
                <td><input type="text" name="basicseotorvald_v1_term_title" value="' . esc_attr($title) . '" /></td>
              </tr>
              <tr class="form-field">
                <th><label for="basicseotorvald_v1_term_desc">Meta Description</label></th>
                <td><textarea name="basicseotorvald_v1_term_desc">' . esc_textarea($desc) . '</textarea></td>
              </tr>';
    });

    add_action('created_product_cat', 'basicseotorvald_v1_save_term_meta');
    add_action('edited_product_cat', 'basicseotorvald_v1_save_term_meta');

    function basicseotorvald_v1_save_term_meta($term_id) {
        if (isset($_POST['basicseotorvald_v1_term_title'])) {
            update_term_meta($term_id, BSTV1_TERM_TITLE, sanitize_text_field($_POST['basicseotorvald_v1_term_title']));
        }
        if (isset($_POST['basicseotorvald_v1_term_desc'])) {
            update_term_meta($term_id, BSTV1_TERM_DESC, sanitize_textarea_field($_POST['basicseotorvald_v1_term_desc']));
        }
    }
}


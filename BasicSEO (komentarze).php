/**
 * Basic SEO
 * 
 * @package   BasicSEO
 * @author    https://github.com/Torwald45
 * @version   1.8.2
 * @pluginURI https://github.com/Torwald45/basic-seo
 * @changelog https://github.com/Torwald45/basic-seo/blob/main/CHANGELOG.md
 * @roadmap   https://github.com/Torwald45/basic-seo/blob/main/ROADMAP.md
* Features:
 * - Custom Title Tag for pages, posts and WooCommerce (shop page & categories)
 * - Meta Description for pages, posts and WooCommerce (shop page & categories)
 * - SEO columns in admin view for posts, pages, products and WooCommerce categories
 * - Open Graph support (title, description, image)
 * - XML Sitemap with HTML display (pages, posts, products, categories)
 * - Error handling for XML Sitemap
 * - Quick Edit support in admin panel
 * - Breadcrumbs support via [basicseo-breadcrumb] shortcode
 * - Media attachments redirect (prevents duplicate content)
 */

/**
 * ==============================================
 * CONFIGURATION SECTION
 * ==============================================
 */

// Define plugin constants
define('BSTV1_POST_TITLE', 'basicseotorvald_v1_post_title');
define('BSTV1_POST_DESC', 'basicseotorvald_v1_post_desc');
define('BSTV1_TERM_TITLE', 'basicseotorvald_v1_term_title');
define('BSTV1_TERM_DESC', 'basicseotorvald_v1_term_desc');
//error_log('BSTV1_POST_TITLE: ' . BSTV1_POST_TITLE);
//error_log('BSTV1_POST_DESC: ' . BSTV1_POST_DESC);

function basicseotorvald_v1_get_supported_post_types() {
    return array(
        'page',     // WordPress Pages
        'post',     // WordPress Posts
        'product',  // WooCommerce Products
        // Add your custom post types below:
        // 'your_custom_post_type',
    );
}

/**
 * ==============================================
 * POSTS & PAGES SEO FIELDS
 * ==============================================
 */

// Add SEO fields to post/page editor
function basicseotorvald_v1_add_meta_boxes() {
    $post_types = basicseotorvald_v1_get_supported_post_types();
    
    foreach($post_types as $post_type) {
        add_meta_box(
            'basicseotorvald_v1_meta_box',
            'SEO Settings',
            'basicseotorvald_v1_meta_box_callback',
            $post_type,
            'normal',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'basicseotorvald_v1_add_meta_boxes');

// Display SEO fields in editor
function basicseotorvald_v1_meta_box_callback($post) {
    wp_nonce_field('basicseotorvald_v1_meta_box', 'basicseotorvald_v1_meta_box_nonce');
    $custom_title = get_post_meta($post->ID, BSTV1_POST_TITLE, true);
    $meta_desc = get_post_meta($post->ID, BSTV1_POST_DESC, true);
    //error_log('Loading meta box for post ID: ' . $post->ID);
    //error_log('Retrieved title: ' . $custom_title);
    //error_log('Retrieved desc: ' . $meta_desc);
    //error_log('About to display title in input field: "' . esc_attr($custom_title) . '"');
    //error_log('About to display desc in textarea: "' . esc_textarea($meta_desc) . '"');
	
    ?>
    <p>
        <label for="custom_title"><strong>Title Tag:</strong></label><br>
        <input type="text" id="custom_title" name="custom_title" value="<?php echo esc_attr($custom_title); ?>" style="width:100%">
        <small>This will replace the default title in search results and browser tab.</small>
    </p>
    <p>
        <label for="meta_desc"><strong>Meta Description:</strong></label><br>
        <textarea id="meta_desc" name="meta_desc" rows="4" style="width:100%"><?php echo esc_textarea($meta_desc); ?></textarea>
        <small>Description for search engine results.</small>
    </p>
    <?php
}

// Save all meta data
function basicseotorvald_v1_save_meta($post_id) {
    // Prevent autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Verify nonce for regular edit
    $is_regular_edit = isset($_POST['basicseotorvald_v1_meta_box_nonce']) && 
                      wp_verify_nonce($_POST['basicseotorvald_v1_meta_box_nonce'], 'basicseotorvald_v1_meta_box');
    
    // Allow quick edit without nonce
    $is_quick_edit = isset($_POST['_inline_edit']) && wp_verify_nonce($_POST['_inline_edit'], 'inlineeditnonce');

    if (!$is_regular_edit && !$is_quick_edit) {
        return;
    }

    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Delete old values first
    delete_post_meta($post_id, BSTV1_POST_TITLE);
    delete_post_meta($post_id, BSTV1_POST_DESC);

    // Save new values if they exist
    if (isset($_POST['custom_title'])) {
        $custom_title = sanitize_text_field($_POST['custom_title']);
        update_post_meta($post_id, BSTV1_POST_TITLE, $custom_title);
    }
    if (isset($_POST['meta_desc'])) {
        $meta_desc = sanitize_textarea_field($_POST['meta_desc']);
        update_post_meta($post_id, BSTV1_POST_DESC, $meta_desc);
    }
}
add_action('save_post', 'basicseotorvald_v1_save_meta', 10, 1);

/**
 * ==============================================
 * ADMIN COLUMNS AND QUICK EDIT
 * ==============================================
 */

// Add columns to admin lists
function basicseotorvald_v1_add_columns($columns) {
    //error_log('Adding columns function called');
    if (!isset($columns['custom_title'])) {
        $columns['custom_title'] = 'Title Tag';
        $columns['meta_desc'] = 'Meta Description';
    }
    return $columns;
}

add_filter('manage_posts_columns', 'basicseotorvald_v1_add_columns', 20);
add_filter('manage_pages_columns', 'basicseotorvald_v1_add_columns', 20);
add_filter('manage_product_posts_columns', 'basicseotorvald_v1_add_columns', 20); 

foreach(basicseotorvald_v1_get_supported_post_types() as $post_type) {
    add_filter("manage_{$post_type}_posts_columns", 'basicseotorvald_v1_add_columns', 20);
}



// Display content in columns
function basicseotorvald_v1_column_content($column_name, $post_id) {
    static $displayed = array();
    
// Avoid duplicates
    $key = $post_id . '-' . $column_name;
    if (isset($displayed[$key])) {
        return;
    }
    $displayed[$key] = true;

    //error_log('Column display called for post: ' . $post_id . ', column: ' . $column_name);
    
    switch ($column_name) {
        case 'custom_title':
            $title = get_post_meta($post_id, BSTV1_POST_TITLE, true);
            //error_log('Fetched title for display: "' . $title . '"');
            echo esc_html($title);
            break;
        case 'meta_desc':
            $desc = get_post_meta($post_id, BSTV1_POST_DESC, true);
            //error_log('Fetched desc for display: "' . $desc . '"');
            echo esc_html($desc);
            break;
    }
}
add_action('manage_pages_custom_column', 'basicseotorvald_v1_column_content', 10, 2);
add_action('manage_posts_custom_column', 'basicseotorvald_v1_column_content', 10, 2);
foreach(basicseotorvald_v1_get_supported_post_types() as $post_type) {
    add_action("manage_{$post_type}_posts_custom_column", 'basicseotorvald_v1_column_content', 10, 2);
}

// Add quick edit fields
function basicseotorvald_v1_quick_edit_fields($column_name, $post_type) {
    if (!in_array($post_type, basicseotorvald_v1_get_supported_post_types()) || $column_name !== 'custom_title') {
        return;
    }
    ?>
    <fieldset class="inline-edit-col-right">
        <div class="inline-edit-col">
            <label>
                <span class="title">Title Tag</span>
                <span class="input-text-wrap">
                    <input type="text" name="custom_title" class="ptitle" value="">
                </span>
            </label>
        </div>
        <div class="inline-edit-col">
            <label>
                <span class="title">Meta Description</span>
                <span class="input-text-wrap">
                    <textarea name="meta_desc" rows="3"></textarea>
                </span>
            </label>
        </div>
    </fieldset>
    <?php
}
add_action('quick_edit_custom_box', 'basicseotorvald_v1_quick_edit_fields', 10, 2);

// Add JavaScript for quick edit
function basicseotorvald_v1_quick_edit_js() {
    global $current_screen;
    if (!in_array($current_screen->post_type, basicseotorvald_v1_get_supported_post_types())) {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(function($) {
        var $wp_inline_edit = inlineEditPost.edit;
        inlineEditPost.edit = function(id) {
            $wp_inline_edit.apply(this, arguments);
            var post_id = 0;
            if (typeof(id) == 'object') {
                post_id = parseInt(this.getId(id));
            }
            if (post_id > 0) {
                var $row = $('#post-' + post_id);
                var $editRow = $('#edit-' + post_id);
                
                var title = $row.find('td.column-custom_title').text().trim();
                var desc = $row.find('td.column-meta_desc').text().trim();
                
                $editRow.find('input[name="custom_title"]').val(title);
                $editRow.find('textarea[name="meta_desc"]').val(desc);
            }
        };
    });
    </script>
    <?php
}
add_action('admin_footer-edit.php', 'basicseotorvald_v1_quick_edit_js');

/**
 * ==============================================
 * WOOCOMMERCE CATEGORY SEO FIELDS
 * ==============================================
 */

// Add fields to category creation form
function basicseotorvald_v1_add_product_cat_fields() {
    ?>
    <div class="form-field">
        <label for="custom_title">Title Tag</label>
        <input type="text" name="custom_title" id="custom_title">
        <p class="description">Custom title for search results and browser tab.</p>
    </div>
    <div class="form-field">
        <label for="meta_desc">Meta Description</label>
        <textarea name="meta_desc" id="meta_desc"></textarea>
        <p class="description">Description for search engine results.</p>
    </div>
    <?php
}
add_action('product_cat_add_form_fields', 'basicseotorvald_v1_add_product_cat_fields');

// Add fields to category edit form
function basicseotorvald_v1_edit_product_cat_fields($term) {
    $custom_title = get_term_meta($term->term_id, BSTV1_TERM_TITLE, true);
    $meta_desc = get_term_meta($term->term_id, BSTV1_TERM_DESC, true);
    ?>
    <tr class="form-field">
        <th scope="row">
            <label for="custom_title">Title Tag</label>
        </th>
        <td>
            <input type="text" name="custom_title" id="custom_title" value="<?php echo esc_attr($custom_title); ?>">
            <p class="description">Custom title for search results and browser tab.</p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row">
            <label for="meta_desc">Meta Description</label>
        </th>
        <td>
            <textarea name="meta_desc" id="meta_desc"><?php echo esc_textarea($meta_desc); ?></textarea>
            <p class="description">Description for search engine results.</p>
        </td>
    </tr>
    <?php
}
add_action('product_cat_edit_form_fields', 'basicseotorvald_v1_edit_product_cat_fields');

// Save category meta fields
function basicseotorvald_v1_save_product_cat_fields($term_id) {
    if (isset($_POST['custom_title'])) {
        update_term_meta($term_id, BSTV1_TERM_TITLE, sanitize_text_field($_POST['custom_title']));
    }
    if (isset($_POST['meta_desc'])) {
        update_term_meta($term_id, BSTV1_TERM_DESC, sanitize_textarea_field($_POST['meta_desc']));
    }
}
add_action('created_product_cat', 'basicseotorvald_v1_save_product_cat_fields');
add_action('edited_product_cat', 'basicseotorvald_v1_save_product_cat_fields');

/**
 * ==============================================
 * WOOCOMMERCE CATEGORY COLUMNS
 * ==============================================
 */

// Add new columns to the product category list
function basicseotorvald_v1_add_product_cat_columns($columns) {
    $new_columns = array();
    
    // Preserve checkbox column if exists
    if (isset($columns['cb'])) {
        $new_columns['cb'] = $columns['cb'];
    }
    
    // Preserve thumbnail column if exists
    if (isset($columns['thumb'])) {
        $new_columns['thumb'] = $columns['thumb'];
    }
    
    // Add name column (required)
    $new_columns['name'] = $columns['name'];
    
    // Add our new SEO columns
    $new_columns['seo_title'] = 'Title Tag';
    $new_columns['seo_desc'] = 'Meta Description';
    
    // Preserve remaining standard columns
    if (isset($columns['description'])) {
        $new_columns['description'] = $columns['description'];
    }
    if (isset($columns['slug'])) {
        $new_columns['slug'] = $columns['slug'];
    }
    if (isset($columns['count'])) {
        $new_columns['count'] = $columns['count'];
    }
    
    return $new_columns;
}
add_filter('manage_edit-product_cat_columns', 'basicseotorvald_v1_add_product_cat_columns');

// Display content in the new columns
function basicseotorvald_v1_product_cat_column_content($content, $column_name, $term_id) {
    switch ($column_name) {
        case 'seo_title':
            $title = get_term_meta($term_id, BSTV1_TERM_TITLE, true);
            return $title ? esc_html($title) : '—';
            
        case 'seo_desc':
            $desc = get_term_meta($term_id, BSTV1_TERM_DESC, true);
            return $desc ? esc_html(wp_trim_words($desc, 10, '...')) : '—';
    }
    return $content;
}
add_filter('manage_product_cat_custom_column', 'basicseotorvald_v1_product_cat_column_content', 10, 3);

/**
 * END OF WOOCOMMERCE CATEGORY COLUMNS
 * ==============================================
 */

/**
 * ==============================================
 * META TAGS OUTPUT IN HEAD
 * ==============================================
 */

// Change default title tag
function basicseotorvald_v1_document_title($title) {
    // For WooCommerce shop page
    if (function_exists('is_shop') && is_shop()) {
        $shop_page_id = wc_get_page_id('shop');
        $custom_title = get_post_meta($shop_page_id, BSTV1_POST_TITLE, true);
        if (!empty($custom_title)) {
            return $custom_title;
        }
    }
    // For posts and pages
    elseif (is_singular(basicseotorvald_v1_get_supported_post_types())) {
        $post_id = get_queried_object_id();
        $custom_title = get_post_meta($post_id, BSTV1_POST_TITLE, true);
        if (!empty($custom_title)) {
            return $custom_title;
        }
    }
    // For WooCommerce categories
    elseif (is_tax('product_cat')) {
        $term_id = get_queried_object_id();
        $custom_title = get_term_meta($term_id, BSTV1_TERM_TITLE, true);
        if (!empty($custom_title)) {
            return $custom_title;
        }
    }
    return $title;
}
add_filter('pre_get_document_title', 'basicseotorvald_v1_document_title', 10);

// Add meta tags and Open Graph
function basicseotorvald_v1_add_meta_tags() {
    // For WooCommerce shop page
    if (function_exists('is_shop') && is_shop()) {
        $shop_page_id = wc_get_page_id('shop');
        $custom_title = get_post_meta($shop_page_id, BSTV1_POST_TITLE, true);
        $meta_desc = get_post_meta($shop_page_id, BSTV1_POST_DESC, true);

        if (!empty($custom_title)) {
            echo '<meta name="title" content="' . esc_attr($custom_title) . '">' . "\n";
        }
        if (!empty($meta_desc)) {
            echo '<meta name="description" content="' . esc_attr($meta_desc) . '">' . "\n";
        }

        // OG TAGS #1: WOOCOMMERCE SHOP PAGE OPEN GRAPH TAGS
        echo '<meta property="og:title" content="' . esc_attr(!empty($custom_title) ? $custom_title : get_the_title($shop_page_id)) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr(!empty($meta_desc) ? $meta_desc : wp_trim_words(get_the_content(null, false, $shop_page_id), 55, '...')) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_permalink($shop_page_id)) . '">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";

        if (has_post_thumbnail($shop_page_id)) {
            $img_src = wp_get_attachment_image_src(get_post_thumbnail_id($shop_page_id), 'large');
            if ($img_src) {
                echo '<meta property="og:image" content="' . esc_url($img_src[0]) . '">' . "\n";
            }
        }
    }
    // For posts and pages
    elseif (is_singular(basicseotorvald_v1_get_supported_post_types())) {
        $post_id = get_queried_object_id();
        $custom_title = get_post_meta($post_id, BSTV1_POST_TITLE, true);
        $meta_desc = get_post_meta($post_id, BSTV1_POST_DESC, true);
        
        if (!empty($custom_title)) {
            echo '<meta name="title" content="' . esc_attr($custom_title) . '">' . "\n";
        }
        if (!empty($meta_desc)) {
            echo '<meta name="description" content="' . esc_attr($meta_desc) . '">' . "\n";
        }
        
        // OG TAGS #2: POSTS AND PAGES OPEN GRAPH TAGS
        echo '<meta property="og:title" content="' . esc_attr(!empty($custom_title) ? $custom_title : get_the_title($post_id)) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr(!empty($meta_desc) ? $meta_desc : wp_trim_words(get_the_content(null, false, $post_id), 55, '...')) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_permalink($post_id)) . '">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";
        
        if (has_post_thumbnail($post_id)) {
            $img_src = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), 'large');
            if ($img_src) {
                echo '<meta property="og:image" content="' . esc_url($img_src[0]) . '">' . "\n";
            }
        }
    }
    // For WooCommerce categories
    elseif (is_tax('product_cat')) {
        $term_id = get_queried_object_id();
        $custom_title = get_term_meta($term_id, BSTV1_TERM_TITLE, true);
        $meta_desc = get_term_meta($term_id, BSTV1_TERM_DESC, true);
        $term = get_term($term_id, 'product_cat');
        
        if (!empty($custom_title)) {
            echo '<meta name="title" content="' . esc_attr($custom_title) . '">' . "\n";
        }
        if (!empty($meta_desc)) {
            echo '<meta name="description" content="' . esc_attr($meta_desc) . '">' . "\n";
        }
        
        // OG TAGS #3: WOOCOMMERCE CATEGORIES OPEN GRAPH TAGS
        echo '<meta property="og:title" content="' . esc_attr(!empty($custom_title) ? $custom_title : $term->name) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr(!empty($meta_desc) ? $meta_desc : strip_tags($term->description)) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_term_link($term_id, 'product_cat')) . '">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";
        
        $thumbnail_id = get_term_meta($term_id, 'thumbnail_id', true);
        if ($thumbnail_id) {
            $img_src = wp_get_attachment_image_src($thumbnail_id, 'large');
            if ($img_src) {
                echo '<meta property="og:image" content="' . esc_url($img_src[0]) . '">' . "\n";
            }
        }
    }
}
add_action('wp_head', 'basicseotorvald_v1_add_meta_tags', 1);

/**
 * ==============================================
 * SITEMAP SECTION
 * ==============================================
 */

// Generate main sitemap index
function basicseotorvald_v1_generate_sitemap_index() {
    $output = '<h1>XML Sitemap</h1>';
    
    // Get all public post types
    $post_types = get_post_types(['public' => true]);
    unset($post_types['attachment']);
    
    if (empty($post_types)) {
        $output .= '<p>No public post types found.</p>';
    } else {
        foreach ($post_types as $post_type) {
            // Check if post type has any posts
            $count = wp_count_posts($post_type);
            if ($count && $count->publish > 0) {
                $url = home_url("/sitemap-post-type-{$post_type}.xml");
                $output .= "<a href='" . esc_url($url) . "'>" . esc_html($url) . "</a><br>\n";
            }
        }
    }
    
    // Get all public taxonomies
    $taxonomies = get_taxonomies(['public' => true]);
    
    if (empty($taxonomies)) {
        $output .= '<p>No public taxonomies found.</p>';
    } else {
        foreach ($taxonomies as $taxonomy) {
            // Check if taxonomy has any terms
            $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true, 'number' => 1]);
            if (!empty($terms) && !is_wp_error($terms)) {
                $url = home_url("/sitemap-taxonomy-{$taxonomy}.xml");
                $output .= "<a href='" . esc_url($url) . "'>" . esc_html($url) . "</a><br>\n";
            }
        }
    }
    
    return $output;
}

// Generate sitemap for specific post type
function basicseotorvald_v1_generate_post_type_sitemap($post_type) {
    $output = '<h1>XML Sitemap</h1>';
    
    // Verify post type exists
    if (!post_type_exists($post_type)) {
        return '<div class="error">Error: Post type does not exist.</div>';
    }
    
    $output .= '<table>
        <tr>
            <th>URL</th>
            <th>Last Modified</th>
        </tr>';
    
    // no limit posts to 500 to prevent server overload
$posts = get_posts([
    'post_type' => $post_type,
    'post_status_header(404);
            echo '<h1>Error 404: Sitemap not found</h1>';
            echo '<p>The requested post type does not exist.</p>';
            echo '<p><a href="' . home_url('/sitemap.xml') . '">Back to main sitemap</a></p>';
            exit;
        }
    }
    
    // Taxonomy sitemaps
    if (preg_match('/sitemap-taxonomy-([^.]+)\.xml$/', $current_url, $matches)) {
        $taxonomy = $matches[1];
        if (taxonomy_exists($taxonomy)) {
            echo basicseotorvald_v1_generate_taxonomy_sitemap($taxonomy);
            exit;
        } else {
            status_header(404);
            echo '<h1>Error 404: Sitemap not found</h1>';
            echo '<p>The requested taxonomy does not exist.</p>';
            echo '<p><a href="' . home_url('/sitemap.xml') . '">Back to main sitemap</a></p>';
            exit;
        }
    }
    
    // If we got here, the sitemap URL pattern was invalid
    if (strpos($current_url, 'sitemap') !== false && strpos($current_url, '.xml') !== false) {
        status_header(404);
        echo '<h1>Error 404: Sitemap not found</h1>';
        echo '<p>Invalid sitemap URL format.</p>';
        echo '<p><a href="' . home_url('/sitemap.xml') . '">Back to main sitemap</a></p>';
        exit;
    }
}

add_action('init', 'basicseotorvald_v1_handle_sitemap_request');

/**
 * ==============================================
 * BREADCRUMBS SECTION
 * ==============================================
 */

function basicseotorvald_v1_generate_breadcrumbs() {
    // Don't show on home page
    if (is_front_page()) {
        return '';
    }

    $output = '<div class="breadcrumbs">';
    $output .= '<a href="' . home_url() . '">Home</a>';

    if (is_page()) {
        $ancestors = get_post_ancestors(get_the_ID());
        $ancestors = array_reverse($ancestors);

        foreach ($ancestors as $ancestor) {
            $output .= ' &raquo; <a href="' . get_permalink($ancestor) . '">' . get_the_title($ancestor) . '</a>';
        }

        $output .= ' &raquo; ' . get_the_title();
    }

    $output .= '</div>';
    return $output;
}

// Register breadcrumbs shortcode [basicseo-breadcrumb]
function basicseotorvald_v1_breadcrumbs_shortcode($atts) {
    return basicseotorvald_v1_generate_breadcrumbs();
}
add_shortcode('basicseo-breadcrumb', 'basicseotorvald_v1_breadcrumbs_shortcode');

// Add basic CSS for breadcrumbs
function basicseotorvald_v1_add_breadcrumbs_css() {
    echo '<style>
    .breadcrumbs {
        margin: 10px 0;
        font-size: 14px;
    }
    .breadcrumbs a {
        color: #007bff;
        text-decoration: none;
    }
    .breadcrumbs a:hover {
        text-decoration: underline;
    }
    </style>';
}
add_action('wp_head', 'basicseotorvald_v1_add_breadcrumbs_css');

/**
 * ==============================================
 * ATTACHMENTS REDIRECT SECTION
 * ==============================================
 */

/**
 * Redirect attachment pages to parent post or home
 * This prevents duplicate content issues with attachment pages
 */
function basicseotorvald_v1_redirect_attachment_pages() {
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
}
add_action('template_redirect', 'basicseotorvald_v1_redirect_attachment_pages');' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'modified',
    'order' => 'DESC'
]);

    if (empty($posts)) {
        $output .= '<tr><td colspan="2">No published content found.</td></tr>';
    } else {
        foreach ($posts as $post) {
            $permalink = get_permalink($post->ID);
            if ($permalink) {
                $output .= '<tr>
                    <td><a href="' . esc_url($permalink) . '">' . esc_html($permalink) . '</a></td>
                    <td>' . get_the_modified_date('Y-m-d\TH:i:sP', $post->ID) . '</td>
                </tr>';
            }
        }
    }
    
    $output .= '</table>';
    
    // Add return link
    $output .= '<p><a href="' . home_url('/sitemap.xml') . '">&laquo; Back to main sitemap</a></p>';
    
    return $output;
}

// Generate sitemap for taxonomy
function basicseotorvald_v1_generate_taxonomy_sitemap($taxonomy) {
    $output = '<h1>XML Sitemap</h1>';
    
    // Verify taxonomy exists
    if (!taxonomy_exists($taxonomy)) {
        return '<div class="error">Error: Taxonomy does not exist.</div>';
    }
    
    $output .= '<table>
        <tr>
            <th>URL</th>
            <th>Last Modified</th>
        </tr>';
    
$terms = get_terms([
    'taxonomy' => $taxonomy,
    'hide_empty' => true
]);
    
    if (empty($terms) || is_wp_error($terms)) {
        $output .= '<tr><td colspan="2">No terms found or an error occurred.</td></tr>';
    } else {
        foreach ($terms as $term) {
            $term_link = get_term_link($term);
            if (!is_wp_error($term_link)) {
                $output .= '<tr>
                    <td><a href="' . esc_url($term_link) . '">' . esc_html($term_link) . '</a></td>
                    <td>' . date('Y-m-d\TH:i:sP') . '</td>
                </tr>';
            }
        }
    }
    
    $output .= '</table>';
    
    // Add return link
    $output .= '<p><a href="' . home_url('/sitemap.xml') . '">&laquo; Back to main sitemap</a></p>';
    
    return $output;
}

// Handle sitemap requests
function basicseotorvald_v1_handle_sitemap_request() {
    $current_url = $_SERVER['REQUEST_URI'];
    
    // Check if URL is related to sitemap
    if (strpos($current_url, 'sitemap') === false) {
        return;
    }
    
    // Remove trailing slash only for sitemap URLs
    if (substr($current_url, -1) === '/' && strpos($current_url, 'sitemap') !== false) {
        wp_redirect(rtrim($current_url, '/'), 301);
        exit;
    }
    
    // Main sitemap
    if ($current_url === '/sitemap.xml') {
        echo basicseotorvald_v1_generate_sitemap_index();
        exit;
    }
    
    // Post type sitemaps
    if (preg_match('/sitemap-post-type-([^.]+)\.xml$/', $current_url, $matches)) {
        $post_type = $matches[1];
        if (post_type_exists($post_type)) {
            echo basicseotorvald_v1_generate_post_type_sitemap($post_type);
            exit;
        } else {
            status

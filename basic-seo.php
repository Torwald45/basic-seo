/**
 * Basic SEO
 * 
 * @package   BasicSEO
 * @author    https://github.com/Torwald45
 * @version   1.7
 * @license   GPL-2.0+
 * @link      https://github.com/Torwald45/basic-seo
 * 
 * Features:
 * - Custom Title Tag for pages, posts and WooCommerce (shop page & categories)
 * - Meta Description for pages, posts and WooCommerce (shop page & categories)
 * - Open Graph support (title, description, image)
 * - XML Sitemap with HTML display (pages, posts, products, categories)
 * - Quick Edit support in admin panel
 * - Breadcrumbs support via [seo-breadcrumbs] shortcode
 * - Media attachments redirect (prevents duplicate content)
 */

/**
 * ==============================================
 * CONFIGURATION SECTION
 * ==============================================
 */

function get_supported_post_types() {
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
function add_custom_meta_boxes() {
    $post_types = get_supported_post_types();
    
    foreach($post_types as $post_type) {
        add_meta_box(
            'custom_meta_box',
            'SEO Settings',
            'custom_meta_box_callback',
            $post_type,
            'normal',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'add_custom_meta_boxes');

// Display SEO fields in editor
function custom_meta_box_callback($post) {
    wp_nonce_field('custom_meta_box', 'custom_meta_box_nonce');
    $custom_title = get_post_meta($post->ID, '_custom_title', true);
    $meta_desc = get_post_meta($post->ID, '_meta_desc', true);
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
function save_custom_meta($post_id) {
    // Prevent autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Delete old values first
    delete_post_meta($post_id, '_custom_title');
    delete_post_meta($post_id, '_meta_desc');

    // Save new values if they exist
    if (!empty($_POST['custom_title'])) {
        update_post_meta($post_id, '_custom_title', sanitize_text_field($_POST['custom_title']));
    }
    if (!empty($_POST['meta_desc'])) {
        update_post_meta($post_id, '_meta_desc', sanitize_textarea_field($_POST['meta_desc']));
    }
}
add_action('save_post', 'save_custom_meta', 10, 1);

/**
 * ==============================================
 * ADMIN COLUMNS AND QUICK EDIT
 * ==============================================
 */

// Add columns to admin lists
function add_custom_columns($columns) {
    $columns['custom_title'] = 'Title Tag';
    $columns['meta_desc'] = 'Meta Description';
    return $columns;
}
add_filter('manage_pages_columns', 'add_custom_columns');
add_filter('manage_posts_columns', 'add_custom_columns');
foreach(get_supported_post_types() as $post_type) {
    add_filter("manage_{$post_type}_posts_columns", 'add_custom_columns');
}

// Display content in columns
function custom_column_content($column_name, $post_id) {
    switch ($column_name) {
        case 'custom_title':
            echo esc_html(get_post_meta($post_id, '_custom_title', true));
            break;
        case 'meta_desc':
            echo esc_html(get_post_meta($post_id, '_meta_desc', true));
            break;
    }
}
add_action('manage_pages_custom_column', 'custom_column_content', 10, 2);
add_action('manage_posts_custom_column', 'custom_column_content', 10, 2);
foreach(get_supported_post_types() as $post_type) {
    add_action("manage_{$post_type}_posts_custom_column", 'custom_column_content', 10, 2);
}

// Add quick edit fields
function add_quick_edit_custom_fields($column_name, $post_type) {
    if (!in_array($post_type, get_supported_post_types()) || $column_name !== 'custom_title') {
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
add_action('quick_edit_custom_box', 'add_quick_edit_custom_fields', 10, 2);

// Add JavaScript for quick edit
function quick_edit_custom_fields_js() {
    global $current_screen;
    if (!in_array($current_screen->post_type, get_supported_post_types())) {
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
                // Get the original row and the edit row
                var $row = $('#post-' + post_id);
                var $editRow = $('#edit-' + post_id);
                
                // Get the data
                var title = $row.find('.column-custom_title').text().replace(/\s+/g, ' ').trim();
                var desc = $row.find('.column-meta_desc').text().replace(/\s+/g, ' ').trim();
                
                // Set the values
                $editRow.find('input[name="custom_title"]').val(title);
                $editRow.find('textarea[name="meta_desc"]').val(desc);
            }
        };
    });
    </script>
    <?php
}
add_action('admin_footer-edit.php', 'quick_edit_custom_fields_js');

/**
* ==============================================
* WOOCOMMERCE CATEGORY SEO FIELDS
* ==============================================
*/

// Add fields to category creation form
function add_product_cat_meta_fields() {
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
add_action('product_cat_add_form_fields', 'add_product_cat_meta_fields');

// Add fields to category edit form
function edit_product_cat_meta_fields($term) {
   $custom_title = get_term_meta($term->term_id, 'custom_title', true);
   $meta_desc = get_term_meta($term->term_id, 'meta_desc', true);
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
add_action('product_cat_edit_form_fields', 'edit_product_cat_meta_fields');

// Save category meta fields
function save_product_cat_meta_fields($term_id) {
   if (isset($_POST['custom_title'])) {
       update_term_meta($term_id, 'custom_title', sanitize_text_field($_POST['custom_title']));
   }
   if (isset($_POST['meta_desc'])) {
       update_term_meta($term_id, 'meta_desc', sanitize_textarea_field($_POST['meta_desc']));
   }
}
add_action('created_product_cat', 'save_product_cat_meta_fields');
add_action('edited_product_cat', 'save_product_cat_meta_fields');

/**
 * ==============================================
 * META TAGS OUTPUT IN HEAD
 * ==============================================
 */

// Change default title tag
function custom_document_title($title) {
    // For WooCommerce shop page
    if (function_exists('is_shop') && is_shop()) {
        $shop_page_id = wc_get_page_id('shop');
        $custom_title = get_post_meta($shop_page_id, '_custom_title', true);
        if (!empty($custom_title)) {
            return $custom_title;
        }
    }
    // For posts and pages
    elseif (is_singular(get_supported_post_types())) {
        $post_id = get_queried_object_id();
        $custom_title = get_post_meta($post_id, '_custom_title', true);
        if (!empty($custom_title)) {
            return $custom_title;
        }
    }
    // For WooCommerce categories
    elseif (is_tax('product_cat')) {
        $term_id = get_queried_object_id();
        $custom_title = get_term_meta($term_id, 'custom_title', true);
        if (!empty($custom_title)) {
            return $custom_title;
        }
    }
    return $title;
}
add_filter('pre_get_document_title', 'custom_document_title', 10);

// Add meta tags and Open Graph
function add_custom_meta_tags() {
    // For WooCommerce shop page
    if (function_exists('is_shop') && is_shop()) {
        $shop_page_id = wc_get_page_id('shop');
        $custom_title = get_post_meta($shop_page_id, '_custom_title', true);
        $meta_desc = get_post_meta($shop_page_id, '_meta_desc', true);

        if (!empty($custom_title)) {
            echo '<meta name="title" content="' . esc_attr($custom_title) . '">' . "\n";
        }
        if (!empty($meta_desc)) {
            echo '<meta name="description" content="' . esc_attr($meta_desc) . '">' . "\n";
        }

        // Open Graph tags
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
    elseif (is_singular(get_supported_post_types())) {
        $post_id = get_queried_object_id();
        $custom_title = get_post_meta($post_id, '_custom_title', true);
        $meta_desc = get_post_meta($post_id, '_meta_desc', true);
        
        if (!empty($custom_title)) {
            echo '<meta name="title" content="' . esc_attr($custom_title) . '">' . "\n";
        }
        if (!empty($meta_desc)) {
            echo '<meta name="description" content="' . esc_attr($meta_desc) . '">' . "\n";
        }
        
        // Open Graph tags
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
        $custom_title = get_term_meta($term_id, 'custom_title', true);
        $meta_desc = get_term_meta($term_id, 'meta_desc', true);
        $term = get_term($term_id, 'product_cat');
        
        if (!empty($custom_title)) {
            echo '<meta name="title" content="' . esc_attr($custom_title) . '">' . "\n";
        }
        if (!empty($meta_desc)) {
            echo '<meta name="description" content="' . esc_attr($meta_desc) . '">' . "\n";
        }
        
        // Open Graph tags
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
add_action('wp_head', 'add_custom_meta_tags', 1);

/**
* ==============================================
* SITEMAP SECTION
* ==============================================
*/

// Generate main sitemap index
function generate_sitemap_index_html() {
   $output = '<h1>XML Sitemap</h1>';
   
   // Get all public post types
   $post_types = get_post_types(['public' => true]);
   unset($post_types['attachment']);
   
   foreach ($post_types as $post_type) {
       // Check if post type has any posts
       $count = wp_count_posts($post_type);
       if ($count->publish > 0) {
           $url = home_url("/sitemap-post-type-{$post_type}.xml");
           $output .= "<a href='{$url}'>{$url}</a><br>\n";
       }
   }
   
   // Get all public taxonomies
   $taxonomies = get_taxonomies(['public' => true]);
   foreach ($taxonomies as $taxonomy) {
       // Check if taxonomy has any terms
       $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);
       if (!empty($terms) && !is_wp_error($terms)) {
           $url = home_url("/sitemap-taxonomy-{$taxonomy}.xml");
           $output .= "<a href='{$url}'>{$url}</a><br>\n";
       }
   }
   
   return $output;
}

// Generate sitemap for specific post type
function generate_post_type_sitemap_html($post_type) {
   $output = '<h1>XML Sitemap</h1>';
   $output .= '<table>
       <tr>
           <th>URL</th>
           <th>Last Modified</th>
       </tr>';
   
   $posts = get_posts([
       'post_type' => $post_type,
       'post_status' => 'publish',
       'posts_per_page' => -1,
       'orderby' => 'modified',
       'order' => 'DESC'
   ]);
   
   foreach ($posts as $post) {
       $output .= '<tr>
           <td><a href="' . get_permalink($post->ID) . '">' . get_permalink($post->ID) . '</a></td>
           <td>' . get_the_modified_date('Y-m-d\TH:i:sP', $post->ID) . '</td>
       </tr>';
   }
   
   $output .= '</table>';
   
   // Add return link
   $output .= '<p><a href="' . home_url('/sitemap.xml') . '">&laquo; Back to main sitemap</a></p>';
   
   return $output;
}

// Generate sitemap for taxonomy
function generate_taxonomy_sitemap_html($taxonomy) {
   $output = '<h1>XML Sitemap</h1>';
   $output .= '<table>
       <tr>
           <th>URL</th>
           <th>Last Modified</th>
       </tr>';
   
   $terms = get_terms([
       'taxonomy' => $taxonomy,
       'hide_empty' => true
   ]);
   
   foreach ($terms as $term) {
       $output .= '<tr>
           <td><a href="' . get_term_link($term) . '">' . get_term_link($term) . '</a></td>
           <td>' . date('Y-m-d\TH:i:sP') . '</td>
       </tr>';
   }
   
   $output .= '</table>';
   
   // Add return link
   $output .= '<p><a href="' . home_url('/sitemap.xml') . '">&laquo; Back to main sitemap</a></p>';
   
   return $output;
}

// Handle sitemap requests
function handle_sitemap_request() {
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
       echo generate_sitemap_index_html();
       exit;
   }
   
   // Post type sitemaps
   if (preg_match('/sitemap-post-type-([^.]+)\.xml$/', $current_url, $matches)) {
       $post_type = $matches[1];
       if (post_type_exists($post_type)) {
           echo generate_post_type_sitemap_html($post_type);
           exit;
       }
   }
   
   // Taxonomy sitemaps
   if (preg_match('/sitemap-taxonomy-([^.]+)\.xml$/', $current_url, $matches)) {
       $taxonomy = $matches[1];
       if (taxonomy_exists($taxonomy)) {
           echo generate_taxonomy_sitemap_html($taxonomy);
           exit;
       }
   }
}
add_action('init', 'handle_sitemap_request');

/**
* ==============================================
* BREADCRUMBS SECTION
* ==============================================
*/

/**
 * ==============================================
 * BREADCRUMBS SECTION
 * ==============================================
 */

function generate_breadcrumbs() {
    // Don't show on home page
    if (is_front_page()) {
        return '';
    }

    $output = '<div class="breadcrumbs">';
    $output .= '<a href="' . home_url() . '">Strona główna</a>';

    if (is_page()) {
        $ancestors = get_post_ancestors(get_the_ID());
        $ancestors = array_reverse($ancestors);

        foreach ($ancestors as $ancestor) {
            $output .= ' &raquo; <a href="' . get_permalink($ancestor) . '">' . get_the_title($ancestor) . '</a>';
        }

        $output .= ' &raquo; ' . get_the_title();
    }

    $output .= '</div>';
    return $output; // Zmienione z return na echo
}

// Register breadcrumbs shortcode [seo-breadcrumbs]
function breadcrumbs_shortcode($atts) {
    return generate_breadcrumbs();
}
add_shortcode('seo-breadcrumbs', 'breadcrumbs_shortcode');

// Add basic CSS for breadcrumbs
function add_breadcrumbs_css() {
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
add_action('wp_head', 'add_breadcrumbs_css');

/**
* ==============================================
* ATTACHMENTS REDIRECT SECTION
* ==============================================
*/

/**
* Redirect attachment pages to parent post or home
* This prevents duplicate content issues with attachment pages
*/
function redirect_attachment_pages() {
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
add_action('template_redirect', 'redirect_attachment_pages');

<?php
/**
 * Intentionally Blank Theme functions
 *
 * @package WordPress
 * @subpackage intentionally-blank
 */

if ( ! function_exists( 'blank_setup' ) ) :
    /**
     * Sets up theme defaults and registers the various WordPress features that
     * this theme supports.
     */
    function blank_setup() {
        load_theme_textdomain( 'intentionally-blank' );
        add_theme_support( 'automatic-feed-links' );
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );

        // This theme allows users to set a custom background.
        add_theme_support(
            'custom-background',
            array(
                'default-color' => 'f5f5f5',
            )
        );

        add_theme_support( 'custom-logo' );
        add_theme_support(
            'custom-logo',
            array(
                'height'      => 256,
                'width'       => 256,
                'flex-height' => true,
                'flex-width'  => true,
                'header-text' => array( 'site-title', 'site-description' ),
            )
        );
    }
endif; // end function_exists blank_setup.

add_action( 'after_setup_theme', 'blank_setup' );

remove_action( 'wp_head', '_custom_logo_header_styles' );

if ( ! is_admin() ) {
    add_action(
        'wp_enqueue_scripts',
        function() {
            wp_dequeue_style( 'global-styles' );
            wp_dequeue_style( 'classic-theme-styles' );
            wp_dequeue_style( 'wp-block-library' );
        }
    );
}

/**
 * Sets up theme defaults and registers the various WordPress features that
 * this theme supports.

 * @param class $wp_customize Customizer object.
 */
function blank_customize_register( $wp_customize ) {
    $wp_customize->remove_section( 'static_front_page' );

    $wp_customize->add_section(
        'blank_footer',
        array(
            'title'      => __( 'Footer', 'intentionally-blank' ),
            'priority'   => 120,
            'capability' => 'edit_theme_options',
            'panel'      => '',
        )
    );
    $wp_customize->add_setting(
        'blank_copyright',
        array(
            'type'              => 'theme_mod',
            'default'           => __( 'Intentionally Blank - Proudly powered by WordPress', 'intentionally-blank' ),
            'sanitize_callback' => 'wp_kses_post',
        )
    );

    /**
     * Checkbox sanitization function

     * @param bool $checked Whether the checkbox is checked.
     * @return bool Whether the checkbox is checked.
     */

	


    function blank_sanitize_checkbox( $checked ) {
        // Returns true if checkbox is checked.
        return ( ( isset( $checked ) && true === $checked ) ? true : false );
    }
    $wp_customize->add_setting(
        'blank_show_copyright',
        array(
            'default'           => true,
            'sanitize_callback' => 'blank_sanitize_checkbox',
        )
    );
    $wp_customize->add_control(
        'blank_copyright',
        array(
            'type'     => 'textarea',
            'label'    => __( 'Copyright Text', 'intentionally-blank' ),
            'section'  => 'blank_footer',
            'settings' => 'blank_copyright',
            'priority' => '10',
        )
    );
    $wp_customize->add_control(
        'blank_footer_copyright_hide',
        array(
            'type'     => 'checkbox',
            'label'    => __( 'Show footer with copyright Text', 'intentionally-blank' ),
            'section'  => 'blank_footer',
            'settings' => 'blank_show_copyright',
            'priority' => '20',
        )
    );
}

add_theme_support( 'post-thumbnails' );
add_action( 'customize_register', 'blank_customize_register', 100 );

function press_release_widgets_init() {
    register_sidebar( array(
        'name'          => __( 'Press Release Sidebar', 'intentionally-blank' ),
        'id'            => 'press-release-sidebar',
        'description'   => __( 'Widgets in this area will be shown on press release posts.', 'intentionally-blank' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );
}
add_action( 'widgets_init', 'press_release_widgets_init' );

function get_cision_token() {
    $url = 'https://contentapi.cision.com/api/v1.0/auth/login';
	$username = CISION_API_USERNAME;
    $password = CISION_API_PASSWORD;

    $response = wp_remote_post($url, array(
        'method' => 'POST',
        'body' => json_encode(array(
            'login' => $username,
            'pwd' => $password
        )),
        'headers' => array(
            'Content-Type' => 'application/json',
            'accept' => 'application/json',
            'X-Client' => $username
        )
    ));

    if (is_wp_error($response)) {
        error_log('Error fetching Cision token: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    error_log('Cision API full response: ' . print_r($body, true));
    error_log('Cision API decoded response: ' . print_r($data, true));

    if (!isset($data['auth_token'])) {
        error_log('Cision API token missing in response');
        return false;
    }

    return $data['auth_token'];
}

// Fetch detailed release data
function fetch_detailed_release_data($release_id) {
    $token = get_cision_token();
    if (!$token) {
        error_log('Failed to get Cision token for detailed release data');
        return false;
    }

    $api_url = 'https://contentapi.cision.com/api/v1.0/releases/' . $release_id;

    $response = wp_remote_get($api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'X-Client' => 'FKQapi',
            'accept' => 'application/json'
        )
    ));

    if (is_wp_error($response)) {
        error_log('Error fetching detailed release data: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decode error: ' . json_last_error_msg());
        return false;
    }

    if (!isset($data['data'])) {
        error_log('Cision API response missing data: ' . print_r($data, true));
        return false;
    }

    error_log('Fetched detailed release data: ' . print_r($data['data'], true));

    return $data['data'];
}

function fetch_pr_newswire_data() {
    $token = get_cision_token();
    if (!$token) {
        error_log('Failed to get Cision token');
        return false;
    }

    // Calculate start and end dates
    $start_date = (new DateTime())->modify('-6 months')->format('Ymd\THisP'); // Six months ago
    $end_date = (new DateTime())->format('Ymd\THisP'); // Now

    // Log the raw dates
    error_log('Raw start date: ' . $start_date);
    error_log('Raw end date: ' . $end_date);

    // Convert timezone format
    $start_date = preg_replace('/(\d{2}):(\d{2})$/', '$1$2', $start_date);
    $end_date = preg_replace('/(\d{2}):(\d{2})$/', '$1$2', $end_date);

    // Log the formatted dates
    error_log('Formatted start date: ' . $start_date);
    error_log('Formatted end date: ' . $end_date);

    // Filters for industry and keyword
    $industry = 'AUT|TRN|PAV|TRA';
    $keyword = 'Hertz';

    $api_url = 'https://contentapi.cision.com/api/v1.0/releases?show_del=true&startdate=' . urlencode($start_date) . '&enddate=' . urlencode($end_date) . '&industry=' . urlencode($industry) . '&keyword=' . urlencode($keyword);

    error_log('API URL: ' . $api_url);

    $response = wp_remote_get($api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'X-Client' => 'FKQapi',
            'accept' => 'application/json'
        )
    ));

    if (is_wp_error($response)) {
        error_log('Error fetching PR Newswire data: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    error_log('Fetched PR Newswire data: ' . print_r($data, true));

    if (isset($data['data'])) {
        return $data['data'];
    }

    error_log('No data found in PR Newswire response');
    return false;
}

// Create post from PR Newswire release
function create_post_from_pr_newswire($release) {
    // Log release data for debugging
    error_log('Creating post from release: ' . print_r($release, true));

    // Fetch detailed release data
    $detailed_release = fetch_detailed_release_data($release['release_id']);
    if (!$detailed_release) {
        error_log('Failed to fetch detailed release data for release ID: ' . $release['release_id']);
        return false;
    }

    // Log detailed release data
    error_log('Detailed release data: ' . print_r($detailed_release, true));

    // Check if post with the same title already exists
    $existing_post = get_page_by_title(sanitize_text_field($detailed_release['title']), OBJECT, 'post');
    if ($existing_post) {
        error_log('Post already exists with title: ' . $detailed_release['title']);
        return false; // Skip creating the post
    }

    // Create post content by including necessary data
    if (!isset($detailed_release['body'])) {
        error_log('Body field is missing in the detailed release data for release ID: ' . $release['release_id']);
        return false;
    }

    $post_content = wp_kses_post($detailed_release['body']); // Ensure this field exists in the API response

    // Log the post content
    error_log('Post content: ' . $post_content);

    // Append additional data if needed
    if (!empty($detailed_release['date'])) {
        $post_content .= '<p><strong>Date:</strong> ' . esc_html($detailed_release['date']) . '</p>';
    }
    if (!empty($detailed_release['source_company'])) {
        $post_content .= '<p><strong>Source Company:</strong> ' . esc_html($detailed_release['source_company']) . '</p>';
    }
    if (!empty($detailed_release['contact'])) {
        $post_content .= '<p><strong>Contact:</strong> ' . esc_html($detailed_release['contact']) . '</p>';
    }

    $post_data = array(
        'post_title'   => sanitize_text_field($detailed_release['title']),
        'post_content' => $post_content,
        'post_status'  => 'publish', 
        'post_category'=> array(2), 
    );

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        error_log('Error creating post from PR Newswire data: ' . $post_id->get_error_message());
        return false;
    }

    // Set featured image if available
    if (isset($detailed_release['multimedia']) && !empty($detailed_release['multimedia'])) {
        foreach ($detailed_release['multimedia'] as $media) {
            if ($media['type'] === 'photo') {
                set_featured_image_from_url($post_id, $media['url']);
                break;
            }
        }
    }

    error_log('Created post ID: ' . $post_id);

    return $post_id;
}

// Set featured image from URL
function set_featured_image_from_url($post_id, $image_url) {
    $image_data = file_get_contents($image_url);
    if ($image_data) {
        $upload_dir = wp_upload_dir();
        $filename = basename($image_url);
        $file = $upload_dir['path'] . '/' . $filename;

        file_put_contents($file, $image_data);

        $wp_filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title'     => sanitize_file_name($filename),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        $attach_id = wp_insert_attachment($attachment, $file, $post_id);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file);
        wp_update_attachment_metadata($attach_id, $attach_data);
        set_post_thumbnail($post_id, $attach_id);
    }
}

// Fetch and create PR Newswire posts
function fetch_and_create_pr_newswire_posts() {
    $releases = fetch_pr_newswire_data();
    if (!$releases) {
        error_log('Failed to fetch PR Newswire data');
        return;
    }

    foreach ($releases as $release) {
        create_post_from_pr_newswire($release);
    }
}

// Schedule the event
if (!wp_next_scheduled('fetch_pr_newswire_event')) {
    wp_schedule_event(time(), 'hourly', 'fetch_pr_newswire_event');
}

// Hook the event
add_action('fetch_pr_newswire_event', 'fetch_and_create_pr_newswire_posts');

// Manually trigger the cron job for testing
// add_action('init', function() {
//     fetch_and_create_pr_newswire_posts();
// });


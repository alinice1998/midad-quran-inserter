<?php
/**
 * Plugin Name:       Midad Quran Inserter
 * Description:       A Gutenberg block that allows searching and inserting Quranic verses in Uthmani script via Kalimat API.
 * Version:           1.0.0
 * Author:            Prototyper
 * Text Domain:       midad
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// 1. Text Domain and Settings Page
add_action( 'plugins_loaded', 'midad_load_textdomain' );
function midad_load_textdomain() {
    load_plugin_textdomain( 'midad', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_filter( 'gettext', 'midad_custom_translations', 10, 3 );
function midad_custom_translations( $translated_text, $text, $domain ) {
    if ( 'midad' === $domain ) {
        switch ( $text ) {
            case 'Search Quran (Kalimat API)':
                return 'البحث في القرآن الكريم (Kalimat API)';
            case 'Check your API Key settings.':
                return 'يرجى التحقق من إعدادات المفتاح / مزود الخدمة.';
            case 'Enter your X-Api-Key from %s':
                return 'أدخل مفتاحك الخاص بـ %s هنا';
            case 'Enter a word, surah, or surah:ayah...':
                return 'أدخل كلمة أو (رقم السورة:رقم الآية)';
        }
    }
    return $translated_text;
}

add_action('admin_menu', 'midad_register_settings_page');
function midad_register_settings_page() {
    add_options_page(
        __('Midad Quran Settings', 'midad'),
        __('Midad Quran', 'midad'),
        'manage_options',
        'midad-settings',
        'midad_settings_page_html'
    );
}

// Register Settings
add_action('admin_init', 'midad_register_settings');
function midad_register_settings() {
    register_setting('midad_settings_group', 'midad_kalimat_api_key');
}

// HTML for Settings Page
function midad_settings_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Midad Quran Inserter Settings', 'midad'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('midad_settings_group');
            do_settings_sections('midad_settings_group');
            
            $api_key = get_option('midad_kalimat_api_key', '');
            ?>
            <table class="form-table">
                <tr valign="top" id="kalimat_key_row">
                    <th scope="row"><?php esc_html_e('Kalimat API Key', 'midad'); ?></th>
                    <td>
                        <input type="password" name="midad_kalimat_api_key" value="<?php echo esc_attr($api_key); ?>" style="width:300px;" />
                        <p class="description">
                            <?php
                            printf(
                                esc_html__('Enter your X-Api-Key from %s', 'midad'),
                                '<a href="https://kalimat.dev" target="_blank" rel="noopener noreferrer">Kalimat.dev</a>'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// 2. Register REST API Endpoint for Search (Proxy)
add_action('rest_api_init', 'midad_register_rest_route');
function midad_register_rest_route() {
    register_rest_route('midad/v1', '/search', array(
        'methods' => 'GET',
        'callback' => 'midad_search_api',
        'permission_callback' => function () {
            return current_user_can('edit_posts'); // Only logged-in editors/authors
        }
    ));
}

function midad_search_api($request) {
    $query = sanitize_text_field($request->get_param('query'));
    
    if (empty($query)) {
        return new WP_Error('empty_query', __('Search query cannot be empty', 'midad'), array('status' => 400));
    }

    $api_key = get_option('midad_kalimat_api_key', '');
    if (empty($api_key)) {
        return new WP_Error('missing_key', __('Kalimat API key is not configured in settings', 'midad'), array('status' => 500));
    }

    $url = 'https://api.kalimat.dev/api/v2/search?query=' . urlencode($query) . '&getText=true&numResults=20';
    $response = wp_remote_get($url, array(
        'headers' => array(
            'X-Api-Key' => $api_key,
            'Accept' => 'application/json'
        ),
        'timeout' => 15
    ));

    if (is_wp_error($response)) {
        return $response;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    // Map of Arabic Surah Names (1-indexed)
    $surah_names = array(
        1 => "الفاتحة", 2 => "البقرة", 3 => "آل عمران", 4 => "النساء", 5 => "المائدة", 6 => "الأنعام", 7 => "الأعراف", 8 => "الأنفال", 9 => "التوبة", 10 => "يونس",
        11 => "هود", 12 => "يوسف", 13 => "الرعد", 14 => "إبراهيم", 15 => "الحجر", 16 => "النحل", 17 => "الإسراء", 18 => "الكهف", 19 => "مريم", 20 => "طه",
        21 => "الأنبياء", 22 => "الحج", 23 => "المؤمنون", 24 => "النور", 25 => "الفرقان", 26 => "الشعراء", 27 => "النمل", 28 => "القصص", 29 => "العنكبوت", 30 => "الروم",
        31 => "لقمان", 32 => "السجدة", 33 => "الأحزاب", 34 => "سبأ", 35 => "فاطر", 36 => "يس", 37 => "الصافات", 38 => "ص", 39 => "الزمر", 40 => "غافر",
        41 => "فصلت", 42 => "الشورى", 43 => "الزخرف", 44 => "الدخان", 45 => "الجاثية", 46 => "الأحقاف", 47 => "محمد", 48 => "الفتح", 49 => "الحجرات", 50 => "ق",
        51 => "الذاريات", 52 => "الطور", 53 => "النجم", 54 => "القمر", 55 => "الرحمن", 56 => "الواقعة", 57 => "الحديد", 58 => "المجادلة", 59 => "الحشر", 60 => "الممتحنة",
        61 => "الصف", 62 => "الجمعة", 63 => "المنافقون", 64 => "التغابن", 65 => "الطلاق", 66 => "التحريم", 67 => "الملك", 68 => "القلم", 69 => "الحاقة", 70 => "المعارج",
        71 => "نوح", 72 => "الجن", 73 => "المزمل", 74 => "المدثر", 75 => "القيامة", 76 => "الإنسان", 77 => "المرسلات", 78 => "النبأ", 79 => "النازعات", 80 => "عبس",
        81 => "التكوير", 82 => "الانفطار", 83 => "المطففين", 84 => "الانشقاق", 85 => "البروج", 86 => "الطارق", 87 => "الأعلى", 88 => "الغاشية", 89 => "الفجر", 90 => "البلد",
        91 => "الشمس", 92 => "الليل", 93 => "الضحى", 94 => "الشرح", 95 => "التين", 96 => "العلق", 97 => "القدر", 98 => "البينة", 99 => "الزلزلة", 100 => "العاديات",
        101 => "القارعة", 102 => "التكاثر", 103 => "العصر", 104 => "الهمزة", 105 => "الفيل", 106 => "قريش", 107 => "الماعون", 108 => "الكوثر", 109 => "الكافرون", 110 => "النصر",
        111 => "المسد", 112 => "الإخلاص", 113 => "الفلق", 114 => "الناس"
    );

    // Transform response to a unified format for our editor
    $unified_results = array();
    if (isset($data['data']['results']) && is_array($data['data']['results'])) {
        foreach ($data['data']['results'] as $res) {
            // Kalimat returns ID as "Surah:Ayah" (e.g. "2:255")
            $parts = explode(':', $res['id']);
            $surah_num = intval($parts[0] ?? 0);
            $ayah = isset($parts[1]) ? $parts[1] : '';
            
            $surah_name = isset($surah_names[$surah_num]) ? $surah_names[$surah_num] : $surah_num;

            // remove HTML highlights for clean insertion
            $clean_text = strip_tags($res['text'] ?? $res['textAr'] ?? '');
            
            $unified_results[] = array(
                'text' => $clean_text,
                'reference' => sprintf( __('Surah %1$s - Ayah %2$s', 'midad'), $surah_name, $ayah ),
                'raw_surah' => $surah_name,
                'raw_ayah' => $ayah
            );
        }
    }
    
    return rest_ensure_response($unified_results);
}

// 3. Register Block and Enqueue Assets
add_action('init', 'midad_register_gutenberg_block');
function midad_register_gutenberg_block() {
    // Automatically load dependencies and version
    wp_register_script(
        'midad-block-js',
        plugins_url('assets/block.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-api-fetch'),
        filemtime(plugin_dir_path(__FILE__) . 'assets/block.js')
    );
    
    // Pass translatable strings directly to JS
    wp_localize_script('midad-block-js', 'midadI18n', array(
        'title' => __('Midad Quran Inserter', 'midad'),
        'enter_search_term' => __('Please enter a search term', 'midad'),
        'no_verses_found' => __('No verses found. Try another keyword.', 'midad'),
        'api_error' => __('API Error: ', 'midad'),
        'check_api_key' => __('Check your API Key settings.', 'midad'),
        'search_quran' => __('Search Quran (Kalimat API)', 'midad'),
        'search_placeholder' => __('Enter a word, surah, or surah:ayah...', 'midad'),
        'searching' => __('Searching...', 'midad'),
        'search' => __('Search', 'midad'),
        'insert' => __('Insert', 'midad'),
        'surah' => __('Surah', 'midad'),
        'remove_search_again' => __('Remove & Search Again', 'midad')
    ));

    wp_register_style(
        'midad-editor-css',
        plugins_url('assets/editor.css', __FILE__),
        array('wp-edit-blocks'),
        filemtime(plugin_dir_path(__FILE__) . 'assets/editor.css')
    );

    wp_register_style(
        'midad-style-css',
        plugins_url('assets/style.css', __FILE__),
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'assets/style.css')
    );

    register_block_type('midad/quran-inserter', array(
        'editor_script' => 'midad-block-js',
        'editor_style'  => 'midad-editor-css',
        'style'         => 'midad-style-css',
    ));
}

<?php
/**
 * Plugin Name: Translation Proxy
 * Description: Purges proxy cache when the Wordpress site is updated.
 * Version: 0.0.1
 * Author: Yoshiaki Iinuma
 * License: GPL2
 */

//defined('ABSPATH') or die('Not allow to directly access this file')


class TranslationProxy
{
  /* Hooks used by wp super cache
   *
	// Post ID is received
	add_action( 'wp_trash_post', 'wp_cache_post_edit', 0 );
	add_action( 'publish_post', 'wp_cache_post_edit', 0 );
	add_action( 'edit_post', 'wp_cache_post_change', 0 ); // leaving a comment called edit_post
	add_action( 'delete_post', 'wp_cache_post_edit', 0 );
	add_action( 'publish_phone', 'wp_cache_post_edit', 0 );

	// Coment ID is received
	add_action( 'trackback_post', 'wp_cache_get_postid_from_comment', 99 );
	add_action( 'pingback_post', 'wp_cache_get_postid_from_comment', 99 );
	add_action( 'comment_post', 'wp_cache_get_postid_from_comment', 99 );
	add_action( 'edit_comment', 'wp_cache_get_postid_from_comment', 99 );
	add_action( 'wp_set_comment_status', 'wp_cache_get_postid_from_comment', 99, 2 );

	// No post_id is available
	add_action( 'switch_theme', 'wp_cache_no_postid', 99 );
	add_action( 'edit_user_profile_update', 'wp_cache_no_postid', 99 );
	add_action( 'wp_update_nav_menu', 'wp_cache_clear_cache_on_menu' );
	add_action( 'clean_post_cache', 'wp_cache_post_edit' );
	add_action( 'transition_post_status', 'wpsc_post_transition', 10, 3 );

	// Cron hooks
	add_action( 'wp_cache_gc','wp_cache_gc_cron' );
	add_action( 'wp_cache_gc_watcher', 'wp_cache_gc_watcher' );
   */

  /* Hooks used by varnish purge cache
   *
    // With Post ID
    'save_post',                       // Fires onece a post has benn saved
    'deleted_post',                    // Delete a post.
    'trashed_post',                    // Empty Trashed post.
    'edit_post',                       // Edit a post - includes leaving comments.
    'delete_attachment',               // Delete an attachment - includes re-uploading.

    // No Post ID
    'switch_theme',                     // After a theme is changed.
    'autoptimize_action_cachepurged,',  // Compat with https://wordpress.org/plugins/autoptimize/ plugin.
   */

  /**
   * POST, PAGE
   *
   *  - add new: ### NO NEED
   *      save_post auto-draft
   *      edit_post draft
   *      save_post draft
   *
   *  - update draft: ### NO NEED
   *      edit_post (draft)
   *      save_post (draft) => XXX handled too many
   *
   *  - publish: ### NO NEED
   *      edit_post publish
   *      save_post inherit (revision)
   *      save_post publish 
   *
   *  - update published: OOO
   *      edit_post (publish) OOO
   *      save_post (publish) => XXX handled too many
   *
   *  - publish to private: OOO
   *  - publik to password protected:
   *      transition_post_status (publish > private) OOO
   *      edit_post (private) ***
   *      save_post (publish) => XXX handled too many
   *
   *  - publish to draft: OOO
   *  - publish to pending: OOO
   *      transition_post_status (publish > draft, pending) OOO
   *      edit_post (draft, pending) => XXX ?p=nnnn
   *      save_post (publish) => XXX handled too many
   *
   *  - trash: OOO
   *      wp_trash_post ***
   *      transition_post_status (publish > trash) OOO
   *      edit_post => XXX __trashed url
   *      save_post => XXX __trashed url
   *
   *  - delete: ### NO NEED already trashed
   *      wp_trash_post OOO
   *      transition_post_status (publish > trash)
   *      edit_post => XXX __trashed url
   *      save_post => XXX __trashed url
   *      delete_post => XXX delete trashed posts and revisions
   *
   *
   * ATTACHMENT
   *
   *  - add new: ### NO NEED
   *      update_attached_file
   *      add_attachment path to file
   *
   *  - edit: OOO
   *      edit_attachment OOO attachment post page
   *
   *  - delete: OOO
   *      delete_attachment OOO path to file, attachment post page 
   *      delete_post *** attachment post page
   *
   *  - replace media: OOO
   *      update_attached_file
   *
   *
   * ENTIRE SITE
   *
   *  - switch:
   *      swith_theme OOO
   *
   *  - update menu: OOO
   *      wp_update_nav_menu  : menu_id and menu_data
   *      transition_post_status (publish > publish) : on all the menu items
   *      edit_post (publish) : on all the menu items
   *      save_post (publish) : on all the menu items
   *      wp_update_nav_menu  : only menu_id OOO
   */
  public static $pageUpdateEvents = array(
    'edit_post',                       // Fires onece an existing post has been updated
    'save_post',                       // Fires onece a post has been saved
    //'delete_post',                     // Fires immediately before a post is deleted from the database
    'deleted_post',                    // Fires immediately after a post is deleted from the database
    //'wp_trash_post',                   // Fires before a post is sent to the trash
    'trashed_post',                    // Fires after a post is sent to the trash
    'edit_attachment',                 // Fires onece an existing attachment has been updated
    'delete_attachment',               // Fires before an attachment is deleted, includes re-uploading.
    'transition_post_status',          // Fires when a post is transitioned from one status to another (publish to something)
    //'clean_post_cache',                // Fires immediately after the given post's cache is cleaned
  );

  public static $siteUpdateEvents = array(
    'switch_theme',                    // Fires after a theme is switched
    'wp_update_nav_menu',              // Fires after naviagtion menu has been successfully updated
    //'autoptimize_action_cachepurged,',  // Compat with https://wordpress.org/plugins/autoptimize/ plugin.
  );

  /**
   * edit_post is enough
   * save_post handle revision and more
   */
  //public static function on_save_post($post_id, $post) {
  //  $url = get_permalink($post_id);
  //  $status = $post->post_status;
  //  self::dbg("SAVE_POST: $post_id $status $url");
  //}

  /**
   * deleted_post cannot provide the url for cached one
   */
  //public static function on_deleted_post($post_id) {
  //  $url = get_permalink($post_id);
  //  self::dbg("DELETED_POST: $post_id $url");
  //}

  /**
   * trashed_post cannot provide the url for cached one
   */
  //public static function on_trashed_post($post_id) {
  //  $url = get_permalink($post_id);
  //  self::dbg("TRASHED_POST: $post_id $url");
  //}

  /**
   * For page and post,
   *
   * For attachment, cannot get the url to the attachment, must use delete_attachment
   */
  //public static function on_delete_post($post_id) {
  //  $url = get_permalink($post_id);
  //  self::dbg("DELETE_POST: $post_id $url");
  //}

  //public static function on_wp_trash_post($post_id) {
  //  $url = get_permalink($post_id);
  //  self::dbg("WP_TRASH_POST: $post_id $url");
  //}

  //public static function on_add_attachment($post_id) {
  //  $url = wp_get_attachment_url($post_id);
  //  self::dbg("ADD_ATTACHMENT: $post_id $url");
  //}

  //const DEBUG = true;
  public static $debug = true;
  public static $enabled = false;

  public static function on_edit_post($post_id, $post) {
    $status = $post->post_status;
    if ($status == 'publish' || $status == 'private') {
      $url = get_permalink($post_id);
      self::purge_url($url, "EDIT_POST: $status $url");
    }
  }

  public static function on_transition_post_status($new_status, $old_status, $post) {
    self::dbg("TRANSITION_POST_STATUS $old_status => $new_status");
    if ($old_status == 'publish') {
      if ($new_status != 'publish') {
        $url = get_permalink($post->id);
        self::purge_url($url, "TRANSITION_POST_STATUS: $old_status => $new_status $url");
      }
    }
  }

  public static function on_edit_attachment($post_id) {
    $url = wp_get_attachment_url($post_id);
    $perma = get_permalink($post_id);
    self::purge_url($url, "EDIT_ATTACHMENT: $post_id $perma");
  }

  public static function on_delete_attachment($post_id) {
    $url = wp_get_attachment_url($post_id);
    $perma = get_permalink($post_id);
    self::purge_url($url, "DELETE_ATTACHMENT: $url");
    self::purge_url($url, "DELETE_ATTACHMENT: $perma");
  }

  public static function on_update_attached_file($file, $post_id) {
    $url = wp_get_attachment_url($post_id);
    $perma = get_permalink($post_id);
    self::purge_url($url, "UPDATE_ATTCHED_FILE: $url");
    self::purge_url($perma, "UPDATE_ATTCHED_FILE: $perma");
    return $file;
  }

  public static function on_switch_theme() {
    self::purge_all("SWITCH_THEME");
  }

  public static function on_wp_update_nav_menu($menu_id, $menu_data = null) {
    if (is_null($menu_data)) {
      self::purge_all("WP_UPDATE_NAV_MENU");
    }
  }

  public static function set_purge_hooks() {
    self::dbg('SET-HOOKS');
    //add_action('save_post', 'TranslationProxy::on_save_post', 10, 2);
    //add_action('deleted_post', 'TranslationProxy::on_deleted_post', 10, 1);
    //add_action('trashed_post', 'TranslationProxy::on_trashed_post', 10, 1);
    //add_action('delete_post', 'TranslationProxy::on_delete_post', 10, 1);
    //add_action('wp_trash_post', 'TranslationProxy::on_wp_trash_post', 10, 1);
    //add_action('add_attachment', 'TranslationProxy::on_add_attachment', 10, 1);
    add_action('edit_post', 'TranslationProxy::on_edit_post', 10, 2);
    add_action('transition_post_status', 'TranslationProxy::on_transition_post_status', 10, 3);
    add_action('edit_attachment', 'TranslationProxy::on_edit_attachment', 10, 1);
    add_action('delete_attachment', 'TranslationProxy::on_delete_attachment', 10, 1);
    add_filter('update_attached_file', 'TranslationProxy::on_update_attached_file', 10, 2);

    add_action('switch_theme', 'TranslationProxy::on_switch_theme');
    add_action('wp_update_nav_menu', 'TranslationProxy::on_wp_update_nav_menu', 10, 2);
  }

  public static function load_scripts() {
    wp_enqueue_style('translation-proxy', plugin_dir_url(__FILE__) . 'css/translation-proxy.css');
    wp_enqueue_script('jquery');
    wp_enqueue_script('translation-proxy', plugin_dir_url(__FILE__) . 'js/translation-proxy.js');
  }

  public static function inject_lang_selector($buffer) {
    //self::dbg($buffer);
    //self::dbg('######################'); 
    //self::dbg(strpos($buffer, 'Stay Connected')); 
    //self::dbg('######################'); 
    //$text = str_replace('Stay Connected', 'Yoshiaki Iinuma', $buffer);
    //$text = str_replace($find, $replace, $buffer);
    $pos = strpos($buffer, self::LOCATION);
    $text = substr_replace($buffer, self::LANG_SELECT, $pos, 0);
    return $text;
    //return $buffer;
  }

  public static function on_the_title($title) {
    ob_end_flush();
    return $title;
  }

  public static function on_header_end() {
    ob_end_flush();
    error_log("HEADER END");
  }

  public static function on_wp_head() {
    error_log("WP_HEAD");
    ob_start('TranslationProxy::inject_lang_selector');
  }

  public static function set_inject_hooks() {
    error_log('SET INJECT HOOKS');
    add_action('wp_head', 'TranslationProxy::on_wp_head', 10);
    add_filter('the_title', 'TranslationProxy::on_the_title', 10, 1);
    //add_action('wp_print_scripts', 'TranslationProxy::on_header_end', 10);
    //add_action('wp_print_styles', 'TranslationProxy::on_header_end', 10);
    //add_action('wp_enqueue_scripts', 'TranslationProxy::on_header_end', 10); //=> </html>
  }

  public static function register_settings() {
    register_setting('tranlation-proxy-settings-group', 'general');
    register_setting('tranlation-proxy-settings-group', 'cache');
  }

  public static function setup_admin() {
    //add_menu_page('Translation Proxy', 'Translation Proxy Settings', 'manage_options', 'translation-proxy-settings', 'TranslationProxy::admin_page', plugins_url('globe.png', __FILE__));
    add_menu_page('Translation Proxy Settings', 'Translation Proxy', 'manage_options', 'translation-proxy-settings', 'TranslationProxy::admin_page');
    add_options_page('Translation Proxy Settings', 'Translation Proxy', 'manage_options', 'translation-proxy-settings', 'TranslationProxy::admin_page');
    self::register_settings();
    //add_action('admin_init', 'register_settings');
  }

  public static function admin_page() {
    ?>
    <div id="translation_proxy_admin_panel" class="wrap">
      <h1>Translation Proxy Settings</h1>
      <?php if (isset($_GET['flash']) && $_GET['flash'] == '1') { ?>
        <div id='flash_message' class="updated fade">
          <p><strong>Purge All request was sent to the proxy.</strong></p>
        </div>
      <?php } ?>
      <form method="post" action="admin-post.php">

       <input type="hidden" name="action" value="translation_proxy_purge_all" />
       <?php wp_nonce_field( 'translation_proxy' ); ?>

      <h2>Cache</h2>
      <p class="submit">
        <input type="submit" value="Purge All" class="button-primary"/>
      </p>
      </form>
    </div>
    <?php
  }

  public static function handle_purge_all() {
    //if (!current_user_can('manage_options')) {
    if (!current_user_can('manage_options') || wp_doing_ajax()) {
      wp_die('Not allowed');
    }
    self::purge_all('HANDLE PURGE ALL');

    $url = add_query_arg(
      array( 'page' => 'translation-proxy-settings', 'flash' => '1'),
      admin_url('options-general.php'));
    self::dbg("REDIRECTING TO $url");

    wp_redirect($url);

    exit;
  }

  public static function set_purge_request_controller() {
    add_action('admin_post_translation_proxy_purge_all', 'TranslationProxy::handle_purge_all');
  }

  public static function initialize() {
    self::set_purge_request_controller();
    //self::set_inject_hooks();
    self::set_purge_hooks();

    /*
    foreach(self::$pageUpdateEvents as $e) {
      self::dbg($e);
      //add_action($e, 'TranslationProxy::flush_single');
      add_action($e, 'self::flush_single');
    };

    foreach(self::$siteUpdateEvents as $e) {
      self::dbg($e);
      //add_action($e, 'TranslationProxy::flush_all');
      add_action($e, 'self::flush_all');
    };
     */

  }

  public static function activate($network_wide) {
    if (is_multisite() && $network_wide) {
      self::dbg('TranslationProxy Network Activation is not allowed');
      //wp_die('Network Activation is not enabled.');
    }
    self::dbg('TranslationProxy Got Activated!');
  }

  public static function deactivate() {
    self::dbg('TranslationProxy Got Deactivated!');
  }

  function purge_all($msg = null) {
    $url = home_url() . '/purge-proxy-cache?page=all';
    self::purge_url($url, $msg);
  }

  /**
   * NOTE: could use wp_remote_request
   *
      $response = wp_remote_request( $purgeme, array(
        'method'  => 'PURGE',
        'headers' => $headers,
      ) );
   *
   */
  public static function purge_url($url, $msg = null) {
    if (self::$enabled) {
      //$curl = curl_init($url);
      //curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PURGE");
      //curl_exec($curl);
    }
    if (!is_null($msg)) self::dbg($msg);
    self::dbg("PURGE: $url");
  }

  /**
   * Add the followings to wp-config.php
      define('WP_DEBUG', true);
      define('WP_DEBUG_DISPLAY', false);
      define('WP_DEBUG_LOG', true);
   */
  static function dbg($msg) {
    if (!self::$debug) return;
    if (is_array($msg) || is_object($msg)) {
      error_log(print_r($msg, true));
    } else {
      error_log($msg);
    }
  }

	const LOCATION =	'<div class="notmobile statewide-banner-right">';

  const LANG_SELECT =<<<EOT
    <div class="notmobile statewide-banner-left">
      <div id="google_cloud_translation_selector">
                           
				<select id="lang_choices" onchange="changeLanguage(this)">
				  <option value="" selected>Select Language</option>
				  <option id="langopt-af" value="af">Afrikaans</option>
				  <option id="langopt-sq" value="sq">Albanian</option>
				  <option id="langopt-am" value="am">Amharic</option>
				  <option id="langopt-ar" value="ar">Arabic</option>
				  <option id="langopt-hy" value="hy">Armenian</option>
				  <option id="langopt-az" value="az">Azerbaijani</option>
				  <option id="langopt-eu" value="eu">Basque</option>
				  <option id="langopt-be" value="be">Belarusian</option>
				  <option id="langopt-bn" value="bn">Bengali</option>
				  <option id="langopt-bs" value="bs">Bosnian</option>
				  <option id="langopt-bg" value="bg">Bulgarian</option>
				  <option id="langopt-ca" value="ca">Catalan</option>
				  <option id="langopt-ceb" value="ceb">Cebuano</option>
				  <option id="langopt-zh-CN" value="zh-CN">Chinese (Simplified)</option>
				  <option id="langopt-zh-TW" value="zh-TW">Chinese (Traditional)</option>
				  <option id="langopt-co" value="co">Corsican</option>
				  <option id="langopt-hr" value="hr">Croatian</option>
				  <option id="langopt-cs" value="cs">Czech</option>
				  <option id="langopt-da" value="da">Danish</option>
				  <option id="langopt-nl" value="nl">Dutch</option>
				  <option id="langopt-en" value="en">English</option>
				  <option id="langopt-eo" value="eo">Esperanto</option>
				  <option id="langopt-et" value="et">Estonian</option>
				  <option id="langopt-fi" value="fi">Finnish</option>
				  <option id="langopt-fr" value="fr">French</option>
				  <option id="langopt-fy" value="fy">Frisian</option>
				  <option id="langopt-gl" value="gl">Galician</option>
				  <option id="langopt-ka" value="ka">Georgian</option>
				  <option id="langopt-de" value="de">German</option>
				  <option id="langopt-el" value="el">Greek</option>
				  <option id="langopt-gu" value="gu">Gujarati</option>
				  <option id="langopt-ht" value="ht">Haitian Creole</option>
				  <option id="langopt-ha" value="ha">Hausa</option>
				  <option id="langopt-haw" value="haw">Hawaiian</option>
				  <option id="langopt-he" value="he">Hebrew</option>
				  <option id="langopt-hi" value="hi">Hindi</option>
				  <option id="langopt-hmn" value="hmn">Hmong</option>
				  <option id="langopt-hu" value="hu">Hungarian</option>
				  <option id="langopt-is" value="is">Icelandic</option>
				  <option id="langopt-ig" value="ig">Igbo</option>
				  <option id="langopt-id" value="id">Indonesian</option>
				  <option id="langopt-ga" value="ga">Irish</option>
				  <option id="langopt-it" value="it">Italian</option>
				  <option id="langopt-ja" value="ja">Japanese</option>
				  <option id="langopt-jw" value="jw">Javanese</option>
				  <option id="langopt-kn" value="kn">Kannada</option>
				  <option id="langopt-kk" value="kk">Kazakh</option>
				  <option id="langopt-km" value="km">Khmer</option>
				  <option id="langopt-ko" value="ko">Korean</option>
				  <option id="langopt-ku" value="ku">Kurdish</option>
				  <option id="langopt-ky" value="ky">Kyrgyz</option>
				  <option id="langopt-lo" value="lo">Lao</option>
				  <option id="langopt-la" value="la">Latin</option>
				  <option id="langopt-lv" value="lv">Latvian</option>
				  <option id="langopt-lt" value="lt">Lithuanian</option>
				  <option id="langopt-lb" value="lb">Luxembourgish</option>
				  <option id="langopt-mk" value="mk">Macedonian</option>
				  <option id="langopt-mg" value="mg">Malagasy</option>
				  <option id="langopt-ms" value="ms">Malay</option>
				  <option id="langopt-ml" value="ml">Malayalam</option>
				  <option id="langopt-mt" value="mt">Maltese</option>
				  <option id="langopt-mi" value="mi">Maori</option>
				  <option id="langopt-mr" value="mr">Marathi</option>
				  <option id="langopt-mn" value="mn">Mongolian</option>
				  <option id="langopt-my" value="my">Myanmar (Burmese)</option>
				  <option id="langopt-ne" value="ne">Nepali</option>
				  <option id="langopt-no" value="no">Norwegian</option>
				  <option id="langopt-ny" value="ny">Nyanja (Chichewa)</option>
				  <option id="langopt-ps" value="ps">Pashto</option>
				  <option id="langopt-fa" value="fa">Persian</option>
				  <option id="langopt-pl" value="pl">Polish</option>
				  <option id="langopt-pt" value="pt">Portuguese (Portugal, Brazil)</option>
				  <option id="langopt-pa" value="pa">Punjabi</option>
				  <option id="langopt-ro" value="ro">Romanian</option>
				  <option id="langopt-ru" value="ru">Russian</option>
				  <option id="langopt-sm" value="sm">Samoan</option>
				  <option id="langopt-gd" value="gd">Scots Gaelic</option>
				  <option id="langopt-sr" value="sr">Serbian</option>
				  <option id="langopt-st" value="st">Sesotho</option>
				  <option id="langopt-sn" value="sn">Shona</option>
				  <option id="langopt-sd" value="sd">Sindhi</option>
				  <option id="langopt-si" value="si">Sinhala (Sinhalese)</option>
				  <option id="langopt-sk" value="sk">Slovak</option>
				  <option id="langopt-sl" value="sl">Slovenian</option>
				  <option id="langopt-so" value="so">Somali</option>
				  <option id="langopt-es" value="es">Spanish</option>
				  <option id="langopt-su" value="su">Sundanese</option>
				  <option id="langopt-sw" value="sw">Swahili</option>
				  <option id="langopt-sv" value="sv">Swedish</option>
				  <option id="langopt-tl" value="tl">Tagalog (Filipino)</option>
				  <option id="langopt-tg" value="tg">Tajik</option>
				  <option id="langopt-ta" value="ta">Tamil</option>
				  <option id="langopt-te" value="te">Telugu</option>
				  <option id="langopt-th" value="th">Thai</option>
				  <option id="langopt-tr" value="tr">Turkish</option>
				  <option id="langopt-uk" value="uk">Ukrainian</option>
				  <option id="langopt-ur" value="ur">Urdu</option>
				  <option id="langopt-uz" value="uz">Uzbek</option>
				  <option id="langopt-vi" value="vi">Vietnamese</option>
				  <option id="langopt-cy" value="cy">Welsh</option>
				  <option id="langopt-xh" value="xh">Xhosa</option>
				  <option id="langopt-yi" value="yi">Yiddish</option>
				  <option id="langopt-yo" value="yo">Yoruba</option>
				  <option id="langopt-zu" value="zu">Zulu</option>
				</select>
        <span id="translation-disclaimer-link">Translation Disclaimer</span>
      </div>
    </div>
    <div id="translation-disclaimer">
      <div class="primary-content">
        <div class="pagetitle">
          <h2>Website Translation Disclaimer</h2>
        </div>
        <hr/>
        <p>THIS SERVICE MAY CONTAIN TRANSLATIONS POWERED BY GOOGLE. GOOGLE DISCLAIMS ALL WARRANTIES RELATED TO THE TRANSLATIONS, EXPRESS OR IMPLIED, INCLUDING ANY WARRANTIES OF ACCURACY, RELIABILITY, AND ANY IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.</p>
        <p>The website has been translated for your convenience using translation software powered by Google Translate. Reasonable efforts have been made to provide an accurate translation, however, no automated translation is perfect nor is it intended to replace human translators. Translations are provided as a service to users of the website, and are provided “as is.” No warranty of any kind, either expressed or implied, is made as to the accuracy, reliability, or correctness of any translations made from English into any other language. Some content (such as images, videos, Flash, etc.) may not be accurately translated due to the limitations of the translation software.</p>
        <p>The official text is the English version of the website. Any discrepancies or differences created in the translation are not binding and have no legal effect for compliance or enforcement purposes. If any questions arise related to the accuracy of the information contained in the translated website, please refer to the English version of the website which is the official version.</p>
      </div>
    </div>
EOT;

}

/**
 * MEMO
 *   init -> adimin_init
 *
             current_user_can('manage_options') && !(wp_doing_ajax())
 */
//add_action('init', 'TranslationProxy::set_purge_hooks');
add_action('admin_init', 'TranslationProxy::initialize');
add_action('admin_menu', 'TranslationProxy::setup_admin');
register_activation_hook(__FILE__, 'TranslationProxy::activate');
register_deactivation_hook(__FILE__, 'TranslationProxy::deactivate');

add_action('wp', 'TranslationProxy::set_inject_hooks');
add_action('wp_enqueue_scripts', 'TranslationProxy::load_scripts');

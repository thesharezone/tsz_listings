<?php
/*
Plugin Name: The Share Zone Listings
Description: Custom post type for TSZ listings
Version: 1.0
Author: The Share Zone
*/

defined('ABSPATH') or die;

show_admin_bar(false);

require_once __DIR__ . '/securimage/securimage.php';
require_once __DIR__ . '/config.php';



/// crypto functions and vars
function tsz_key_details() {
  $fp = fopen(PRIVATE_KEY_PATH, "r");
  $pk = fread($fp, 8192);
  fclose($fp);

  $kh = openssl_pkey_get_private($pk);

  return openssl_pkey_get_details($kh);

}

function tsz_decrypt_email($encrypted) {
  $fp = fopen(PRIVATE_KEY_PATH, "r");
  $pk = fread($fp, 8192);
  fclose($fp);

  $kh = openssl_pkey_get_private($pk);

  $data = @pack('H*', $encrypted);
  openssl_private_decrypt($data, $decrypted_email, $kh);

  return $decrypted_email;

}

function tsz_encrypt_email($decrypted) {
  $fp = fopen(PUBLIC_KEY_PATH, "r");
  $pk = fread($fp, 8192);
  fclose($fp);

  $kh = openssl_pkey_get_public($pk); 

  openssl_public_encrypt($decrypted, $encrypted, $kh); // use same pair for now...

  return bin2hex($encrypted);
}

function tsz_hash($data) {
  global $algorithms;
  foreach($algorithms as $algo) {
    $data = hash($algo, $data);
  }
  return $data;
}

function to_hex($data)
{
    return strtoupper(bin2hex($data));
}


/////////////

function get_attachments($id) {
    $args = array(
     'post_type' => 'attachment',
     'orderby' => 'menu_order',
     'order'            => 'ASC',
     'numberposts' => -1,
     'post_status' => null,
     'post_parent' => $id
    );

  return get_posts( $args );

}

function first_image_tag($id, $size) {
  $attachments = get_attachments($id);

  $attachment = reset($attachments);

  echo wp_get_attachment_image( $attachment->ID, $size );
}



add_action('init', 'tsz_listing_register');
 
function tsz_listing_register() {
 
  $labels = array(
    'name' => _x('Listings', 'post type general name'),
    'singular_name' => _x('Listing', 'post type singular name'),
    'add_new' => _x('Add New', 'Listing'),
    'add_new_item' => __('Add New Listing'),
    'edit_item' => __('Edit Listing'),
    'new_item' => __('New Listing'),
    'view_item' => __('View Listing'),
    'search_items' => __('Search Listings'),
    'not_found' =>  __('No listings found'),
    'not_found_in_trash' => __('No listings found in Trash'),
    'parent_item_colon' => ''
  );
 
  $args = array(
    'labels' => $labels,
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true,
    'query_var' => true,
    'menu_icon' => plugins_url( 'img/icon.png' , __FILE__ ),
    'rewrite' => true,
    'capability_type' => 'post',
    'hierarchical' => false,
    'menu_position' => 21,
    'supports' => array('title','editor','thumbnail'),
    'has_archive' => true,
    'taxonomies' => array('post_tag')
    ); 
 
	register_post_type( 'listing' , $args );
	
	//If there is permalink wonkiness enable this:
	//flush_rewrite_rules();
}


add_post_type_support( 'listing', 'page-attributes' ); // so we can use menu_order

add_action("admin_init", "tsz_listing_admin_init");
 
function tsz_listing_admin_init(){
  add_meta_box("tsz_location_meta", "Location", "tsz_location_meta", "listing", "normal", "high");
}

add_action( 'add_meta_boxes', 'action_add_meta_boxes' );
function action_add_meta_boxes() {
  global $_wp_post_type_features;
  global $post;
  if ($post->post_type == "listing" && isset($_wp_post_type_features['listing']['editor']) && $_wp_post_type_features['listing']['editor']) {
    unset($_wp_post_type_features['listing']['editor']);
    add_meta_box(
      'description_section',
      __('Description'),
      'inner_custom_box',
      'listing', 'normal'
    );

  }
}

function inner_custom_box( $post ) {
  the_editor($post->post_content);
}

add_action('admin_menu' , 'tsz_listing_options');

function tsz_listing_options() 
{
  add_submenu_page('edit.php?post_type=listing', 'Settings', 'Settings', 'manage_options', 'tsz_listing_options', 'tsz_listing_options_page' );

}

/**
 * Options page callback
 */
function tsz_listing_options_page()
{
    ?>
    <div class="wrap">
        <?php screen_icon(); ?>         
        <form method="post" action="options.php">
        <?php
            // This prints out all hidden setting fields
            settings_fields( 'tsz_listing_options' );   
            do_settings_sections( 'tsz_listing_options_page' );
            submit_button(); 
        ?>
        </form>
    </div>
    <?php
}

add_action( 'admin_init', 'tsz_page_init' );

/**
 * Register and add settings
 */
function tsz_page_init()
{        

  register_setting(
      'tsz_listing_options', // Option group
      'google_maps_api_key'
  );

  register_setting(
      'tsz_listing_options', // Option group
      'image_upload_limit'
  );

  register_setting(
      'tsz_listing_options', // Option group
      'expiration_period'
  );





  add_settings_section(
      'setting_section_id', // ID
      'Listings Plugin Settings', // Title
      'print_section_info', // Callback
      'tsz_listing_options_page' // Page
  );  

  add_settings_field(
      'google_maps_api_key', // ID
      'Google Maps API Key', // Title 
      'google_maps_api_key_callback', // Callback
      'tsz_listing_options_page', // Page
      'setting_section_id' // Section        
  ); 

  add_settings_field(
      'image_upload_limit', // ID
      'Images per Listing', // Title 
      'image_upload_limit_callback', // Callback
      'tsz_listing_options_page', // Page
      'setting_section_id' // Section        
  ); 

  add_settings_field(
      'expiration_period', // ID
      'Days before Listing expires', // Title 
      'expiration_period_callback', // Callback
      'tsz_listing_options_page', // Page
      'setting_section_id' // Section        
  );


}

/** 
 * Print the Section text
 */
function print_section_info()
{
    printf( 'Information required for the plugin to function.');
}


function google_maps_api_key_callback()
{
    printf(
        '<input type="text" name="google_maps_api_key" value="%s"  />',
        get_site_option('google_maps_api_key')
    );
}

function image_upload_limit_callback()
{
    printf(
        '<input type="text" name="image_upload_limit" value="%s"  />',
        get_site_option('image_upload_limit')
    );
}

function expiration_period_callback()
{
    printf(
        '<input type="text" name="expiration_period" value="%s"  />',
        get_site_option('expiration_period')
    );
}



 
function tsz_location_meta(){
  global $post;
  $custom = get_post_custom($post->ID);
  
  $tsz_listing_address = $custom["tsz_listing_address"][0];

  ?>
  <div>
    <p>
      <label>Cross Streets: </label><input type="text" name="tsz_listing_address" value="<?php echo $tsz_listing_address; ?>" size="70" /> 
    </p>
  </div>

  <?php
}

add_action('save_post', 'save_tsz_listing_metadata'); // for mapping
function save_tsz_listing_metadata(){
  global $post;
 
  update_post_meta($post->ID, "tsz_listing_address", $_POST["tsz_listing_address"]);

}

function maps_api_key() {
  if( $key = get_site_option( "google_maps_api_key" ) )
    return "&key=" . $key;
  else
    return "";
}



// pluggable function, need to decrypt user email for notification
if(!function_exists('wp_new_user_notification')) {
function wp_new_user_notification($user_id, $plaintext_pass = '') {

  $user = get_userdata( $user_id );

  $decrypted_email = tsz_decrypt_email($user->user_email);

  // The blogname option is escaped with esc_html on the way into the database in sanitize_option
  // we want to reverse this for the plain text arena of emails.
  $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

  $message  = sprintf(__('New user registration on your site %s:'), $blogname) . "\r\n\r\n";
  $message .= sprintf(__('Username: %s'), $user->user_email) . "\r\n\r\n";
  $message .= sprintf(__('E-mail: %s'), $decrypted_email) . "\r\n";

  @wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Registration'), $blogname), $message);

  if ( empty($plaintext_pass) )
    return;

  $message  = sprintf(__('Email: %s'), $decrypted_email) . "\r\n";
  $message .= sprintf(__('Password: %s'), $plaintext_pass) . "\r\n";
  $message .= get_bloginfo("url") . "/login" . "\r\n";
  $message .= "\r\n";
  $message .= "IMPORTANT!  You must login and save your listing once before it becomes active.  Listings not activated within 12 hours are automatically destroyed." . "\r\n";

  wp_mail($decrypted_email, sprintf(__('[%s] Your username and password'), $blogname), $message);

}}

// ignore invalid email address, we validate in js, and save encrypted email
add_filter('registration_errors', 'tsz_fix_registration_errors', 10, 3); 
function tsz_fix_registration_errors($errors, $sanitized_user_login, $user_email) {
  unset($errors->errors['invalid_email']);
  unset($errors->errors['invalid_username']);

    return $errors;

}


// check hashed email against decrypted, if they don't match then someone is probably trying some funny tricks
/*
add_action('register_post','check_hash', 10, 3); 
function check_hash ($sanitized_user_login, $user_email, $errors){

  $decrypted_email = tsz_decrypt_email($sanitized_user_login);

  if(sha1($decrypted_email) != $_POST['hash'])
    die("bad hash sent");

}
*/

add_filter('pre_user_email', 'tsz_restore_email', 10, 1);  // email got set to "" because it wasn't valid
function tsz_restore_email($user_email) {
  $user_email = $_POST['email'];
  return $user_email;

}

add_filter('pre_user_nicename', 'tsz_hash_as_nicename', 10, 1);
function tsz_hash_as_nicename($user_nicename) {
  $user_nicename = tsz_hash(tsz_decrypt_email($_POST['email']));
  return $user_nicename;

}

add_filter('pre_user_display_name', 'tsz_anonymous_display_name', 10, 1);
function tsz_anonymous_display_name($user_display_name) {
  return "Anonymous";
}

add_filter('authenticate', 'tsz_auth_login',10,3); // takes decrypted email and hashes it for login
function tsz_auth_login ($user, $username, $password) {
    $username = tsz_hash($username);
    return wp_authenticate_username_password($user, $username, $password);
}



function get_sample_listing() {
  $listings = get_posts(array(
          'post_type' => "listing",
          'post_status' => "publish",
          'orderby' => 'post_date',
          'order' => 'ASC',
          'limit' => 1
    ));

  return reset($listings);
}






///  TSZ Listings Templates, can be over ridden in theme directory
add_action("template_redirect", 'tsz_theme_redirect');

function tsz_theme_redirect() {
    global $wp;

    $supported_pages = array(
        "edit-listing" => "tsz_listings",
        "create-listing" => "tsz_listings",
        "login" => "tsz_listings"
    );

    $supported_pages = apply_filters("tsz_supported_pages", $supported_pages);

    //die(print_r($wp->query_vars));

    if ($wp->query_vars["post_type"] == 'listing') {
        if(isset($wp->query_vars["listing"]))
          $templatefilename = 'single-listing.php';
        else
          $templatefilename = 'archive-listing.php';
        if (file_exists(TEMPLATEPATH . '/' . $templatefilename)) {
            $return_template = TEMPLATEPATH . '/' . $templatefilename;
        } else {
            $return_template = dirname( __FILE__ ) . '/themefiles/' . $templatefilename;
        }
    } elseif (isset($supported_pages[$wp->query_vars["pagename"]])) {
        $templatefilename = $wp->query_vars["pagename"] . '.php';
        if (file_exists(TEMPLATEPATH . '/' . $templatefilename)) {
            $return_template = TEMPLATEPATH . '/' . $templatefilename;
        } else {
            $return_template = 
              WP_PLUGIN_DIR . '/' . 
              $supported_pages[$wp->query_vars["pagename"]] . 
              '/themefiles/' . $templatefilename;
        }
    }

    if(isset($return_template)) {
      include $return_template;
      die();
    }

}


add_action( 'wp_enqueue_scripts', 'tsz_scripts', 12 );
function tsz_scripts() {
  wp_enqueue_style( 'tsz-style', plugins_url('themefiles/css/tsz.css', __FILE__) );
  wp_enqueue_style( 'jquery.datetimepicker', plugins_url('themefiles/css/jquery.datetimepicker.css', __FILE__) );
  wp_enqueue_style( 'fullcalendar', plugins_url('themefiles/css/fullcalendar.css', __FILE__) );

  wp_enqueue_script( 'jsbn', plugins_url('themefiles/js/jsbn.js', __FILE__) );
  wp_enqueue_script( 'prng4', plugins_url('themefiles/js/prng4.js', __FILE__) );
  wp_enqueue_script( 'rng', plugins_url('themefiles/js/rng.js', __FILE__) );
  wp_enqueue_script( 'rsa', plugins_url('themefiles/js/rsa.js', __FILE__) );
  wp_enqueue_script( 'jquery.cookie', plugins_url('themefiles/js/jquery.cookie.js', __FILE__), array( 'jquery' ) );
  wp_enqueue_script( 'jquery.datetimepicker', plugins_url('themefiles/js/jquery.datetimepicker.js', __FILE__), array( 'jquery' ) );
  wp_enqueue_script( 'moment', plugins_url('themefiles/js/moment.min.js', __FILE__), array( 'jquery' ) );
  wp_enqueue_script( 'fullcalendar', plugins_url('themefiles/js/fullcalendar.min.js', __FILE__), array( 'jquery', 'moment' ) );
  wp_enqueue_script( 'tsz', plugins_url('themefiles/js/tsz.js', __FILE__), array( 'jquery' ) );
}


/// custom rewrite rules:

add_filter( 'rewrite_rules_array','tsz_listings_rewrite_rules' );
add_action( 'register_activation_hook','tsz_listings_flush_rules' );
add_action( 'wp_loaded','tsz_listings_flush_rules' );
add_filter( 'query_vars','tsz_listings_insert_query_vars' );

function tsz_listings_flush_rules(){
      global $wp_rewrite;
      $wp_rewrite->flush_rules();
      $rules = get_option( 'rewrite_rules' );
}

//http://junebug/edit-listing/20/delete-image/32

function tsz_listings_rewrite_rules( $rules )
{
  $newrules = array();
  
  $newrules['edit-listing/(\d*)/delete-image/(\d*)$'] = 'index.php?pagename=edit-listing&listing-id=$matches[1]&delete-image=$matches[2]';
  $newrules['edit-listing/(\d*)$'] = 'index.php?pagename=edit-listing&listing-id=$matches[1]';
  return $newrules + $rules;
}

function tsz_listings_insert_query_vars( $vars )
{
    array_push($vars, 'listing-id');
    array_push($vars, 'delete-image');
    return $vars;
}






?>
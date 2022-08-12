<?php
/*
Plugin Name: Test Vetorino
Description: Accumulate points with each order, the number of points accumulated corresponds to 5% of the amount of an order rounded down to the lower unit. These points are displayed in a new section of the customer's testvetorino Account part. When the customer reaches at least 50 points, he can click on a button to send himself a reduction coupon by e-mail of a value equal to the number of points he chooses to consume, the coupon can be used only once time.
Author: Jim
Version: 1.0
Text Domain: testvetorino
Domain Path: /lang
*/

require_once plugin_dir_path(__FILE__) . 'includes/vt-functions.php';
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
add_action( 'wp_enqueue_scripts', 'prefix_enqueue' );
function prefix_enqueue() {  
    wp_register_script('prefix_js', plugins_url('/js/public/vt-scripts.js',__FILE__ ), array('jquery'), '', true);
    wp_localize_script('prefix_js','ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' )) );
    wp_enqueue_script('prefix_js');
    wp_register_style('prefix_bootstrap',plugins_url('/css/bootstrap.min.css',__FILE__));
    wp_enqueue_style('prefix_bootstrap');   
}
function testvetorino_init() {
    load_plugin_textdomain( 'testvetorino', false, 'test-vetorino/lang' );
  }
  add_action('init', 'testvetorino_init');

  function testvetorino_settings_link($links) { 
    $settings_link = '<a href="options-general.php?page=test-vetorino-admin">Settings</a>'; 
    array_unshift($links, $settings_link); 
    return $links; 
  }
  $plugin = plugin_basename(__FILE__); 
  add_filter("plugin_action_links_$plugin", 'testvetorino_settings_link' );

  function testvetorino_register_settings() {

    register_setting('testvetorino_options_group', 'sendinblue_mail');

    register_setting('testvetorino_options_group', 'sendinblue_api');
}

add_action('admin_init', 'testvetorino_register_settings');

function testvetorino_setting_page() {

  // add_options_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '' )
   add_options_page('Test vetorino', __('Test Vetorino Settings','testvetorino'), 'manage_options', 'test-vetorino-admin', 'custom_page_html_form');
   // custom_page_html_form is the function in which I have written the HTML for my custom plugin form.
}

add_action('admin_menu', 'testvetorino_setting_page');

function custom_page_html_form() { ?>
    <div class="wrap">
        <h2><?php _e('Test Vetorino Settings','testvetorino');?></h2>
        <form method="post" action="options.php">
            <?php settings_fields('testvetorino_options_group'); ?>

            <table class="form-table">
                <tr>
                    <th><label for="first_field_id"><?php _e('Sendinblue Mail:','testvetorino');?></label></th>
                    <td>
                        <input type = 'text' class="regular-text" id="first_field_id" name="sendinblue_mail" value="<?php echo get_option('sendinblue_mail'); ?>">
                    </td>
                </tr>

                <tr>
                    <th><label for="second_field_id"><?php _e('Sendinblue API key:','testvetorino');?></label></th>
                    <td>
                        <input type = 'text' class="regular-text" id="second_field_id" name="sendinblue_api" value="<?php echo get_option('sendinblue_api'); ?>">
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
    </div>
<?php }




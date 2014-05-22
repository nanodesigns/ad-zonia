<?php
/**
 * Plugin Name: WP AdZonia
 * Plugin URI: http://nanodesignsbd.com
 * Description: An simpler and easier Ad management Plugin for WordPress sites
 * Version: 0.1
 * Author: Mayeenul Islam (@mayeenulislam)
 * Author URI: http://nishachor.com
 * License: GNU General Public License v2.0
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */


/*  Copyright 2014  nanodesigns  (email : info@nanodesignsbd.com)

    This plugin is a free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This plugin is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/



/**
 * SETTING UP SOME CONSTANTS & VARIABLES FOR THE USE OF ALL OVER THE PLUGIN
 *  - Setting the plugin name
 *  - Setting the Plugin textdomain
 */
$plugin_name = 'WP AdZonia';
$plugin_prefix = 'adzonia-';

/**
 * STEP 0: SETUP NECESSARY FILES
 * A CSS file to do styles
 */

add_action( 'admin_enqueue_scripts', 'adzonia_css' );

function adzonia_css() {
    wp_register_style( 'adzonia-style', plugins_url('style.css', __FILE__) );
    wp_register_style( 'datepicker-style', plugins_url('css/jquery.datetimepicker.css', __FILE__) );

    wp_enqueue_style( 'adzonia-style' );
    wp_enqueue_style( 'datepicker-style' );
}

add_action( 'wp_enqueue_scripts', 'adzonia_output_css' );

function adzonia_output_css() {
    wp_register_style( 'adzonia-output-style', plugins_url('css/output.css', __FILE__) );

    wp_enqueue_style( 'adzonia-output-style' );
}


/**
 * STEP I: Setup the Database
 * create table if not exists
 *
 * With assistance:
 * Link: http://cube3x.com/2013/04/how-to-create-database-table-when-wordpress-plugin-is-activated/
 */


/*
 * The db version by which we can update new
 * our table with new versions
 * Current Version: 1.0
 */
global $nano_db_version;
$nano_db_version = "1.0";

function add_the_table(){
    global $wpdb;
    global $nano_db_version;

    $table_name = $wpdb->prefix . "wp_adzonia";

    if( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
        $sql = "CREATE TABLE $table_name (
                  id mediumint(9) NOT NULL AUTO_INCREMENT,
                  ad_type VARCHAR(20) DEFAULT '' NOT NULL,
                  name_of_ad tinytext NOT NULL,
                  ad_code_title tinytext NOT NULL,
                  ad_code text NOT NULL,
                  ad_image_url text NOT NULL,
                  url VARCHAR(100) DEFAULT '' NOT NULL,
                  str_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                  end_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                  ad_status boolean NOT NULL,
                  UNIQUE KEY id (id)
                );";

    //reference to upgrade.php file
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    } //endif($wpdb->get_var

    add_option( "nano_db_version", $nano_db_version );
}

//action hook for plugin activation
register_activation_hook( __FILE__, 'add_the_table' );


/**
 * STEP II: SETUP A DASHBOARD
 * An admin page, from where the ad will be controlled
 */


add_action('admin_menu', 'add_nano_ad_plugin_menu');

function add_nano_ad_plugin_menu() {

    add_object_page(
        'WP AdZonia',               // Page Title
        'WP AdZonia',               // Menu Title
        'read',                     // Capability
        'adzonia',                  // Menu Slug/ID
        'adzonia_page',             // Callback
        ''                          // Icon URL
    );

    add_submenu_page(
        'adzonia',                  // Parent Slug
        'Add New Advertisement',    // Page Title
        'Add New Ad',               // Menu Title
        'read',                     // Capability
        'add-adzonia',              // Menu Slug/ID
        'adzonia_add_ad_subpage'    // Callback
    );

}

// Callback Ad Page
function adzonia_page() {
    include('adzonia-view.php');
} //function adzonia_page()

/*
 * VALIDATION MESSAGES
 */
$success_message = '';
$error_message = '';

// Callback Add Ad Subpage
function adzonia_add_ad_subpage() {
    include('adzonia-insert-edit.php');
} //function adzonia_add_ad_subpage()




/**
 * STEP II: ADD THE NECESSARY JAVASCRIPT FILE
 *
 */

add_action('admin_enqueue_scripts', 'adzonia_admin_scripts');

function adzonia_admin_scripts() {
    $jQueryLatestURI = "http://code.jquery.com/jquery-latest.min.js";
    @$connection = fopen( $jQueryLatestURI, "r" );

    if ( ( isset( $_GET['page'] ) && ( $_GET['page'] === 'add-adzonia' || $_GET['page'] === 'adzonia' ) ) ) {
        wp_enqueue_media();

        /**
         * FALLBACK
         * Check whether the jQuery library from server is available or not
         * If available, load from server,
         * If not, load from the plugin 'js' folder
         */

        if($connection) {

            /* Latest jQuery Library from Server */
            wp_register_script('jQuery-library', $jQueryLatestURI, array('jquery'));
            wp_enqueue_script('jQuery-library');
        } else {

            /* In-Package jQuery Library */
            wp_register_script('jquery-js', plugins_url('/js/jquery.js', __FILE__) );
            wp_enqueue_script('jquery-js');

        } //endif($connection)

        wp_register_script('adzonia-js', plugins_url('/js/adzonia.js', __FILE__) );
        wp_register_script('datepicker-js', plugins_url('/js/jquery.datetimepicker.js', __FILE__) );

        wp_enqueue_script('adzonia-js');
        wp_enqueue_script('datepicker-js');
    }
}


/**
 * STEP III: FUNCTION TO SHOW THE AD
 * to call the function into the tempalte you have to include
 * <?php if (function_exists("show_ad_zonia")){ show_ad_zonia($id); }; ?>
 */

function show_ad_zonia( $id ){

        global $wpdb, $plugin_prefix;
        $table = $wpdb->wp_adzonia = $wpdb->prefix . "wp_adzonia";

        $ad_show_query = $wpdb->get_results(
            "SELECT *
                FROM $table
                WHERE id = $id;
                ");

        $thisDate = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) );
        $datetoday = strtotime( $thisDate );
        $startDateString = ( $ad_show_query[0]->str_time != '' ? strtotime( $ad_show_query[0]->str_time ) : '' );
        $endDateString = ( $ad_show_query[0]->end_time != '' ? strtotime( $ad_show_query[0]->end_time ) : '' );

        $ad_output = '';


        //check the ad is "Active" and "Not Expired"
        if( $ad_show_query[0]->ad_status == '1' && $datetoday >= $startDateString && $datetoday <= $endDateString ){

            if( $ad_show_query[0]->ad_type == 'imagead' ){

                echo '<div id="'. $plugin_prefix . $ad_show_query[0]->id .'" class="'. $plugin_prefix .'holder '. $plugin_prefix . $ad_show_query[0]->ad_type .' '. $plugin_prefix . $ad_show_query[0]->ad_type . '-' . $ad_show_query[0]->id .'">';

                    // Image Ad Output
                    $ad_output = '<a href="'. esc_url( $ad_show_query[0]->url ) .'" target="_blank"><img src="'. esc_url( $ad_show_query[0]->ad_image_url ) .'"/></a>';
                    echo $ad_output;

                echo '</div> <!-- #'. $plugin_prefix . $ad_show_query[0]->id .' Type: '. $ad_show_query[0]->ad_type .' -->';

            } else if ( $ad_show_query[0]->ad_type == 'codead' ) {

                echo '<div id="'. $plugin_prefix . $ad_show_query[0]->id .'" class="'. $plugin_prefix .'holder '. $plugin_prefix . $ad_show_query[0]->ad_type .' '. $plugin_prefix . $ad_show_query[0]->ad_type . '-' . $ad_show_query[0]->id .'">';

                    // Code Ad Output
                    $ad_output = stripslashes($ad_show_query[0]->ad_code);
                    echo $ad_output;

                echo '</div> <!-- #'. $plugin_prefix . $ad_show_query[0]->id .' Type: '. $ad_show_query[0]->ad_type .' -->';

            }

        } //endif( $ad_show_query[0]->ad_status == '1'

        //return $ad_output;

}




/**
 * STEP IV: SHORTCODE
 * Adding shortcode to call the ad inside post, pages, widgets - everywhere
 * [ad-zonia id="#"]
 */

function ad_zonia_shortcode($atts){

    $scOutput = '';

    $args = shortcode_atts(
        array(
            'id' => ''
        ),
        $atts
    );
    $atts = (int) $args['id'];

    if(!empty($atts)){
        ob_start();
        $scOutput = show_ad_zonia( $atts );
        $scOutput = ob_get_clean();
    } else {
        $scOutput = '';
    }

    return $scOutput;
}

add_shortcode('wp-adzonia', 'ad_zonia_shortcode');





/**
 * STEP V: ADDING AD WIDGET
 * Adding a widget to add ad to the widget areas easily
 */

class ad_zonia_widget extends WP_Widget {

    function __construct() {
        parent::__construct(
            'ad_zonia_widget', //base ID of widget
            __('Ad Zonia Widget', 'ad-zonia'), //name of the widget
            array( 'description' => __( 'Ad Zonia Widget to call the advertisement easily.', 'ad-zonia' ) )
        );
    }

    // Widget Backend
    public function form( $instance ) {

        if ( isset( $instance[ 'title' ] ) ) {
            $title = $instance[ 'title' ];
        }
        else {
            $title = '';
        }

        if ( isset( $instance[ 'ad_id' ] ) ) {
            $ad_id = $instance[ 'ad_id' ];
        } else {
            $ad_id = '';
        }

        // Widget admin form
        global $wpdb, $plugin_prefix;
        $table = $wpdb->wp_adzonia = $wpdb->prefix . "wp_adzonia";

        $widget_ad_query = $wpdb->get_results(
            "SELECT *
            FROM $table;
            ");

        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'ad_id' ); ?>"><?php _e( 'Advertisements:' ); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id( 'ad_id' ); ?>" name="<?php echo $this->get_field_name( 'ad_id' ); ?>">
                <option value="">Choose one...</option>
                <?php
                foreach( $widget_ad_query as $activead ) { ?>
                    <option value="<?php echo $activead->id; ?>" <?php
                    if( isset($ad_id) && $ad_id == $activead->id )
                        echo 'selected="selected"'; ?>>
                        <?php
                        echo $activead->id . '&nbsp;&mdash;&nbsp;' . ( !empty($activead->name_of_ad) ? $activead->name_of_ad : $activead->ad_type);
                    echo '</option>';
                }
                ?>
            </select>
        </p>
    <?php
    }

    // Updating widget replacing old instances with new
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['ad_id'] = ( ! empty( $new_instance['ad_id'] ) ) ? $new_instance['ad_id'] : '';
        return $instance;
    }

    //Creating Widget Front End
    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance['title'] );
        echo $args['before_widget'];

        if ( ! empty( $title ) )
            echo $args['before_title'] . $title . $args['after_title']; //before & after widget args are defined by themes
        // This is where you run the code and display the output
        if ( ! empty( $ad_id ) )
            $instance['ad_id'];
            show_ad_zonia( $instance['ad_id'] );

        echo $args['after_widget'];
    }

}

// Register and load the widget
function nano_load_widget() {
    register_widget( 'ad_zonia_widget' );
}

add_action( 'widgets_init', 'nano_load_widget' );
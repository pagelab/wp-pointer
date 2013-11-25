<?php
/**
 * This file holds the Axis_Pointer class which will setup Necessary Pointers
 *
 * @package     Axis Themes
 * @subpackage  Axis Framework
 * @author      Shiva Poudel <info.shivapoudel@gmail.com>
 * @copyright   Copyright (c) Axis Themes
 * @link        http://axisthemes.com
 * @link        https://github.com/AxisThemes/wp-pointer
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @since       Version 1.0
 */
 
/**
 * This Class is a part of AxisFramework by AxisThemes.com
 * Note: Please Configure below configuration function and you are all done..
 */
 
/**
 * AXIS Standard Pointers Configuration
 * 
 * @param array $axis_pointers Data array that is passed upon creation of the superobject.
 *
 * @access  public
 * @since   Version 1.0
 * @return  void
 */
function axis_theme_pointers() {
   //First we define our pointers 
   $axis_pointers = array(
        array(
            'id' => "axis_theme_activate",   // unique id for this pointer
            'screen' => "themes", // this is the page hook we want our pointer to show on
            'target' => "#wpadminbar", // the css selector for the pointer to be tied to, best to use ID's
            'title' => __( 'Congratulations !', 'textdomain' ), // Title of Pointer
            'content' => sprintf( __( 'You\'ve just installed the %1$s by AxisThemes! Click "Customize" to tweak this theme\'s settings and see a preview of those changes in real time.', 'textdomain' ), 'Pointer' ), // Content of Pointer
            'position' => array(
                'edge' => 'top', // top, bottom, left, right
                'align' => 'middle' // top, bottom, left, right, middle
            ),
            'button_text'        => __( 'Customize', 'textdomain' ), // Button Text
            'button_link'        => admin_url( 'customize.php' ), // Button URL Link
            'dismiss_text'       => __( 'Dismiss', 'textdomain' ) // Button Dismiss Text
        ),
    );

    /**
     * ---------------------------------------------------------
     * Filter the $axis_pointers data array that is 
     * passed upon creation of the $AxisPointer object
     * ---------------------------------------------------------
     */
    $axis_pointers = apply_filters( 'axis_filter_pointers_data', $axis_pointers );  

    /**
     * ---------------------------------------------------------
     * Create new $AxisPointer object and instantiate the class.
     * Also passed $axis_pointers data array to the constructor
     * ---------------------------------------------------------
     */
    $AxisPointer = new Axis_Pointer( $axis_pointers );
}

add_action('admin_enqueue_scripts', 'axis_theme_pointers');

if ( ! class_exists( 'Axis_Pointer' ) ) {
    /**
     * AXIS Pointers Class
     *
     * This class is the Main Axis 'Pointer' Class.
     * This Class is responsible for loading and controlling Axis Pointers.
     */
    class Axis_Pointer {

        /**
         * Holds Basic Information about Screen ID
         * @var obj
         */
        public $screen_id;

        /**
         * Holds basic Information about valid pointers
         * @var array
         */
        public $valid;

        /**
         * Holds basic Information about Axis pointers
         * @var array
         */
        public $pointers;

        /**
         * Class Constructor Method
         * 
         * This constructor sets up $axis_pointers and $pntrs data array.
         * Also it sets up action and filter hooks into WordPress.
         * 
         * For more information on hooks, actions, and filters,
         * @link http://codex.wordpress.org/Plugin_API
         *
         * @param   array   $pntrs    Data array for Axis Pointers
         *
         * @access  public
         * @since   Version 1.0
         * @return  void
         */
        public function __construct( $pntrs = array() ) {

            /**
             * Don't run on WP < 3.3
             */
            if ( get_bloginfo( 'version' ) < '3.3' || ! function_exists( 'get_current_screen' ) ) return;

            /**
             * Get the Current Screen ID
             * @return obj Returns a WP_Screen object, or null if not applicable.
             */
            $screen = get_current_screen();
            $this->screen_id = $screen->id;

            /**
             * Call the function register_axis_pointers() To Register Axis Pointers
             * @see register_axis_pointers()
             */
            $this->register_axis_pointers( $pntrs );

            /**
             * Action Hooks to Enqueue and add Scripts in admin head respectively
             * @see add_axis_pointers()
             * @see admin_head()
             */
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_axis_pointers' ), 1000 );
            add_action( 'admin_head', array( $this, 'add_axis_scripts' ) );
        }

        /**
         * Register Axis Pointers
         * @param  array $pntrs Data Array of Axis Pointers
         * @return void
         */
        public function register_axis_pointers( $pntrs ) {

            /**
             * Loop through $pntrs to register value in $pointers
             */
            foreach ( $pntrs as $ptr ) {

                if ( $ptr['screen'] == $this->screen_id ) {
                    
                    $pointers[$ptr['id']] = array(
                        'screen'            => $ptr['screen'],
                        'target'            => $ptr['target'],
                        'options'           => array(
                            'content'       => sprintf( '<h3> %s </h3> <p> %s </p>', $ptr['title'], $ptr['content'] ),
                            'position'      => $ptr['position'],
                        ),
                        'button_text'       => $ptr['button_text'],
                        'button_link'       => $ptr['button_link'],
                        'dismiss_text'      => $ptr['dismiss_text'],
                    );
                }
            }

            if ( ! empty( $pointers ) ) 
                $this->pointers = $pointers;
        }

        /**
         * Enqueue Axis Pointers Styles and Scripts conditionally
         * Note: Uses 'admin_enqueue_scripts' action hook
         */
        public function enqueue_axis_pointers() {

            $pointers = $this->pointers;

            /**
             * Not defined Pointers or if defined and isn't array? Stop here.
             */
            if ( ! $pointers || ! is_array( $pointers ) ) return;

            /**
             * Get Dismissed Pointers
             */
            $dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
            
            /**
             * Store and set as array
             * @var array
             */
            $valid_pointers = array();

            /**
             * Loop through to Check Pointers and Remove Dismissed Ones
             */
            foreach ( $pointers as $pointer_id => $pointer ) {

                /**
                 * Make sure we have pointers & check if they have been dismissed
                 */
                if ( in_array( $pointer_id, $dismissed ) || empty( $pointer )  || empty( $pointer_id ) || empty( $pointer['target'] ) || empty( $pointer['options'] ) ) continue;

                $pointer['pointer_id'] = $pointer_id;

                /**
                 * Add the Axis pointer to $valid_pointers array
                 */
                $valid_pointers['pointers'][] =  $pointer;
            }

            /**
             * No Valid Pointers? Stop here.
             */
            if ( empty( $valid_pointers ) ) return;

            $this->valid = $valid_pointers;

            /**
             * Enqueue Pointer Style and Script
             */
            wp_enqueue_style( 'wp-pointer' );
            wp_enqueue_script( 'wp-pointer' );
        }

        /**
         * Add/Print the Axis Script in Admin Head
         * Note: Uses 'admin_head' action hook
         */
        public function add_axis_scripts() {

            $pointers = $this->valid;

            if ( empty( $pointers ) ) return;

            $pointers = json_encode( $pointers );

            echo <<<HTML
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready( function($) {
    var axis_pointer_options = {$pointers}, setup;

    $.each(axis_pointer_options.pointers, function(i) {
        axis_pointer_open(i);
    });

    function axis_pointer_open(i) {
        pointer = axis_pointer_options.pointers[i];
        options = $.extend( pointer.options, {
            buttons: function (event, t) {
                button = jQuery('<a id="pointer-close" style="margin-left:5px" class="button-secondary">' + pointer.dismiss_text + '</a>');
                button.bind('click.pointer', function () {
                    t.element.pointer('close');
                });
                return button;
            },
            close: function() {
                $.post( ajaxurl, {
                    pointer: pointer.pointer_id,
                    action: 'dismiss-wp-pointer'
                });
            }
        });
        $(pointer.target).pointer( options ).pointer('open');
    }

    setup = function() {
        if ('pointer.button') {
            // Store Button Value in variables
            var axis_pointer_button_text = pointer.button_text,
                axis_pointer_button_link = pointer.button_link;

            // Set Button Text and URL link if button_text is not null
            if ( axis_pointer_button_text != '' ) {

                // Set Button Text
                jQuery('#pointer-close').after('<a id="pointer-primary" class="button-primary">' + axis_pointer_button_text + '</a>');
                
                // Set Button URL Link
                jQuery('#pointer-primary').click(function () {
                    if ( axis_pointer_button_link != '') {
                        // Redirect when button is clicked
                        window.location = axis_pointer_button_link;
                    }
                    return false;
                });                
            };
        };
    }

    if (axis_pointer_options.position && axis_pointer_options.position.defer_loading)
        $(window).bind('load.wp-pointers', setup);
    else
        $(document).ready(setup);
});
//]]>
</script>
HTML;
        }
    }
}

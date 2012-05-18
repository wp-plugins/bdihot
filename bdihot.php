<?php
/**
 * Plugin Name: Random Joke (Bdihot.co.il)
 * Plugin URI: http://www.bdihot.co.il/wordpress_plugin/
 * Description: This plugin allows you to add a random joke (from bdihot.co.il) using wordpress Sidebar-Widget and Dashboard-Widget systems.
 * Version: 0.2
 * Author: Rami Y
 * Author URI: http://www.bdihot.co.il/
 * License: GPL2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
**/


// Uninstall hook
register_uninstall_hook( __FILE__ , 'bdihot_uninstall' );

// Load translations
add_action( 'init', 'bdihot_translations' );

// Register dashboard widget
add_action( 'wp_dashboard_setup', 'bdihot_add_dashboard_widget' );

// Register sidebar widget
add_action( 'widgets_init', 'bdihot_add_sidebar_widget' );



/*
 * General
 */


 
// Uninstall bdihot
function bdihot_uninstall() {
	delete_option('bdihot_random_joke');
}

// Load translation POT files
function bdihot_translations() {
	load_plugin_textdomain( 'bdihot', null, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

// Set/Get plugin options
function bdihot_options() {
	$defaults = array(
		'title' => __( 'Random Joke', 'Bdihot' ),
		'jokes_number' => 1,
	);
	if ( ( !$options = get_option( 'bdihot_random_joke' ) ) || !is_array( $options ) )
		$options = array();
	return array_merge( $defaults, $options );
}

// Cache feed for 2 seconds
function bdihot_feed_cache_lifetime() {
	return 2;
}



/*
 * Dashboard Widget
 */



// Register dashboard widget
function bdihot_add_dashboard_widget() {
	wp_add_dashboard_widget( 'bdihot_dashboard_random_joke', __( 'Random Joke', 'Bdihot' ), 'bdihot_widget_output' );
}


// The content of the widget (random joke)
function bdihot_widget_output() {
	include_once( ABSPATH . WPINC . '/feed.php' );

	$widget_options = bdihot_options();
	$url = 'http://www.bdihot.co.il/webmasters-rss/';

	add_filter( 'wp_feed_cache_transient_lifetime' , 'bdihot_feed_cache_lifetime', $url );
	$rss = fetch_feed( $url );
	remove_filter( 'wp_feed_cache_transient_lifetime' , 'bdihot_feed_cache_lifetime', $url );

	if ( ! is_wp_error( $rss ) ) :
		$max_items = $rss->get_item_quantity( $widget_options['jokes_number'] );	// Total items, default one.
		$rss_items = $rss->get_items( 0, $max_items );								// Build an array, starting with 0.
	endif;

	if ( $max_items == 0 ) {
		echo __('No Jokes found.', 'Bdihot');
    } else {
		foreach ( $rss_items as $item ) : 
			echo '<p><a href="' . esc_url( $item->get_permalink() ) . '" title="' . $item->get_date( 'j F Y | g:i a' ) .'" target="_blank">' . esc_html( $item->get_title() ) . '</a></p>';
			echo wpautop( $item->get_description() );
		endforeach;
	}
}



/*
 * Sidebar Widget
 */



// Register sidebar widget
function bdihot_add_sidebar_widget() {
	register_widget( 'Bdihot_Widget' );
}

class Bdihot_Widget extends WP_Widget {

	// Register widget
	public function __construct() {
		parent::__construct(
	 		'bdihot_widget',
			__( 'Random Joke', 'Bdihot' ),
			array( 'description' => __( 'Displays Random Joke from Bdihot.co.il', 'Bdihot' ), )
		);
	}

	// Widget front-end display
	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		echo $before_widget;
		if ( ! empty( $title ) ) echo $before_title . $title . $after_title;
		bdihot_widget_output();
		echo $after_widget;
	}

	// Sanitize widget form values as they are saved
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}

	// Widget back-end form
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'Random Joke', 'Bdihot' );
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'Bdihot' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php
	}
}

?>
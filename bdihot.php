<?php
/**
 * Plugin Name: Random Joke (Bdihot.co.il)
 * Plugin URI: http://www.bdihot.co.il/wordpress_plugin/
 * Description: This plugin allows you to add a random joke (from bdihot.co.il) using wordpress Sidebar-Widget and Dashboard-Widget systems.
 * Version: 0.3
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
	delete_option( 'bdihot_random_joke' );
}

// Load translation POT files
function bdihot_translations() {
	load_plugin_textdomain( 'bdihot', null, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

// Set/Get plugin options
function bdihot_options() {
	$defaults = array(
		'title' => __( 'Random Joke', bdihot ),
		'jokes_number' => 1,
		'jokes_title' => 'false',
		'jokes_icon' => 'false',
		'jokes_poweredby' => 'false',
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
	wp_add_dashboard_widget( 'bdihot_dashboard_random_joke', __( 'Random Joke', bdihot ), 'bdihot_widget_output' );
}


// The content of the widget (random joke)
function bdihot_widget_output( $show_title = false, $show_icon = false ) {
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
		echo __('No Jokes found.', bdihot );
    } else {
		foreach ( $rss_items as $item ) :
			/* if ( $show_icon == true ) echo ''; */
			if ( $show_title == true ) echo '<p class="joke_title"><a href="' . esc_url( $item->get_permalink() ) . '" target="_blank">' . esc_html( $item->get_title() ) . '</a></p>';
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
			__( 'Random Joke', bdihot ),
			array( 'description' => __( 'Display Random Jokes from Bdihot.co.il', bdihot ), )
		);
	}

	// Widget front-end display
	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$jokes_title = empty( $instance['jokes_title'] ) ? '' : apply_filters( 'jokes_title', $instance['jokes_title'] );
		$jokes_icon = empty( $instance['jokes_icon'] ) ? '' : apply_filters( 'jokes_icon', $instance['jokes_icon'] );
		$jokes_poweredby = empty( $instance['jokes_poweredby'] ) ? '' : apply_filters( 'jokes_poweredby', $instance['jokes_poweredby'] );
		echo $before_widget;
		// title
		if ( ! empty( $title ) ) echo $before_title . $title . $after_title;
		// content
		bdihot_widget_output( $jokes_title, $jokes_icon );
		// 
		if ( $jokes_poweredby == true ) {
			echo '<p class="joke_poweredby">';
			_e( 'Powered by <a href="http://www.bdihot.co.il/">Bdihot.co.il</a>', bdihot );
			echo '</p>';
		}
		echo '<!--// Joke by Bdihot.co.il //-->';
		echo $after_widget;
	}

	// Sanitize widget form values as they are saved
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['jokes_title'] = strip_tags( $new_instance['jokes_title'] );
		$instance['jokes_icon'] = strip_tags( $new_instance['jokes_icon'] );
		$instance['jokes_poweredby'] = strip_tags( $new_instance['jokes_poweredby'] );
		return $instance;
	}

	// Widget back-end form
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'Random Joke', bdihot );
		}
		$jokes_title = strip_tags(stripslashes($new_instance['jokes_title']));
		$jokes_icon = strip_tags(stripslashes($new_instance['jokes_icon']));
		$jokes_poweredby = strip_tags(stripslashes($new_instance['jokes_poweredby']));
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', bdihot ); ?></label>
		<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<input type="checkbox" id="<?php echo $this->get_field_id( 'jokes_title' ); ?>" name="<?php echo $this->get_field_name( 'jokes_title' ); ?>" <?php if ( $instance['jokes_title'] ) echo 'checked="checked" '; ?>/>
		<label for="<?php echo $this->get_field_id( 'jokes_title' ); ?>"><?php _e( 'Joke title', bdihot ); ?></label>
		</p>
		<!--
		<p>
		<input type="checkbox" id="<?php echo $this->get_field_id( 'jokes_icon' ); ?>" name="<?php echo $this->get_field_name( 'jokes_icon' ); ?>" <?php if ( $instance['jokes_icon'] ) echo 'checked="checked" '; ?>/>
		<label for="<?php echo $this->get_field_id( 'jokes_icon' ); ?>"><?php _e( 'Joke icon', bdihot ); ?></label>
		</p>
		-->
		<p>
		<input type="checkbox" id="<?php echo $this->get_field_id( 'jokes_poweredby' ); ?>" name="<?php echo $this->get_field_name( 'jokes_poweredby' ); ?>" <?php if ( $instance['jokes_poweredby'] ) echo 'checked="checked" '; ?>/>
		<label for="<?php echo $this->get_field_id( 'jokes_poweredby' ); ?>"><?php _e( 'Powered by <a href="http://www.bdihot.co.il/">Bdihot.co.il</a>', bdihot ); ?></label>
		</p>
		<?php
	}
}

?>
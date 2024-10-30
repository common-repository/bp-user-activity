<?php
/**
 * Plugin Name: BuddyPress User Activity
 * Description: Activity stream can be embedded using widget and shortcode. It will list the activity of the logged-in user. 
 * Author: Jomol MJ
 * Plugin URI: https://wordpress.org/plugins/bp-user-activity/
 * Author URI: https://codingdom.wordpress.com/
 * Version: 1.0.1
 * License: GPL
 */

// exit if access directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BP_User_Activity {

	public function __construct(){
        $this->bpu_action_method();
	}

	public function bpu_action_method(){
		add_shortcode( 'user-activity-stream', array( $this, 'bpu_activity_stream_shortcode' ));
	    add_action( 'wp_enqueue_scripts', array($this,'bpu_enquee_styles' ));
	}

	public function bpu_enquee_styles(){		
		wp_register_style( 'activity-css',plugin_dir_url( __FILE__ ) . 'css/acitivity.css', false, '4.8.0' );
        wp_enqueue_style( 'activity-css' );
	}

	public function bpu_activity_stream_shortcode(){
		$data = $this->bpu_activity_stream();
		echo $data;
	}

	public function bpu_activity_stream(){
	    if ( is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				
				$atts =  array(
						'title'            => 'Recent Activity',//title of the section
						'pagination'       => 1,//show or not
						'load_more'        => 0,
						'display_comments' => 'stream',
						'include'          => false,     // pass an activity_id or string of IDs comma-separated
						'exclude'          => false,     // pass an activity_id or string of IDs comma-separated
						'in'               => false,     // comma-separated list or array of activity IDs among which to search
						'sort'             => 'DESC',    // sort DESC or ASC
						'page'             => 1,         // which page to load
						'per_page'         => 5,         //how many per page
						'max'              => false,     // max number to return
						'count_total'      => true,

						// Scope - pre-built activity filters for a user (friends/groups/favorites/mentions)
						'scope'            => false,

						// Filtering
						'user_id'          => $current_user->ID,    // user_id to filter on
						'object'           => false,    // object to filter on e.g. groups, profile, status, friends
						'action'           => false,    // action to filter on e.g. activity_update, new_forum_post, profile_updated
						'primary_id'       => false,    // object ID to filter on e.g. a group_id or forum_id or blog_id etc.
						'secondary_id'     => false,    // secondary object ID to filter on e.g. a post_id

						// Searching
						'search_terms'     => false,         // specify terms to search on
						'use_compat'       => bp_use_theme_compat_with_current_theme(),
						'allow_posting'    => false,    //experimental, some of the themes may not support it.
						'container_class'  => 'activity',//default container,
						'hide_on_activity' => 1,//hide on user and group activity pages
				);

		            //hide on user activity, activity directory and group activity
				if ( $atts['hide_on_activity'] && ( function_exists( 'bp_is_activity_component' ) && bp_is_activity_component() ||
				       function_exists( 'bp_is_group_home' ) && bp_is_group_home() ) ) {
					return '';
				}

				//start buffering
				ob_start();
				?>

				<?php if ( $atts['use_compat'] ) : ?>
				<div id="buddypress" class="buddypress-user-activity">
				<?php endif; ?>

				<?php if ( $atts['title'] ) : ?>
					<h3 class="activity-title"><?php echo $atts['title']; ?></h3>
				<?php endif; ?>

				<?php do_action( 'bp_before_activity_loop' ); ?>			

				<?php if ( bp_has_activities( $atts ) ) : ?>

					<div class="<?php echo esc_attr( $atts['container_class'] ); ?> <?php if ( ! $atts['display_comments'] ) : ?> hide-activity-comments<?php endif; ?> shortcode-activity-stream">

						<?php if ( empty( $_POST['page'] ) ) : ?>
							<ul id="activity-stream" class="activity-list item-list">
						<?php endif; ?>

							<?php while ( bp_activities() ) : bp_the_activity(); ?>

		                            <?php do_action( 'bp_before_activity_entry' ); ?>

										<li class="<?php bp_activity_css_class(); ?>" id="activity-<?php bp_activity_id(); ?>">
											<div class="activity-avatar">
												<a href="<?php bp_activity_user_link(); ?>">

													<?php bp_activity_avatar(); ?>

												</a>
											</div>

											<div class="activity-content">
												<div class="activity-header">
													<p><?php 
	                                                    global $activities_template;                                                
												    
					                                    $activity = $activities_template->activity->action;
					                                    if (strlen($activity) > 160){
															$activity = substr($activity, 0, strpos($activity, ' ', 140));																							
					                                        echo $activity . '...';
					                                    }else{
					                                        echo $activity;
					                                    }         
	                                                ?>
	                                                <br> <a href="<?php echo bp_activity_get_permalink( bp_get_activity_id() ); ?>" class="view activity-time-since" title="View Discussion"><span class="time-since"><?php echo bp_core_time_since(bp_get_activity_date_recorded()); ?></span></a>
	                                                </p>
												</div>											
												<?php do_action( 'bp_activity_entry_content' ); ?>								
											</div>							

										</li>

										<?php do_action( 'bp_after_activity_entry' ); ?>

								<?php endwhile; ?>								

							<?php if ( empty( $_POST['page'] ) ) : ?>
							</ul>
							<?php endif; ?>						

					</div>

					<?php else : ?>
						<div id="message" class="info">
							<p><?php _e( 'Sorry, there was no activity found.', 'buddypress' ); ?></p>
						</div>
					<?php endif; ?>

					<?php do_action( 'bp_after_activity_loop' ); ?>

					<form action="" name="activity-loop-form" id="activity-loop-form" method="post">
						<?php wp_nonce_field( 'activity_filter', '_wpnonce_activity_filter' ); ?>
					</form>

			<?php if ( $atts['use_compat'] ) : ?>
				</div>
			<?php endif; 
			$output = ob_get_clean();

		    return $output;
	    }

    }

}
/* End BP_User_Activity class here */

$bp_user_activity = new BP_User_Activity();


/* User activity widget starts*/

// Register and load the widget
function bpu_load_widget() {
	register_widget( 'bpu_widget' );
}
add_action( 'widgets_init', 'bpu_load_widget' );

/* Creating the widget  */
class bpu_widget extends WP_Widget {

	function __construct() {
		parent::__construct(

		'bpu_widget', 

		__('(BuddyPress) User Activity', 'bp-user-activity'), 

		array( 'description' => __( 'A list of recent 5 activity of the loggined user across your network.', 'bpu_widget_domain' ), ) 
		);
	}


	public function callUserActivity(){
       $user_activity = new BP_User_Activity();
       $data = $user_activity->bpu_activity_stream();
       echo $data;    
    }

	// Creating widget front-end
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $args['before_widget'];
		if ( ! empty( $title ) )
		echo $args['before_title'] . $title . $args['after_title'];

	    $this->callUserActivity();

		echo $args['after_widget'];
	}
			
	// Widget Backend 
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
		$title = $instance[ 'title' ];
		}
		else {
		$title = __( 'Recent Activity', 'bp-user-activity' );
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
	<?php 
	}
		
	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
} 
// Class bpu_widget ends here

/* User activity widget ends*/
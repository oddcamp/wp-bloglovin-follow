<?php
/*
Plugin Name: Bloglovin Follow
Plugin URI: http://wordpress.org/extend/plugins/bloglovin-follow/
Description: Add a Bloglovin Follow button to posts or display it in a widget.
Version: 1.0
Author: Per Sandström
Author URI: http://www.kollegorna.se
License: GPL3

Credits: Lots of code and inspiration came from the Facebook Likes You!
plugin by Piotr Sochalewski.
http://wordpress.org/extend/plugins/facebook-likes-you/

Sponsored by: Rodeo Magazine (http://rodeo.net) and Kollegorna (http://www.kollegorna.se)
Source code and development: http://github.com/kollegorna/wp-bloglovin-follow
*/

$bloglovin_follow_settings = array();

function bloglovin_register_follow_settings() {
	register_setting( 'bloglovin_follow', 'bloglovin_follow_button_code' );
	register_setting( 'bloglovin_follow', 'bloglovin_follow_show_at_top' );
	register_setting( 'bloglovin_follow', 'bloglovin_follow_show_at_bottom' );
	register_setting( 'bloglovin_follow', 'bloglovin_follow_show_on_page' );
	register_setting( 'bloglovin_follow', 'bloglovin_follow_show_on_post' );
	register_setting( 'bloglovin_follow', 'bloglovin_follow_show_on_home' );
	register_setting( 'bloglovin_follow', 'bloglovin_follow_show_on_search' );
	register_setting( 'bloglovin_follow', 'bloglovin_follow_show_on_archive' );
	register_setting( 'bloglovin_follow', 'bloglovin_follow_margin_top' );
	register_setting( 'bloglovin_follow', 'bloglovin_follow_margin_bottom' );
	register_setting( 'bloglovin_follow', 'bloglovin_follow_margin_left' );
	register_setting( 'bloglovin_follow', 'bloglovin_follow_margin_right' );
	register_setting( 'bloglovin_follow', 'bloglovin_follow_excl_post' );
	register_setting( 'bloglovin_follow', 'bloglovin_follow_excl_cat' );
	register_setting( 'bloglovin_follow', 'bloglovin_follow_css_style' );
	register_setting( 'bloglovin_follow', 'bloglovin_follow_css_class' );

	// Loop custom post types
	$post_types = get_post_types( array( 'public' => true, '_builtin' => false ), 'objects', 'and' ); 
	foreach ( $post_types as $post_type ) {
		register_setting( 'bloglovin_follow', 'bloglovin_follow_show_on_post_type_'.$post_type->name );
	}
}

function bloglovin_follow_init() {
  global $bloglovin_follow_settings;

	if ( is_admin() ) {
		add_action( 'admin_init', 'bloglovin_register_follow_settings' );
		wp_enqueue_style( 'bloglovin-follow-admin', WP_PLUGIN_URL.'/'.str_replace( basename( __FILE__) , "", plugin_basename(__FILE__) ).'/bloglovin-follow-admin.css', array(), false, 'screen' );	
	}

	add_filter( 'the_content', 'bloglovin_follow_button' );
	add_filter( 'the_excerpt', 'bloglovin_follow_button' );
	add_filter( 'admin_menu', 'bloglovin_follow_admin_menu' );
	add_filter( 'widget_text', 'do_shortcode' );
	add_action( 'widgets_init', create_function( '', 'return register_widget("BloglovinFollowWidget");' ) );
	
	add_option( 'bloglovin_follow_button_code', '' );
	add_option( 'bloglovin_follow_show_at_top', 'false' );
	add_option( 'bloglovin_follow_show_at_bottom', 'true' );
	add_option( 'bloglovin_follow_show_on_page', 'false' );
	add_option( 'bloglovin_follow_show_on_post', 'true' );
	add_option( 'bloglovin_follow_show_on_home', 'false' );
	add_option( 'bloglovin_follow_show_on_search', 'false' );
	add_option( 'bloglovin_follow_show_on_archive', 'false' );
	add_option( 'bloglovin_follow_margin_top', '0' );
	add_option( 'bloglovin_follow_margin_bottom', '0' );
	add_option( 'bloglovin_follow_margin_left', '0' );
	add_option( 'bloglovin_follow_margin_right', '0' );
	add_option( 'bloglovin_follow_excl_post', '' );	
	add_option( 'bloglovin_follow_excl_cat', '' );	
	add_option( 'bloglovin_follow_css_style', '' );
	add_option( 'bloglovin_follow_css_class', '' );

	$bloglovin_follow_settings['button_code'] = get_option( 'bloglovin_follow_button_code' );
	$bloglovin_follow_settings['showattop'] = get_option( 'bloglovin_follow_show_at_top' ) === 'true';
	$bloglovin_follow_settings['showatbottom'] = get_option( 'bloglovin_follow_show_at_bottom' ) === 'true';
	$bloglovin_follow_settings['showonpage'] = get_option( 'bloglovin_follow_show_on_page' ) === 'true';
	$bloglovin_follow_settings['showonpost'] = get_option( 'bloglovin_follow_show_on_post' ) === 'true';
	$bloglovin_follow_settings['showonhome'] = get_option( 'bloglovin_follow_show_on_home' ) === 'true';
	$bloglovin_follow_settings['showonsearch'] = get_option( 'bloglovin_follow_show_on_search' ) === 'true';
	$bloglovin_follow_settings['showonarchive'] = get_option( 'bloglovin_follow_show_on_archive' ) === 'true';
	$bloglovin_follow_settings['margin_top'] = get_option( 'bloglovin_follow_margin_top' );
	$bloglovin_follow_settings['margin_bottom'] = get_option( 'bloglovin_follow_margin_bottom' );
	$bloglovin_follow_settings['margin_left'] = get_option( 'bloglovin_follow_margin_left' );
	$bloglovin_follow_settings['margin_right'] = get_option( 'bloglovin_follow_margin_right' );
	$bloglovin_follow_settings['excl_post'] = get_option( 'bloglovin_follow_excl_post' );
	$bloglovin_follow_settings['excl_cat'] = get_option( 'bloglovin_follow_excl_cat' );
	$bloglovin_follow_settings['css_style'] = get_option( 'bloglovin_follow_css_style' );
	$bloglovin_follow_settings['css_class'] = get_option( 'bloglovin_follow_css_class' );
	
	$locale = defined(WPLANG) ? WPLANG : 'en_US';

	// Shortcode [bloglovin-follow-button] linked to bloglovin_follow_generate_button()
	add_shortcode( 'bloglovin-follow-button', 'bloglovin_follow_generate_button' );

  load_plugin_textdomain( 'bloglovin_follow_trans_domain', '', plugin_basename( dirname( __FILE__ ) . '/languages' ) );
}

function bloglovin_follow_pluginPath( $file ) {
	return WP_PLUGIN_URL . '/' . str_replace( basename( __FILE__) , "" , plugin_basename(__FILE__) ) . $file;
}

// URL Validation (for incomplete/relative address in Custom Field)
function bloglovin_follow_isValidURL( $url ) {
	$urlregex = "^(https?|ftp)\:\/\/([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)*(\:[0-9]{2,5})?(\/([a-z0-9+\$_-]\.?)+)*\/?(\?[a-z+&\$_.-][a-z0-9;:@/&%=+\$_.-]*)?(#[a-z_.-][a-z0-9+\$_.-]*)?\$";
	return eregi( $urlregex, $url );
}

// Show the button
function bloglovin_follow_button( $content ) {
  global $bloglovin_follow_settings;

  if ( is_feed() ) return $content;

  if ( is_front_page() && !$bloglovin_follow_settings['showonhome'] )
		return $content;

  if ( is_search() && !$bloglovin_follow_settings['showonsearch'] )
		return $content;

  if ( is_archive() && !$bloglovin_follow_settings['showonarchive'] )
		return $content;
	
	// Exclude posts and pages
	if ( trim( $bloglovin_follow_settings['excl_post'] ) != '' ) {
		$excl_post_array = explode(",", $bloglovin_follow_settings['excl_post']);
		for ( $i = 0; $i < count( $excl_post_array ); $i++ ) {
			$excl_post_array[$i] = trim( $excl_post_array[$i] );
			if( is_single( $excl_post_array[$i] ) == true or is_page( $excl_post_array[$i] ) == true )
				return $content;
		}	
	}
	
	// Exclude categories
	if ( trim( $bloglovin_follow_settings['excl_cat'] ) != '' ) {	
		$excl_cat_array = explode( ",", $bloglovin_follow_settings['excl_cat'] );	
		for ( $i = 0; $i < count( $excl_cat_array ); $i++ ) {
			$excl_cat_array[$i] = trim( $excl_cat_array[$i] );
			if ( in_category( $excl_cat_array[$i] ) == true )
				return $content;
		}
	}

	if ( is_single() || is_page() ) {
		$bloglovin_follow = FALSE;

		$current_post_type = get_post_type( get_the_ID() );
		if ( $bloglovin_follow_settings['showonpost'] && $current_post_type == 'post' ) {
			$bloglovin_follow = TRUE;
		} elseif ( $bloglovin_follow_settings['showonpage'] && $current_post_type == 'page' ) {
			$bloglovin_follow = TRUE;
		} else {
			// Loop custom post types
			$post_types = get_post_types( array( 'public' => true, '_builtin' => false ), 'objects', 'and' ); 
			foreach ( $post_types as $post_type ) {
				// We must load these options here - post types can't be loaded in bloglovin_follow_init()
				add_option( 'bloglovin_follow_show_on_post_type_'.$post_type->name, 'true' );
				$bloglovin_follow_settings['showonposttype'.$post_type->name] = get_option( 'bloglovin_follow_show_on_post_type_'.$post_type->name ) === 'true';

				if ($bloglovin_follow_settings['showonposttype'.$post_type->name] && $current_post_type == $post_type->name ) {
					$bloglovin_follow = TRUE;
					break;
				}
			}
		}
	} else {
		$bloglovin_follow = TRUE;
	}

	if ( !$bloglovin_follow )
		return $content;
 
  // Show the button where user wants to
  if ( $bloglovin_follow_settings['showattop'] == 'true' )
		$content = bloglovin_follow_generate_button() . $content;

  if ( $bloglovin_follow_settings['showatbottom'] == 'true' )
	  $content .= bloglovin_follow_generate_button();
	    
	return $content;
}

function bloglovin_follow_count_margin() {
	global $bloglovin_follow_settings;

	return $bloglovin_follow_settings['margin_top'] . 'px '
		. $bloglovin_follow_settings['margin_right'] . 'px ' 
		. $bloglovin_follow_settings['margin_bottom'] . 'px '
		. $bloglovin_follow_settings['margin_left'] . 'px';
}

// Return button's body (to bloglovin_follow_button() and shortcode [bloglovin-follow-button])
function bloglovin_follow_generate_button() {
	global $bloglovin_follow_settings;

	if ( empty( $bloglovin_follow_settings['button_code'] ) )
		return;

	$margin = bloglovin_follow_count_margin();

	$button_code = $bloglovin_follow_settings['button_code'];
	$button_code = '<div class="bloglovin-follow '.$bloglovin_follow_settings['css_class'].'" style="margin: ' . $margin . ';' . ( ( $bloglovin_follow_settings['css_style'] != '' ) ? ' ' . $bloglovin_follow_settings['css_style'] : '' ) . '">'.$button_code.'</div>';

	return $button_code;
}

// Admin menu page linked to fb_plugin_options()
function bloglovin_follow_admin_menu() {
  add_options_page( 'Bloglovin Follow Options', 'Bloglovin Follow', 8, __FILE__, 'bloglovin_follow_options' );
}

function bloglovin_follow_options() {
?>
<div class="wrap bfadmin">
<h2><?php _e( "Bloglovin Follow", 'bloglovin_follow_trans_domain' ); ?></h2>

<form method="post" action="options.php">

  <?php settings_fields( 'bloglovin_follow' ); ?>
	
  <table class="form-table">
    <tr valign="top">
    	<th scope="row"><h3><?php _e( "Button code", 'bloglovin_follow_trans_domain' ); ?></h3></th>
		</tr>

		<tr valign="top">
			<th scope="row"><?php _e( "Paste your button code:", 'bloglovin_follow_trans_domain' ); ?></th>
			<td class="bfadmin-cell">
				<textarea class="bfadmin-button-code" name="bloglovin_follow_button_code"><?php echo get_option( 'bloglovin_follow_button_code' ); ?></textarea> <br /><small><?php _e( "Paste the code that you can obtain from <a href='http://www.bloglovin.com/widgets' target='_blank'>www.bloglovin.com/widgets</a>.", 'bloglovin_follow_trans_domain' ) ?></small>
			</td>
    </tr>
    		
    <tr valign="top">
    	<th scope="row"><h3><?php _e( "Position", 'bloglovin_follow_trans_domain' ); ?></h3></th>
		</tr>

		<tr valign="top">
			<th scope="row" colspan="2" class="bfadmin-cell">
				<small><?php _e( "You can also add the button manually to your posts and pages by using the shortcode: <code>[bloglovin-follow-button]</code>.<br />Or use PHP: <code>&lt;?php echo do_shortcode( '[bloglovin-follow-button]' ); ?&gt;</code>.", 'bloglovin_follow_trans_domain' ); ?></small>
			</th>
		</tr>

    <tr valign="top">
			<th scope="row"><?php _e("Show at:", 'bloglovin_follow_trans_domain' ); ?></th>
			<td>
				<input id="top" type="checkbox" name="bloglovin_follow_show_at_top" value="true" <?php echo ( get_option( 'bloglovin_follow_show_at_top' ) == 'true' ? 'checked' : '' ); ?>/> <label for="top"><?php _e( "Top", 'bloglovin_follow_trans_domain' ); ?></label><br />
        <input id="bottom" type="checkbox" name="bloglovin_follow_show_at_bottom" value="true" <?php echo ( get_option( 'bloglovin_follow_show_at_bottom' ) == 'true' ? 'checked' : '' ); ?>/> <label for="bottom"><?php _e( "Bottom", 'bloglovin_follow_trans_domain' ); ?></label>
      </td>
		</tr>
    
    <tr valign="top">
      <th scope="row"><?php _e( "Show on:", 'bloglovin_follow_trans_domain' ); ?></th>
      <td>
        <input id="page" type="checkbox" name="bloglovin_follow_show_on_page" value="true" <?php echo ( get_option( 'bloglovin_follow_show_on_page' ) == 'true' ? 'checked' : '' ); ?>/> <label for="page"><?php _e( "Page", 'bloglovin_follow_trans_domain' ); ?></label><br />

        <input id="post" type="checkbox" name="bloglovin_follow_show_on_post" value="true" <?php echo ( get_option( 'bloglovin_follow_show_on_post' ) == 'true' ? 'checked' : '' ); ?>/> <label for="post"><?php _e( "Post", 'bloglovin_follow_trans_domain' ); ?></label><br />

        <?php
    		$post_types = get_post_types( array( 'public' => true, '_builtin' => false ), 'objects', 'and' ); 
				foreach ( $post_types as $post_type ) {
					?>
					<input id="post-type-<?php echo $post_type->name; ?>" type="checkbox" name="bloglovin_follow_show_on_post_type_<?php echo $post_type->name; ?>" value="true" <?php echo ( get_option( 'bloglovin_follow_show_on_post_type_'.$post_type->name ) == 'true' ? 'checked' : '' ); ?>/> <label for="post"><?php _e( $post_type->labels->name ); ?></label><br />
					<?php
				}
    		?>

        <input id="home" type="checkbox" name="bloglovin_follow_show_on_home" value="true" <?php echo ( get_option( 'bloglovin_follow_show_on_home' ) == 'true' ? 'checked' : '' ); ?>/> <label for="home"><?php _e( "Home", 'bloglovin_follow_trans_domain' ); ?></label><br />

        <input id="search" type="checkbox" name="bloglovin_follow_show_on_search" value="true" <?php echo ( get_option( 'bloglovin_follow_show_on_search' ) == 'true' ? 'checked' : '' ); ?>/> <label for="search"><?php _e( "Search", 'bloglovin_follow_trans_domain' ); ?></label><br />

        <input id="archive" type="checkbox" name="bloglovin_follow_show_on_archive" value="true" <?php echo ( get_option( 'bloglovin_follow_show_on_archive' ) == 'true' ? 'checked' : '' ); ?>/> <label for="archive"><?php _e( "Archive", 'bloglovin_follow_trans_domain' ); ?></label>
      </td>
    </tr>

    <tr valign="top">
			<th scope="row"><?php _e( "Margins:", 'bloglovin_follow_trans_domain' ); ?></th>
      <td>
        <div class="bfadmin-margin-settings">
					<span><input size="2" type="text" name="bloglovin_follow_margin_top" value="<?php echo get_option( 'bloglovin_follow_margin_top' ); ?>" /> <small>px</small></span>
					<br />
					<span class="bfadmin-margin-settings-left"><input size="2" type="text" name="bloglovin_follow_margin_left" value="<?php echo get_option( 'bloglovin_follow_margin_left' ); ?>" /> <small>px</small></span>
					<img src="<?php echo bloglovin_follow_pluginPath( 'images/bloglovin-follow.gif' ); ?>" alt="Bloglovin" />
					<span class="bfadmin-margin-settings-right"><input size="2" type="text" name="bloglovin_follow_margin_right" value="<?php echo get_option( 'bloglovin_follow_margin_right' ); ?>" /> <small>px</small></span>
					<br />
					<span><input size="2" type="text" name="bloglovin_follow_margin_bottom" value="<?php echo get_option( 'bloglovin_follow_margin_bottom' ); ?>" /> <small>px</small></span>
				</div>
			</td>
    </tr>
        
		<tr valign="top">
      <th scope="row"><?php _e( "Exclude posts and pages:", 'bloglovin_follow_trans_domain' ); ?></th>
      <td class="bfadmin-cell">
      	<input size="50" type="text" name="bloglovin_follow_excl_post" value="<?php echo get_option( 'bloglovin_follow_excl_post' ); ?>" /> <br /><small><?php _e( "You can type for each post/page ID, title, or slug seperated with commas.<br />E.g. <code>17, Irish Stew, beef-stew</code>.", 'bloglovin_follow_trans_domain' ) ?></small>
      </td>
    </tr>
		
		<tr valign="top">
			<th scope="row"><?php _e( "Exclude categories:", 'bloglovin_follow_trans_domain' ); ?></th>
      <td class="bfadmin-cell">
      	<input size="50" type="text" name="bloglovin_follow_excl_cat" value="<?php echo get_option( 'bloglovin_follow_excl_cat' ); ?>" /> <br /><small><?php _e( "You can type for each category ID, name, or slug seperated with commas.<br />E.g. <code>9, Stinky Cheeses, blue-cheese</code>.", 'bloglovin_follow_trans_domain' ) ?></small>
      </td>
    </tr>

		<tr valign="top">
      <th scope="row"><?php _e( "Additional CSS style:", 'bloglovin_follow_trans_domain' ); ?></th>
      <td class="bfadmin-cell">
      	<input size="80" type="text" name="bloglovin_follow_css_style" value="<?php echo get_option( 'bloglovin_follow_css_style' ); ?>" /> <br /><small><?php _e( "Added properties will be placed between <code>style=\"</code> and <code>\"</code>.", 'bloglovin_follow_trans_domain' ) ?><small>
      </td>
    </tr>

    <tr valign="top">
      <th scope="row"><?php _e( "Additional CSS class(es):", 'bloglovin_follow_trans_domain' ); ?></th>
      <td class="bfadmin-cell">
      	<input size="80" type="text" name="bloglovin_follow_css_class" value="<?php echo get_option( 'bloglovin_follow_css_class' ); ?>" /> <br /><small><?php _e( "The bloglovin button has the class .bloglovin-button per default. Here you can add more classes if you like. Separate each class with a space.", 'bloglovin_follow_trans_domain' ) ?><small>
      </td>
    </tr>    
  </table>
    
	<p class="submit">
		<input type="submit" class="button-primary" value="<?php _e( 'Save' ) ?>" />
	</p>
	
</form>
</div>
<?php
}

class BloglovinFollowWidget extends WP_Widget {
  function BloglovinFollowWidget() {
    $widget_ops = array( 'classname' => 'bloglovin-follow-widget', 'description' => __( 'Displays the Bloglovin Follow button.', 'bloglovin_follow_trans_domain' )  );
    $this->WP_Widget( 'BloglovinFollowWidget', __( 'Bloglovin Follow', 'bloglovin_follow_trans_domain' ), $widget_ops );
  }
 
  function form( $instance ) {
    $instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
    $title = $instance['title'];
?>
  	<p>
  		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo attribute_escape( $title ); ?>" /></label>
  	</p>
<?php
  }

  function update( $new_instance, $old_instance ) {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    return $instance;
  }
 
  function widget( $args, $instance ) {
    extract( $args, EXTR_SKIP );

    $button_code = bloglovin_follow_generate_button();

    if ( !empty( $button_code ) ) {
    	echo $before_widget;

    	$title = empty( $instance['title'] ) ? ' ' : apply_filters( 'widget_title', $instance['title'] );
    	if ( !empty( $title ) ) {
    		echo $before_title;
      	echo $title;
      	echo $after_title;
    	}

    	echo $button_code;

    	echo $after_widget;
    }
  }
}

bloglovin_follow_init();
?>
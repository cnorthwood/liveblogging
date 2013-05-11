<?php
/*
Plugin Name: Live Blogging
Plugin URI: http://cnorthwood.github.io/liveblogging/
Description: Plugin to support automatic live blogging
Version: 2.3
Author: Chris Northwood
Author URI: http://www.pling.org.uk/
Text-Domain: live-blogging

Live Blogging for WordPress
Copyright (C) 2010-2013 Chris Northwood <chris@pling.org.uk>
Contributors: Gabriel Koen, Corey Gilmore, Juliette

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

require( 'LiveBlogging.php' );
LiveBlogging::get_instance()->init();



// Insert live blog button on rich editor
function live_blogging_insert_liveblog_button( $context ) {
	$additional = '<a class="button" style="cursor:pointer;" onclick="live_blog_insert_shortcode();" id="insert-live-blog-shortcode">Live Blog</a>';
	return $context . $additional;
}

add_action( 'media_buttons_context', 'live_blogging_insert_liveblog_button' );

function live_blogging_shortcode_insert_button() {
	?>
	<script type="text/javascript">
		function live_blog_insert_shortcode() {
			var win = window.dialogArguments || opener || parent || top;
			win.send_to_editor("[liveblog]");
		}
	</script>
<?php
}

add_action( 'admin_head', 'live_blogging_shortcode_insert_button' );

if ( is_admin() ) {
	function is_liveblog_edit_post() {
		if ( ! is_admin() ) {
			return false;
		} elseif (
			strpos( $_SERVER['REQUEST_URI'], 'post-new.php' ) !== false && 'liveblog_entry' == $_GET['post_type']
		) {
			return true;
		} elseif ( strpos( $_SERVER['REQUEST_URI'], 'post.php' ) !== false ) {
			if ( 'liveblog_entry' == $GLOBALS['post']->post_type ) {
				return true;
			}
		}
		return false;
	}

	function live_blogging_admin_js() {
		if ( is_liveblog_edit_post() ) {
			?>
			<script type="text/javascript">/*<![CDATA[ */
				jQuery(document).ready(function () {
					// Add title to New Entry box
					jQuery("div#quicktags").html("<h3 class=\"hndle\"><span>New Entry</span></h3>");

					// Allow image uploads
					jQuery("form#post").attr("enctype", "multipart/form-data");
					// Publish the post on image upload click
					jQuery("input#quick-image-upload").click(function () {
						jQuery("input#publish").click();
						return false;
					});

					// Get current posts (default entry)
					live_blogging_update_chatbox();
				});

				function get_selected_liveblog_post_id() {
					var liveblog_selector = document.getElementById("live_blogging_entry_post");
					return liveblog_selector.options[liveblog_selector.selectedIndex].value;
				}

				function live_blogging_update_chatbox() {
					jQuery.post(
						"<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>",
						{
							action: "live_blogging_update_chatbox",
							liveblog_id: get_selected_liveblog_post_id()
						},
						function (response) {
							var entries = JSON.parse(response);
							jQuery("#live_blogging_chatbox").find(".inside").html(entries)
						},
						'application/json'
					);
					setTimeout(live_blogging_update_chatbox, 10000);
				}
				/*]]>*/</script>
		<?php
		}
	}

	add_action( 'admin_head', 'live_blogging_admin_js' );

	/**
	 * Overloading a filter to remove the "Post saved" message
	 */
	function live_blogging_remove_messages( $messages ) {
		if ( is_liveblog_edit_post() ) {
			if ( isset( $_GET['message'] ) && 6 == $_GET['message'] ) {
				unset( $_GET['message'] );
			}
		}
		return $messages;
	}

	add_filter( 'post_updated_messages', 'live_blogging_remove_messages' );

	// Post saving
	/**
	 * Run on_post_edit actions against the parent post.  This is primarily to trigger W3TC's clear page cache.
	 * Only needs to run if polling is disabled, since polling updates the post content via ajax.
	 */
	function live_blogging_do_edit_post( $data ) {
		if ( is_liveblog_edit_post() && 'disabled' === get_option( 'liveblogging_method' ) ) {
			$parent_post_id = (int)$_POST['live_blogging_entry_post'];
			$parent_post    = get_post( $parent_post_id );
			do_action( 'edit_post', $parent_post_id, $parent_post );
		}
		return $data;
	}

	add_filter( 'wp_insert_post_data', 'live_blogging_do_edit_post', 10, 1 );

	function live_blogging_save_upload( $data, $postarr ) {
		// Check for autosaves
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return $data;
		}
		if ( is_liveblog_edit_post() && $_FILES ) {
			$file = wp_handle_upload( $_FILES['async-upload'], array( 'action' => 'editpost' ) );
			if ( isset( $file['file'] ) && ! is_wp_error( $file ) ) {
				$name = $_FILES['async-upload']['name'];

				$name_parts = pathinfo( $name );
				$name       = trim( substr( $name, 0, - ( 1 + strlen( $name_parts['extension'] ) ) ) );

				$url     = $file['url'];
				$type    = $file['type'];
				$file    = $file['file'];
				$title   = $name;
				$content = '';

				// use image exif/iptc data for title and caption defaults if possible
				if ( $image_meta = @wp_read_image_metadata( $file ) ) {
					if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
						$title = $image_meta['title'];
					}
					if ( trim( $image_meta['caption'] ) ) {
						$content = $image_meta['caption'];
					}
				}

				// Construct the attachment array
				$attachment = array(
					'post_mime_type' => $type,
					'guid'           => $url,
					'post_parent'    => (int)$_POST['live_blogging_entry_post'],
					'post_title'     => $title,
					'post_content'   => $content,
				);

				// Save the data
				$id = wp_insert_attachment( $attachment, $file, (int)$_POST['live_blogging_entry_post'] );
				if ( ! is_wp_error( $id ) ) {
					wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
				}

				// Add the image to the post
				$data['post_content'] .= '<div class="liveblog-image">' . wp_get_attachment_image( $id, 'large' ) . '</div>';
			}
		}
		return $data;
	}

	add_filter( 'wp_insert_post_data', 'live_blogging_save_upload', 10, 2 );

	function live_blogging_chatbox() {
		if ( ! isset( $_GET['live_blogging_entry_post'] ) ) {
			return;
		}
		$liveblog_id   = (int)$_GET['live_blogging_entry_post'];
		$liveblog_sort = 'ASC';
		live_blog_chatbox_get_posts( $liveblog_id, $liveblog_sort );
	}

	function live_blog_chatbox_get_posts( $liveblog_id, $liveblog_sort = 'DESC' ) {
		$liveblog         = new LiveBlogging_LiveBlog( intval( $liveblog_id ) );
		$liveblog_entries = $liveblog->get_liveblog_entries();
		if ( ! empty ( $liveblog_entries ) ) : ?>
			<div class="liveblog-entries">
				<?php foreach ( $liveblog_entries as $liveblog_entry ) : ?>
					<div id="liveblog-entry-<?php echo esc_attr( $liveblog_entry->id ); ?>">
						<?php $liveblog_entry->body(); ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif;
	}

	function live_blogging_update_chatbox() {
		header( 'Content-Type: application/json' );
		if ( ! isset( $_POST['liveblog_id'] ) ) {
			die();
		}
		ob_start();
		live_blog_chatbox_get_posts( (int)$_POST['liveblog_id'] );
		$response = ob_end_clean();
		echo json_encode( $response );
		die();
	}

	add_action( 'wp_ajax_live_blogging_update_chatbox', 'live_blogging_update_chatbox' );
}


// Redirect new posts to the new post page
add_filter( 'redirect_post_location', 'live_blogging_redirect', 10, 2 );
function live_blogging_redirect( $location, $post_id ) {
	$url  = explode( '?', $location, 2 );
	$args = wp_parse_args( $url[1] );
	$url  = explode( '/', $url[0] );
	$url  = $url[count( $url ) - 1];

	if ( $url == 'post.php' && isset( $_POST['publish'] ) ) {
		$post = get_post( $post_id );
		if ( 'liveblog_entry' == $post->post_type ) {
			return
				'post-new.php?post_type=liveblog_entry&live_blogging_entry_post=' . $_POST['live_blogging_entry_post']
					. '&message=' . $args['message'];
		}
	}

	return $location;
}

// Fix the title for live blogging entries to show the content (or at least
// it's excerpt)
add_filter( 'the_title', 'live_blogging_fix_title', 10, 2 );
function live_blogging_fix_title( $title, $id = 0 ) {
	$post = get_post( $id );
	if ( $id != 0 && 'liveblog_entry' == $post->post_type ) {
		$title = filter_var( $post->post_content, FILTER_SANITIZE_STRING );
		if ( strlen( $title ) > 140 ) {
			$title = substr( $title, 0, 140 ) . '&hellip;';
		}
	}
	return $title;
}

function live_blogging_taxonomy_name( $tax ) {
	if ( substr( $tax->name, 0, 6 ) == 'legacy' )
	{
		return $tax->description;
	}

	$test = get_post( intval( $tax->name ) );

	if ( $test == null ) {
		return 'Deleted post: ' . $tax->name;
	} else {
		return $test->post_title;
	}
}

// Show a custom column on the liveblog_entry edit screen
add_action( 'manage_posts_custom_column', 'live_blogging_custom_column', 10, 2 );
function live_blogging_custom_column( $column_name, $post_ID ) {
	switch ( $column_name ) {
	case 'liveblog':
		$blogs = wp_get_object_terms( array( $post_ID ), 'liveblog' );
		if ( count( $blogs ) > 0 ) {
			echo '<a href="post.php?post=' . $blogs[0]->name . '&amp;action=edit">' . live_blogging_taxonomy_name(
				$blogs[0]
			) . '</a>';
		}
		break;
	}
}

add_filter( 'manage_liveblog_entry_posts_columns', 'live_blogging_add_custom_column' );
function live_blogging_add_custom_column( $columns ) {
	$columns['liveblog'] = __( 'Live Blog', 'live-blogging' );
	return $columns;
}

// Insert the live blog in a post
add_shortcode( 'liveblog', 'live_blogging_shortcode' );

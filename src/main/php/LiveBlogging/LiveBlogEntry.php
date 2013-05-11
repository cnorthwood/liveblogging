<?php
/*
	Live Blogging for WordPress
	Copyright (C) 2010-2013 Chris Northwood <chris@pling.org.uk>

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

/**
 * This class encapsulates a single entry within a live blog
 */
class LiveBlogging_LiveBlogEntry
{
	/**
	 * @param $entry_id
	 *
	 * @return LiveBlogging_LiveBlog|null
	 */
	public static function live_blogs_for_entry_id( $entry_id ) {
		foreach ( wp_get_object_terms( array( $entry_id ), 'liveblog' ) as $liveblog_term ) {
			return new LiveBlogging_LiveBlog( $liveblog_term );
		}
		return null;
	}

	public static function is_being_edited() {
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

	public static function save_uploaded_files( $data, $postarr ) {
		// Check for autosaves
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return $data;
		}
		if ( self::is_being_edited() && $_FILES ) {
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

	public $id;
	private $post;

	public function __construct( $post ) {
		$this->post = $post;
		$this->id   = $post->ID;
	}

	public function build_body() {
		$style = get_option( 'liveblogging_style' );
		$user  = get_userdata( $this->post->post_author );

		LiveBlogging_Setting_ContentHooks::apply_unhooks();
		$style = str_replace(
			array( '$DATE', '$CONTENT', '$AUTHOR' ),
			array(
				get_the_time( get_option( 'liveblogging_date_style' ), $this->post ),
				apply_filters( 'the_content', $this->post->post_content ),
				apply_filters( 'the_author', $user->display_name ),
			),
			$style
		);
		LiveBlogging_Setting_ContentHooks::apply_hooks();

		return $style;
	}

	public function body() {
		// @codingStandardsIgnoreStart
		echo $this->build_body();
		// @codingStandardsIgnoreEnd
	}
}
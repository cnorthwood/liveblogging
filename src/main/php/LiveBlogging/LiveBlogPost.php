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
 * This class encapsulates a standard WordPress post
 */
class LiveBlogging_LiveBlogPost
{
	public static function process_shortcode( $atts, $id = null ) {
		global $post;
		if ( ! empty( $id ) ) {
			$id = 'legacy-' . $id;
		} else {
			$id = $post->ID;
		}
		$liveblog = new self( $id );
		ob_start();
		if ( LiveBlogging_Setting_UpdateMethod::is_enabled() && $liveblog->is_active_liveblog() ) {
			LiveBlogging::get_instance()->updater->javascript();
		} ?>
		<div id="liveblog-<?php echo esc_attr( $id ); ?>">
			<?php foreach ( $liveblog->get_liveblog_entries() as $entry ) : ?>
				<div id="liveblog-entry-<?php echo esc_attr( $entry->id ); ?>"><?php $entry->body(); ?></div>
			<?php endforeach; ?>
		</div><?php
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	public static function get_liveblogs_for_current_post() {
		global $post;
		$liveblog_post = new self( $post->ID );
		$liveblogs     = array();
		foreach ( $liveblog_post->get_liveblogs() as $liveblog ) {
			$liveblogs[$liveblog->post_id()] = $liveblog->name();
		}
		return $liveblogs;
	}

	/**
	 * Run on_post_edit actions against the parent post.  This is primarily to trigger W3TC's clear page cache.
	 * Only needs to run if polling is disabled, since polling updates the post content via ajax.
	 */
	public static function mark_parent_post_as_edited( $data ) {
		if ( LiveBlogging_LiveBlogEntry::is_being_edited() && ! LiveBlogging_Setting_UpdateMethod::is_enabled() ) {
			$parent_post_id = (int)$_POST['live_blogging_entry_post'];
			do_action( 'edit_post', $parent_post_id, get_post( $parent_post_id ) );
		}
		return $data;
	}

	private $post_id;

	public function __construct( $post_id ) {
		$this->post_id = $post_id;
	}

	public function is_active_liveblog() {
		return get_post_meta( $this->post_id, '_liveblog_active', true ) == '1';
	}

	public function build_comments_html() {
		$args = apply_filters( 'live_blogging_build_comments', array() );
		ob_start();
		wp_list_comments(
			$args,
			get_comments(
				array(
					'status'  => 'approve',
					'post_id' => intval( $this->post_id ),
					'order'   => 'ASC',
				)
			)
		);
		$comments = ob_get_clean();
		return $comments;
	}

	/**
	 * @return LiveBlogging_LiveBlogEntry[]
	 */
	public function get_liveblog_entries() {
		$entries = array();
		$q = new WP_Query(
			array(
				'post_type'      => 'liveblog_entry',
				'liveblog'       => strval( $this->post_id ),
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => ( 'bottom' == get_option( 'liveblogging_update_effect' ) ) ? 'ASC' : 'DESC',
			)
		);
		while ( $q->have_posts() ) {
			$q->next_post();
			$entries[] = new LiveBlogging_LiveBlogEntry( $q->post );
		}
		return $entries;
	}

	/**
	 * @return LiveBlogging_LiveBlog[]
	 */
	public function get_liveblogs() {
		$liveblogs = array();
		foreach ( wp_get_object_terms( array( $this->post_id ), 'liveblog' ) as $term ) {
			$liveblogs[] = new LiveBlogging_LiveBlog( $term );
		}
		return $liveblogs;
	}
}
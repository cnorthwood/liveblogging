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
 * This class captures the concept of a live blog. It is represented in WordPress as a taxonomy term which all
 * the individual entries are associated with. The name of the
 */
class LiveBlogging_LiveBlog
{

	public static function render_title( $title, $id = 0 ) {
		$post = get_post( $id );
		if ( $id != 0 && 'liveblog_entry' == $post->post_type ) {
			$title = filter_var( $post->post_content, FILTER_SANITIZE_STRING );
			if ( strlen( $title ) > 140 ) {
				$title = substr( $title, 0, 140 ) . '&hellip;';
			}
		}
		return $title;
	}

	private $term;

	public function __construct( $term ) {
		$this->term = $term;
	}

	public function post_id() {
		return $this->term->name;
	}

	public function name() {
		if ( substr( $this->post_id(), 0, 6 ) == 'legacy' ) {
			return $this->term->description;
		} else {
			$parent_post = get_post( intval( $this->post_id() ) );

			if ( $parent_post == null ) {
				return 'Deleted post: ' . $this->post_id();
			} else {
				return $parent_post->post_title;
			}
		}

	}

}
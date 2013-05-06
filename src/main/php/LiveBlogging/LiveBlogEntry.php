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

class LiveBlogging_LiveBlogEntry
{
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
}
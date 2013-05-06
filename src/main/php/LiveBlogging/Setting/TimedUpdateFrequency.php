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

class LiveBlogging_Setting_TimedUpdatedFrequency extends LiveBlogging_Setting
{
	public static $setting_name     = 'liveblogging_timed_update_freq';
	protected static $default_value = 25;

	/**
	 * Sanitize the value for timed updates and ensure reasonable max/min values.
	 *
	 * @param mixed $input value in seconds between page refreshes
	 * @return int $return value in seconds
	 *
	 */
	public function sanitise_setting( $input ) {
		return abs( round( intval( $input ) ) );
	}

	public static function admin_label() {
		_e( 'Number of seconds between page refreshes (min 15, max 180) in timed update mode', 'live-blogging' );
	}
}
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

class LiveBlogging_Setting_UpdateMethod extends LiveBlogging_Setting
{
	protected static $setting_name  = 'liveblogging_method';
	protected static $default_value = 'poll';

	public function sanitise_setting( $input ) {
		switch ( $input )
		{
			case 'poll':
			case 'meteor':
			case 'timed':
			case 'disabled':
				return $input;
			default:
				return 'disabled';
		}
	}

	public static function is_enabled() {
		return ! self::is( 'disabled' );
	}

	public static function is_ajax() {
		return self::is( 'poll' );
	}

	public static function is_meteor() {
		return self::is( 'meteor' );
	}

	public static function is_timed() {
		return self::is( 'timed' );
	}

}
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

class LiveBlogging_Setting_DateStyle extends LiveBlogging_Setting
{
	public static $setting_name     = 'liveblogging_date_style';
	protected static $default_value = 'H.i';

	public static function admin_label() {
		_e( 'Date specifier for live blog entries (see <a href="http://uk3.php.net/manual/en/function.date.php">PHP\'s date function</a> for allowable strings)', 'live-blogging' );
	}
}
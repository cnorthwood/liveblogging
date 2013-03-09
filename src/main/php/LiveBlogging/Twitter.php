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

class LiveBlogging_Twitter
{
	public function setup_timer() {
		if ( LiveBlogging_Setting_Twitter::is_enabled() && LiveBlogging_Setting_TwitterComments::is_enabled() ) {
			if ( $this->schedule_not_created() ) {
				$this->create_schedule();
			}
		} else if ( $this->get_schedule() ) {
			// Schedule is enabled even though Twitter comment importing is disabled, so we should clear it
			$this->clear_schedule();
		}
	}

	protected function create_schedule() {
		wp_schedule_event( time(), 'live_blogging', 'live_blogging_check_twitter' );
	}

	protected function clear_schedule() {
		wp_clear_scheduled_hook( 'live_blogging_check_twitter' );
	}

	protected function get_schedule() {
		return wp_get_schedule( 'live_blogging_check_twitter' );
	}

	protected function schedule_not_created() {
		return false === $this->get_schedule();
	}
}
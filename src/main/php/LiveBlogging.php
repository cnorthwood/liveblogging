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

require( 'LiveBlogging/Setting.php' );
require( 'LiveBlogging/Setting/Comments.php' );
require( 'LiveBlogging/Setting/ContentHooks.php' );
require( 'LiveBlogging/Setting/DateStyle.php' );
require( 'LiveBlogging/Setting/MeteorController.php' );
require( 'LiveBlogging/Setting/MeteorControllerPort.php' );
require( 'LiveBlogging/Setting/MeteorNamespace.php' );
require( 'LiveBlogging/Setting/MeteorSubscriber.php' );
require( 'LiveBlogging/Setting/PostStyle.php' );
require( 'LiveBlogging/Setting/TimedUpdateFrequency.php' );
require( 'LiveBlogging/Setting/Twitter.php' );
require( 'LiveBlogging/Setting/TwitterComments.php' );
require( 'LiveBlogging/Setting/UpdateMethod.php' );
require( 'LiveBlogging/Setting/UpdateStyle.php' );
require( 'LiveBlogging/Updater/Ajax.php' );
require( 'LiveBlogging/Updater/Meteor.php' );
require( 'LiveBlogging/Updater/Timed.php' );
require( 'LiveBlogging/Twitter.php' );

class LiveBlogging
{

	/** @var LiveBlogging_Setting[] */
	protected $settings = array();
	protected $updater;

	public function __construct() {
		$this->settings[] = new LiveBlogging_Setting_Comments();
		$this->settings[] = new LiveBlogging_Setting_ContentHooks();
		$this->settings[] = new LiveBlogging_Setting_DateStyle();
		$this->settings[] = new LiveBlogging_Setting_MeteorController();
		$this->settings[] = new LiveBlogging_Setting_MeteorControllerPort();
		$this->settings[] = new LiveBlogging_Setting_MeteorNamespace();
		$this->settings[] = new LiveBlogging_Setting_MeteorSubscriber();
		$this->settings[] = new LiveBlogging_Setting_PostStyle();
		$this->settings[] = new LiveBlogging_Setting_TimedUpdatedFrequency();
		$this->settings[] = new LiveBlogging_Setting_Twitter();
		$this->settings[] = new LiveBlogging_Setting_TwitterComments();
		$this->settings[] = new LiveBlogging_Setting_UpdateMethod();
		$this->settings[] = new LiveBlogging_Setting_UpdateStyle();

		$this->twitter = new LiveBlogging_Twitter();
	}

	public function init() {
		$this->load_language_resources();
		$this->register_taxonomy();
		$this->register_custom_post_type();
		$this->load_updater();
		$this->twitter->setup_timer();
	}

	private function load_updater() {
		if ( LiveBlogging_Setting_UpdateMethod::is_enabled() ) {
			$this->add_javascript_assets();

			if ( LiveBlogging_Setting_UpdateMethod::is_ajax() ) {
				$this->updater = new LiveBlogging_Updater_Ajax();
			} elseif ( LiveBlogging_Setting_UpdateMethod::is_meteor() ) {
				$this->updater = new LiveBlogging_Updater_Meteor();
			} elseif ( LiveBlogging_Setting_UpdateMethod::is_timed() ) {
				$this->updater = new LiveBlogging_Updater_Timed();
			}
		}
	}

	private function add_javascript_assets() {
		wp_enqueue_script(
			'live-blogging', plugins_url( 'live-blogging.min.js', __FILE__ ), array( 'jquery', 'json2' )
		);
		wp_localize_script(
			'live-blogging', 'live_blogging', array(
				'ajaxurl'       => addslashes( admin_url( 'admin-ajax.php' ) ),
				'update_effect' => get_option( 'liveblogging_update_effect' ),
			)
		);
	}

	private function register_custom_post_type() {
		register_post_type(
			'liveblog_entry',
			array(
				'labels'             => array(
					'name'               => __( 'Live Blog Entries', 'live-blogging' ),
					'singular_name'      => __( 'Live Blog Entry', 'live-blogging' ),
					'add_new_item'       => __( 'Add New Entry', 'live-blogging' ),
					'edit_item'          => __( 'Edit Entry', 'live-blogging' ),
					'new_item'           => __( 'New Entry', 'live-blogging' ),
					'view_item'          => __( 'Edit Entry', 'live-blogging' ),
					'search_items'       => __( 'Search Entries', 'live-blogging' ),
					'not_found'          => __( 'No entries found', 'live-blogging' ),
					'not_found_in_trash' => __( 'No entries found in trash', 'live-blogging' )
				),
				'description'        => __( 'Individual entries for a live blog', 'live-blogging' ),
				'show_ui'            => true,
				'supports'           => array( 'editor', 'author' ),
				'taxonomies'         => array( 'liveblog' ),
				'menu_icon'          => plugins_url( 'img/icon.png', __FILE__ ),
				'publicly_queryable' => true,
			)
		);
	}

	/**
	 * The Live Blogging taxonomy allows us to associate live blogs with posts
	 */
	private function register_taxonomy() {
		register_taxonomy( 'liveblog', null, array( 'public' => false ) );
	}

	private function load_language_resources() {
		load_plugin_textdomain( 'live-blogging', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	public function admin_init() {
		foreach ( $this->settings as $setting ) {
			$setting->register_setting();
		}
	}

	public function activate_plugin() {
		foreach ( $this->settings as $setting ) {
			$setting->activate_setting();
		}
	}

	public function deactivate_plugin() {
		wp_clear_scheduled_hook( 'live_blogging_check_twitter' );
	}
}
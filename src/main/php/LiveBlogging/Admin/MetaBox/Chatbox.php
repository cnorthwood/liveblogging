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

class LiveBlogging_Admin_MetaBox_Chatbox extends LiveBlogging_Admin_MetaBox
{
	public function __construct() {
		add_action( 'wp_ajax_live_blogging_update_chatbox', array( $this, 'render_chatbox_ajax_response' ) );
		add_action( 'admin_head',  array( $this, 'render_chatbox_js' ) );
	}

	public function register_meta_box() {
		add_meta_box(
			'live_blogging_chatbox',
			__( 'Entries', 'live-blogging' ),
			array( $this, 'render_chatbox' ),
			'liveblog_entry',
			'normal'
		);
	}

	public function render_chatbox() {
		if ( ! isset( $_GET['live_blogging_entry_post'] ) ) {
			return;
		}
		$liveblog_id   = (int)$_GET['live_blogging_entry_post'];
		$liveblog_sort = 'ASC';
		$this->get_posts( $liveblog_id, $liveblog_sort );
	}

	private function get_posts( $liveblog_id, $liveblog_sort = 'DESC' ) {
		$liveblog         = new LiveBlogging_LiveBlogPost( intval( $liveblog_id ) );
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

	public function render_chatbox_ajax_response() {
		header( 'Content-Type: application/json' );
		if ( ! isset( $_POST['liveblog_id'] ) ) {
			die();
		}
		ob_start();
		$this->get_posts( (int)$_POST['liveblog_id'] );
		$response = ob_end_clean();
		echo json_encode( $response );
		die();
	}

	public function render_chatbox_js() {
		if ( LiveBlogging_LiveBlogEntry::is_being_edited() ) {
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

}
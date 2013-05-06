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

class LiveBlogging_Legacy
{
	public static function exists() {
		global $wpdb, $table_prefix;
		$tables = $wpdb->get_results( "SHOW TABLES LIKE '{$table_prefix}liveblogs'" );
		return ! empty($tables);
	}

	public static function migrate() { ?>
		<p>Starting upgrade...</p>
		<ul>
			<?php foreach ( self::get_legacy_entries() as $entry ) self::migrate_legacy_entry( $entry ); ?>
		</ul>
		<?php
		$num_left = self::remaining_to_migrate();

		if ( $num_left > 0 ) {
			self::render_next_page( $num_left );
		} else {
			self::migrate_legacy_blogs();
		}
	}

	/**
	 * @return mixed
	 */
	private static function get_legacy_entries() {
		/** @var $wpdb wpdb */
		global $wpdb, $table_prefix;
		$query   = "SELECT `lbid`,`entryid`,`author`,`post`,`dt` FROM `{$table_prefix}liveblog_entries` LIMIT 50";
		$entries = $wpdb->get_results( $query );
		return $entries;
	}

	/**
	 * @param $entry
	 */
	private static function migrate_legacy_entry( $entry ) {
		echo '<li>Importing live blog entry: ' . esc_html( substr( $entry->post, 0, 100 ) ) . '&hellip;</li>';
		$import = array(
			'post_status'  => 'publish',
			'post_type'    => 'liveblog_entry',
			'post_author'  => $entry->author,
			'post_title'   => 'Imported legacy liveblog post',
			'post_content' => $entry->post,
			'post_date'    => mysql2date( 'Y-m-d H:i:s', $entry->dt ),
			'tax_input'    => array(
				'liveblog' => 'legacy-' . $entry->lbid,
			)
		);
		wp_insert_post( $import );
		self::remove_legacy_entry( $entry );
	}

	/**
	 * @param $entry
	 */
	private static function remove_legacy_entry( $entry ) {
		/** @var $wpdb wpdb */
		global $wpdb, $table_prefix;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `{$table_prefix}liveblog_entries` WHERE `entryid`=%d",
				$entry->entryid
			)
		);
	}

	/**
	 * @return mixed
	 */
	private static function remaining_to_migrate() {
		/** @var $wpdb wpdb */
		global $wpdb, $table_prefix;
		$num_left = $wpdb->get_results(
			"SELECT COUNT(*) AS num_left FROM `{$table_prefix}liveblog_entries`"
		);
		return $num_left[0]->num_left;
	}

	private static function render_next_page( $num_left ) {
		?><p><?php printf( _n( '%d description left to migrate', '%d descriptions left to migrate.', $num_left, 'live-blogging' ), $num_left ); ?></p>
		<form method="post" action="tools.php?page=live-blogging-upgrade">
			<?php wp_nonce_field( 'live-blogging-upgrade' ); ?>
			<p><input type="submit" class="button-primary"
					  id="live-blogging-continue-migration"
					  name="live_blogging_start_migration"
					  value="<?php _e( 'Next batch', 'live-blogging' ) ?>"/>
			</p>
			<script type="text/javascript">
				/*<![CDATA[ */
				jQuery(function () {
					jQuery('#live-blogging-continue-migration').click();
					jQuery('#live-blogging-continue-migration').replaceWith(
						'<em>Processing next batch...</em>'
					);
				});
				/*]]>*/
			</script>
		</form><?php
	}

	private static function migrate_legacy_blogs() { ?>
		<ul>
			<?php foreach ( self::get_legacy_liveblogs() as $liveblog ) : ?>
				<?php self::migrate_legacy_live_blog( $liveblog ); ?>
				<li>Imported live blog <?php echo esc_html( $liveblog->desc ); ?></li>
			<?php endforeach; ?>
		</ul>
		<p>Cleaning up tables...</p>
		<?php self::clean_up_legacy_tables(); ?>
		<p>Migration done!</p><?php
	}

	private static function get_legacy_liveblogs() {
		/** @var $wpdb wpdb */
		global $wpdb, $table_prefix;
		return $wpdb->get_results( "SELECT `lbid`,`desc` FROM `{$table_prefix}liveblogs`" );
	}

	private static function clean_up_legacy_tables() {
		/** @var $wpdb wpdb */
		global $wpdb, $table_prefix;
		$wpdb->query(
			"DROP TABLE `{$table_prefix}liveblogs`, `{$table_prefix}liveblog_entries`"
		);
	}

	private static function migrate_legacy_live_blog( $liveblog ) {
		$t = get_term_by( 'name', 'legacy-' . $liveblog->lbid, 'liveblog' );
		wp_update_term( $t->term_id, 'liveblog', array( 'description' => $liveblog->desc ) );
	}

}
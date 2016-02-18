<?php
/*
Plugin Name: BuddyPress Abuse Department
Plugin URI: http://www.icoder.com/wordpress/buddypress/bp-abuse/
Description: Abuse and moderation for BuddyPress communities.
Version: 0.3
Revision Date: August 10, 2010
Requires at least: WP 2.9.2, BP 1.2.1
Tested up to: WP 2.9.2, BP 1.2.3
License: GNU General Public License 2.0 (GPL) http://www.gnu.org/licenses/gpl.html
Author: Michel Komarov
Author URI: http://askmichel.icoder.com
*/

/* Only load the component if BuddyPress is loaded and initialized. */
function bp_abuse_init() {
	/* Define a constant that will hold the current version number of the component */
	define ( 'BP_ABUSE_VERSION', '0.3' );
	define ( 'BP_ABUSE_DB_VERSION', 2 );
	define ( 'BP_ABUSE_SLUG', 'abuse' );
	if ( is_user_logged_in() ) {
		require( dirname( __FILE__ ) . '/screen.php' );
		add_action( 'wp', 'bp_abuse_setup_globals', 2 );
		add_action( 'wp', 'bp_abuse_register_report',3 );
		do_action('bp_abuse_init');
	}
}
add_action( 'bp_init', 'bp_abuse_init' );

function bp_abuse_setup_globals() {
	global $bp;
	$bp->abuse = new stdClass();
	$bp->abuse->id = 'abuse';
	$bp->abuse->slug = BP_ABUSE_SLUG;
	$bp->abuse->topic_post_slug = 'topic_post';
	$bp->abuse->auto_moderate = intval(get_site_option('bp-abuse-auto-moderate'))? true: false;
	$num = intval(get_site_option( 'bp-abuse-num-reports-to-auto-moderate' ));
	$bp->abuse->num_reports_to_auto_moderate = $num? $num: 5;
	$bp->active_components[$bp->abuse->slug] = $bp->abuse->id;
}
function bp_abuse_setup_root_component() {
	/* Register 'abuse' as a root component */
	bp_core_add_root_component( BP_ABUSE_SLUG );
}
add_action( 'bp_setup_root_components', 'bp_abuse_setup_root_component' );

function bp_abuse_register_report() {
	global $bp;
	if ( $bp->current_component == $bp->abuse->slug && is_user_logged_in()
	&& is_array($bp->action_variables) && isset($bp->action_variables[0]) && isset($bp->action_variables[1]) ) {
		$activity_hash = $bp->action_variables[1];
		if ( ($activity_type = $bp->current_action) == $bp->abuse->topic_post_slug ) {
			if ( $post = bp_forums_get_post($item_id = $bp->action_variables[0]) ) {
				if ($activity_hash == bp_abuse_get_link_hash($item_id, $activity_type, $poster_id = $post->poster_id) ) {
					$activity_id = bp_activity_get_activity_id( array(
						'user_id'           => $poster_id,
						'component'         => 'groups',
						'type'              => ($activity_type = 'new_forum_post'),
						'secondary_item_id' => $item_id,
					) );
					if (0 == intval($activity_id)) {
						$activity_id = bp_activity_get_activity_id( array(
							'user_id'           => $poster_id,
							'component'         => 'groups',
							'type'              => ($activity_type = 'new_forum_topic'),
							'secondary_item_id' => $post->topic_id,
						) );
					}
					bp_abuse_save_report($bp->loggedin_user->id, $poster_id, $activity_type, $activity_id, $item_id);
					$num_reports = bp_abuse_get_reports_count($item_id, $bp->abuse->topic_post_slug);
					if (1 == $num_reports) bp_abuse_send_notification($activity_id);
					if ( $bp->abuse->auto_moderate && $num_reports >= $bp->abuse->num_reports_to_auto_moderate ) {
						bp_abuse_moderate($poster_id, $activity_type, $activity_id, $item_id);
					}
				}
			}
		}
		else {
			$activity = bp_activity_get_specific( array(
				'activity_ids' => ($activity_id = $bp->action_variables[0]),
			) );
			if ( count($activity['activities']) > 0 && ($activity = $activity['activities'][0]) ) {
				if ($activity_hash == bp_abuse_get_link_hash($activity_id, $activity_type, $poster_id = $activity->user_id) ) {
					switch ($activity_type) {
						default:
							$item_id = $activity->secondary_item_id;
							break;
						case 'activity_comment':
							$item_id = 0;
							break;
						case 'new_forum_topic':
							$item_id = 0;
							$posts = bp_forums_get_topic_posts( array(
								'topic_id' => $activity->secondary_item_id,
								'page'     => 1,
								'per_page' => 1
								) );
							if ( $posts ) {
								$item_id = $posts[0]->post_id;
							}
							break;
						case 'bp_album_picture':
							$item_id = $activity->item_id;
							break;
					}
					bp_abuse_save_report($bp->loggedin_user->id, $poster_id, $activity_type, $activity_id, $item_id);
					$num_reports = bp_abuse_get_reports_count($activity_id, $activity_type);
					if (1 == $num_reports) bp_abuse_send_notification($activity_id);
					if ( $bp->abuse->auto_moderate && $num_reports >= $bp->abuse->num_reports_to_auto_moderate ) {
						bp_abuse_moderate($poster_id, $activity_type, $activity_id, $item_id);
					}
				}
			}
		}
		if ( ! $redirect = wp_get_referer() ) $redirect = $bp->root_domain;
		bp_core_redirect( $redirect );
	}
}

function bp_abuse_get_reports_count($activity_id, $activity_type) {
	global $wpdb, $bp;
	$count = 0;
	if ( $activity_type == $bp->abuse->topic_post_slug ) {
		if (!isset($bp->abuse->cache2)) {
			$sql = $wpdb->prepare(
				"SELECT `item_id` AS `id`, COUNT(*) AS `num_reports`,
						COUNT(`ignored`) AS `num_ignored`
					FROM {$wpdb->base_prefix}bp_abuse
					WHERE `item_id` > 0 AND `moderated` IS NULL
					GROUP BY `item_id`
					HAVING `num_ignored` = 0"
				);
			$reports = $wpdb->get_results( $sql );
			if ($reports) {
				$bp->abuse->cache2 = array();
				foreach ( (array) $reports as $row ) {
					$bp->abuse->cache2[$row->id] = $row->num_reports;
				}
			}
		}
		if (isset($bp->abuse->cache2) && isset($bp->abuse->cache2[$activity_id]))
			return intval($bp->abuse->cache2[$activity_id]);
	}
	else {
		if (!isset($bp->abuse->cache)) {
			$sql = $wpdb->prepare(
				"SELECT `activity_id` AS `id`, COUNT(*) AS `num_reports`,
						COUNT(`ignored`) AS `num_ignored`
					FROM {$wpdb->base_prefix}bp_abuse
					WHERE `moderated` IS NULL
					GROUP BY `activity_id`
					HAVING `num_ignored` = 0"
				);
			$reports = $wpdb->get_results( $sql );
			if ($reports) {
				$bp->abuse->cache = array();
				foreach ( (array) $reports as $row ) {
					$bp->abuse->cache[$row->id] = $row->num_reports;
				}
			}
		}
		if (isset($bp->abuse->cache) && isset($bp->abuse->cache[$activity_id]))
			return intval($bp->abuse->cache[$activity_id]);
	}
	return $count;
}

function bp_abuse_save_report($creator_id, $user_id, $activity_type, $activity_id, $item_id) {
	global $wpdb, $bp;
	if (intval($creator_id) > 0 && intval($user_id) > 0 && intval($activity_id) > 0) {
		$sql = $wpdb->prepare(
			"SELECT COUNT(*), `ignored` FROM {$wpdb->base_prefix}bp_abuse
			WHERE `ignored` IS NOT NULL AND `activity_id` = %d", intval($activity_id));
		$pre = $wpdb->get_row( $sql );
		$sql = $wpdb->prepare(
			"INSERT IGNORE INTO {$wpdb->base_prefix}bp_abuse (
				`creator_id`,
				`user_id`,
				`item_id`,
				`activity_id`,
				`activity_type`,
				`date`,
				`ignored`
			) VALUES ( %d, %d, %d, %d, %s, NOW(), "
			. (trim($pre->ignored)? "'".$pre->ignored."'": "NULL") . " )",
				intval($creator_id),
				intval($user_id),
				intval($item_id),
				intval($activity_id),
				$activity_type
			);
		$result = $wpdb->query( $sql );
		if ( !$result ) return false;

		$bp->abuse->report = array(
			'id' => $wpdb->insert_id,
			'creator_id' => $creator_id,
			'user_id' => $user_id,
			'item_id' => $item_id,
			'activity_id' => $activity_id,
			'activity_type' => $activity_type,
			);
		do_action( 'bp_abuse_save_report' );
		return $result;
	}
	else return false;
}

function bp_abuse_moderate($user_id, $activity_type, $activity_id, $item_id) {
	global $bp;
	if ('activity_comment' == $activity_type) {
		$activity = bp_activity_get_specific( array( 'activity_ids' => $activity_id ) );
		if ( count($activity['activities']) > 0 && ($activity = $activity['activities'][0])
		&& 'activity_comment' == $activity->type) {
			bp_activity_delete_comment($activity->item_id, $activity_id);
			bp_abuse_set_moderated($activity_id);
		}
	}
	else {
		switch ($activity_type) {
			case 'new_forum_topic':
			case 'new_forum_post':
				if (function_exists('bp_forums_delete_post'))
					bp_forums_delete_post( array('post_id' => $item_id) );
				break;
			case 'new_blog_comment':
				if (function_exists('wp_trash_comment')) {
					wp_trash_comment($item_id);
				}
				break;
			case 'bp_album_picture':
				$private_level = 6;
				if (function_exists('bp_album_get_pictures')) {
					$pictures = bp_album_get_pictures( array('id' => $item_id, 'owner_id' => $user_id, 'page'=>1, 'per_page'=>1) );
					if (count($pictures) > 0)
						bp_album_edit_picture($item_id, $pictures[0]->title, $pictures[0]->description, $private_level, false);
				}
		}
		$activity = bp_activity_get_specific( array( 'activity_ids' => $activity_id ) );
		if ( bp_activity_delete( array('id' => $activity_id, 'user_id' => $user_id) ) ) {
			bp_abuse_set_moderated($activity_id);
		}
	}
}
function bp_abuse_set_moderated($activity_id) {
	global $wpdb;
	$sql = $wpdb->prepare(
		"UPDATE {$wpdb->base_prefix}bp_abuse SET `moderated` = NOW()
		WHERE `activity_id` = %d", intval($activity_id)
		);
	$wpdb->query( $sql );
	do_action('bp_abuse_set_moderated', $activity_id);
}
function bp_abuse_send_notification($activity_id) {
	$bp_abuse_notify_email   = get_site_option( 'bp-abuse-notify-email' );
	$bp_abuse_notify_subject = get_site_option( 'bp-abuse-notify-subject' );
	if (trim($bp_abuse_notify_email) && intval($activity_id) > 0) {
		$activity_list = bp_activity_get_specific( array('activity_ids' => intval($activity_id)) );
		if ( count($activity_list['activities']) > 0 && ($activity = $activity_list['activities'][0]) ) {
			$message = $activity->type . (isset($activity->date_recorded)? ' ('.$activity->date_recorded.')': '')
				. " has been abused\n\n"
				. $activity->action . "\n\n"
				. $activity->content;
			wp_mail(trim($bp_abuse_notify_email), $bp_abuse_notify_subject, $message);
		}
	}
}

function bp_abuse_get_link( $activity_id, $activity_type = false, $activity_user_id = false ) {
	global $bp;
	//TODO: check and restore $activity_type and $activity_user_id if needed
	return apply_filters('bp_abuse_get_link'
			, '<a href="'.$bp->root_domain.'/'.$bp->abuse->slug.'/'.$activity_type.'/'.$activity_id
				.'/'.bp_abuse_get_link_hash($activity_id, $activity_type, $activity_user_id)
				.'" class="abuse">Report Abuse ('.bp_abuse_get_reports_count($activity_id, $activity_type).')</a>'
			, $activity_id, $activity_type, $activity_user_id );
}
function bp_abuse_get_link_hash( $activity_id, $activity_type = '', $activity_user_id = '' ) {
	return substr(md5($activity_id. $activity_type. $activity_user_id), 0, 8);
}
/////////

function bp_abuse_install() {
	global $bp,$wpdb;

	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";

	$sql[] = "CREATE TABLE {$wpdb->base_prefix}bp_abuse (
	            `id`            bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	            `creator_id`    bigint(20) UNSIGNED NOT NULL,
	            `user_id`       bigint(20) UNSIGNED NOT NULL,
	            `item_id`       bigint(20) UNSIGNED NOT NULL default '0',
	            `activity_id`   bigint(20) UNSIGNED NOT NULL,
	            `activity_type` varchar(50) NOT NULL,
	            `date`          datetime NOT NULL,
	            `moderated`     datetime default NULL,
	            `ignored`       datetime default NULL,
	            UNIQUE KEY `abuse_report` (`activity_id`, `creator_id`, `item_id`),
	            KEY `user_id` (`user_id`, `date`, `moderated`, `ignored`)
	            ) {$charset_collate};";

	require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );

	dbDelta($sql);
	update_site_option( 'bp-abuse-db-version', BP_ABUSE_DB_VERSION );
	if ( '' == get_site_option('bp-abuse-auto-moderate') )
		update_site_option( 'bp-abuse-auto-moderate', 1 );
	if ( '' == get_site_option('bp-abuse-num-reports-to-auto-moderate') )
		update_site_option( 'bp-abuse-num-reports-to-auto-moderate', 5 );
}
register_activation_hook( __FILE__, 'bp_abuse_install' );

function bp_abuse_check_installed() {
	global $wpdb, $bp;

	if ( !current_user_can('install_plugins') )
		return;

	if (!defined('BP_VERSION') || version_compare(BP_VERSION, '1.2','<')) {
		add_action('admin_notices', 'bp_abuse_compatibility_notices' );
		return;
	}
	if ( get_site_option( 'bp-abuse-db-version' ) < BP_ABUSE_DB_VERSION )
		bp_abuse_install();
}
add_action( 'admin_menu', 'bp_abuse_check_installed' );

function bp_abuse_add_admin_menu() {
	global $bp;
	if ( !$bp->loggedin_user->is_site_admin ) return false;
	require ( dirname( __FILE__ ) . '/admin.php' );
	add_submenu_page( 'bp-general-settings', 'BP Abuse', 'BP Abuse', 'manage_options', 'bp-abuse-settings', 'bp_abuse_admin' );
}
add_action( 'admin_menu', 'bp_abuse_add_admin_menu', 11 );

function bp_abuse_compatibility_notices() {
	$message = 'BuddyPress Abuse Department needs at least BuddyPress 1.2 to work.';
	if (!defined('BP_VERSION')) {
		$message .= ' Please install Buddypress';
	}elseif(version_compare(BP_VERSION, '1.2','<') ) {
		$message .= ' Your current version is '.BP_VERSION.' please updrade.';
	}
	echo '<div class="error fade"><p>'.$message.'</p></div>';
}

function bp_abuse_activate() {
	bp_abuse_check_installed();
	do_action( 'bp_abuse_activate' );
}
register_activation_hook( __FILE__, 'bp_abuse_activate' );

function bp_abuse_deactivate() {
	do_action( 'bp_abuse_deactivate' );
}
register_deactivation_hook( __FILE__, 'bp_abuse_deactivate' );

?>

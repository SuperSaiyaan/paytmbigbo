<?php

function bp_abuse_activity_link() {
	global $bp;
	$ignore_activity_types = array('thumb','new_member','created_group','joined_group','activity_liked','friendship_created','friendship_accepted');
	if ( is_user_logged_in() ) {
		$activity_user_id = bp_get_activity_user_id();
		$activity_type = bp_get_activity_type();
		if ( $activity_user_id == $bp->loggedin_user->id
		|| FALSE !== array_search($activity_type, $ignore_activity_types)
		|| FALSE !== array_search(bp_core_get_username($activity_user_id), get_site_option( 'site_admins', array('admin') ))
		) return;
		echo bp_abuse_get_link( bp_get_activity_id(), $activity_type, $activity_user_id );
	}
}
add_action( 'bp_activity_entry_meta', 'bp_abuse_activity_link' );

function bp_abuse_activity_comment_link( $content ) {
	global $bp;
	$re = '[(<a href="#acomment-(\d+)" class="acomment-reply" id="acomment-reply-\d+">Reply</a>)]sim';
	if ( is_user_logged_in() && preg_match_all($re, $content, $matches, PREG_PATTERN_ORDER)
	&& isset($matches[2]) && is_array($matches[2])) {
		foreach ($matches[2] as $activity_id) {
			$activity = bp_activity_get_specific( array( 'activity_ids' => $activity_id ) );
			if (!empty($activity['activities'][0])) {
				$activity_user_id = $activity['activities'][0]->user_id;
				if ($activity_user_id == $bp->loggedin_user->id
				|| FALSE !== array_search(bp_core_get_username($activity_user_id), get_site_option( 'site_admins', array('admin') ))
				) continue;
				$content = preg_replace(
					str_replace('(\d+)', $activity_id, $re),
					'\\1 &middot; ' . bp_abuse_get_link( $activity_id, $activity['activities'][0]->type, $activity_user_id ),
					$content);
			}
		}
	}
	return $content;
}
add_action( 'bp_activity_get_comments', 'bp_abuse_activity_comment_link' );

function bp_abuse_topic_post_link( $content ) {
	global $bp, $topic_template;
	$link = '';
	if (FALSE === array_search(bp_core_get_username($topic_template->post->poster_id), get_site_option( 'site_admins', array('admin') ))
	&& ! bp_get_the_topic_post_is_mine() ) {
		$link = '<div class="admin-links2 abuse">'
			. bp_abuse_get_link(bp_get_the_topic_post_id(), $bp->abuse->topic_post_slug, $topic_template->post->poster_id)
			. '</div>';
	}
	return $content.$link;
}
add_action( 'bp_get_the_topic_post_content', 'bp_abuse_topic_post_link' );

// Admin moderation link
function bp_abuse_admin_moderation_link() {
	global $bp;
	if ( ! ($bp->loggedin_user->is_site_admin && bp_is_my_profile()) ) return false;
	echo '<a href="'.site_url().'/wp-admin/admin.php?page=bp-abuse-settings'.'">Moderate</a>';
}
add_action( 'bp_members_directory_member_types', 'bp_abuse_admin_moderation_link' );
?>

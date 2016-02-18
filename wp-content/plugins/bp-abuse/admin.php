<?php
function bp_abuse_admin() {
	global $wpdb, $bp;
	$version = BP_ABUSE_VERSION;
	$action = site_url() . '/wp-admin/admin.php?page=bp-abuse-settings';

	$msg = '';
	if ( isset( $_POST['submit'] ) && check_admin_referer('bp-abuse-settings') ) {
		update_site_option( 'bp-abuse-auto-moderate', $_POST['bp_abuse_auto_moderate'] );
		update_site_option( 'bp-abuse-num-reports-to-auto-moderate', $_POST['bp_abuse_num_reports_to_auto_moderate'] );
		update_site_option( 'bp-abuse-notify-email', $_POST['bp_abuse_notify_email'] );
		update_site_option( 'bp-abuse-notify-subject', $_POST['bp_abuse_notify_subject'] );
		$msg = '<div id="message" class="updated fade"><p>Settings Updated.</p></div>';
	}
	elseif ( isset( $_POST['ignore_reports'] ) && check_admin_referer('bp-abuse-reports')) {
		if ( isset( $_POST['activity'] ) && is_array($_POST['activity']) && count($_POST['activity']) > 0 ) {
			$marked = $wpdb->query(
				"UPDATE {$wpdb->base_prefix}bp_abuse SET `ignored` = NOW()
				WHERE `activity_id` IN ('".implode("','",array_map('intval',$_POST['activity']))."')"
				);
			$msg = '<div id="message" class="updated fade"><p>'.intval($marked).' Reports have been reverted.</p></div>';
		}
	}
	elseif ( isset( $_POST['del_activity'] ) && check_admin_referer('bp-abuse-reports')) {
		if ( isset( $_POST['activity'] ) && is_array($_POST['activity']) && count($_POST['activity']) > 0 ) {
			$activities = $wpdb->get_results(
				"SELECT DISTINCT `activity_id`,`activity_type`,`user_id`,`item_id`
				FROM {$wpdb->base_prefix}bp_abuse
				WHERE `activity_id` IN ('".implode("','",array_map('intval',$_POST['activity']))."')"
				);
			foreach ($activities as $row) {
				bp_abuse_moderate($row->user_id, $row->activity_type, $row->activity_id, $row->item_id);
			}
			$msg = '<div id="message" class="updated fade"><p>'.count($activities).' Abused Posts have been Deleted.</p></div>';
		}
	}
	elseif ( isset( $_POST['del_users'] ) && check_admin_referer('bp-abuse-spammers') && current_user_can('delete_users')) {
		if ( isset( $_POST['users'] ) && is_array($_POST['users']) && count($_POST['users']) > 0 ) {
			$del_count = 0;
			foreach (array_map('intval',$_POST['users']) as $id) {
				if ( ! current_user_can('delete_user', $id) || $id == $bp->loggedin_user->id ) continue;
				wp_delete_user($id);
				++$del_count;
			}
			$msg = '<div id="message" class="updated fade"><p>'.$del_count.' Users (spammers) Deleted.</p></div>';
		}
	}

echo <<<PAGE_HEADER
<div class="wrap">
	<h2>BP Abuse Department Settings | Version $version</h2>
	<br />$msg
PAGE_HEADER;

	$bp_abuse_auto_moderate = get_site_option('bp-abuse-auto-moderate');
	$bp_abuse_num_reports_to_auto_moderate = get_site_option( 'bp-abuse-num-reports-to-auto-moderate' );
	$bp_abuse_notify_email = get_site_option( 'bp-abuse-notify-email' );
	$bp_abuse_notify_subject = get_site_option( 'bp-abuse-notify-subject' );
	if ('' == $bp_abuse_notify_subject) $bp_abuse_notify_subject = '[BP-Abuse] an abuse report has been placed';
	$checked = intval($bp_abuse_auto_moderate) > 0? 'checked': '';

echo <<<SETTINGS_FORM
	<form action="$action" method="post">
		<h3>Abuse Notifications</h3>
		<table class="form-table" style="width:auto;">
			<tr>
				<th scope="row" style="text-align:right" noWrap="noWrap"><label for="bp_abuse_notify_email">Email address to notify on an abuse report</label><div style="font-weight:normal;font-style:italic;font-size:85%;color:gray;">(enter several addresses separated by commas)</div></th>
				<td>
					<input name="bp_abuse_notify_email" type="text" size="80" id="bp_abuse_notify_email" value="$bp_abuse_notify_email" />
				</td>
			</tr>
			<tr>
				<th scope="row" style="text-align:right" noWrap="noWrap"><label for="bp_abuse_notify_subject">Subject of the notification messages</label></th>
				<td>
					<input name="bp_abuse_notify_subject" type="text" size="80" id="bp_abuse_notify_subject" value="$bp_abuse_notify_subject" />
				</td>
			</tr>
		</table>
		<h3>Auto Moderation</h3>
		<table class="form-table" style="width:auto;">
			<tr valign="top">
				<th scope="row" noWrap="noWrap">Auto-Delete forum and activity posts and comments after abuse reports</th>
				<td width="120"><label for="bp_abuse_auto_moderate"><input name="bp_abuse_auto_moderate" type="hidden" value="0" />
					<input name="bp_abuse_auto_moderate" type="checkbox" id="bp_abuse_auto_moderate" value="1" $checked /> Yes</label>
				</td>
			</tr>
			<tr>
				<th scope="row" style="text-align:right" noWrap="noWrap"><label for="bp_abuse_num_reports_to_auto_moderate">Number of abuse reports to&nbsp;auto-moderate a post</label></th>
				<td>
					<input name="bp_abuse_num_reports_to_auto_moderate" type="text" size="4" id="bp_abuse_num_reports_to_auto_moderate" value="$bp_abuse_num_reports_to_auto_moderate" />
				</td>
			</tr>
			<tr><td>&nbsp;</td>
				<td><input type="submit" name="submit" value="Save Changes" /></td>
			</tr>
		</table>
SETTINGS_FORM;
wp_nonce_field( 'bp-abuse-settings' );
echo '</form>';

	$stats = array(
		'today'       => array('by_date'=>'today'),
		'yesterday'   => array('by_date'=>'yesterday'),
		'last 7 days' => array('by_date'=>'last 7 days'),
	);
	$reps = $wpdb->get_results(
		"(SELECT COUNT(*) AS `reports`, COUNT(DISTINCT `activity_id`) AS `posts`, `date` AS `sort`,
		CASE
			WHEN DATE_FORMAT(`date`,'%Y-%m-%d') = DATE_FORMAT(CURDATE(),'%Y-%m-%d') THEN 'today'
			WHEN DATE_FORMAT(`date`,'%Y-%m-%d') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY),'%Y-%m-%d') THEN 'yesterday'
		END AS `by_date`
		FROM {$wpdb->base_prefix}bp_abuse
		WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND `ignored` IS NULL
		GROUP BY `by_date` ORDER BY `sort` DESC)
			UNION
		(SELECT COUNT(*) AS `reports`, COUNT(DISTINCT `activity_id`) AS `posts`, `date` AS `sort`,
		'last 7 days' AS `by_date`
		FROM {$wpdb->base_prefix}bp_abuse
		WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND `ignored` IS NULL
		GROUP BY `by_date` ORDER BY `sort` DESC)
			UNION
		(SELECT COUNT(*) AS `reports`, COUNT(DISTINCT `activity_id`) AS `posts`, `date` AS `sort`,
		DATE_FORMAT(`date`,'%M of %Y') AS `by_date`
		FROM {$wpdb->base_prefix}bp_abuse
		WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) AND `ignored` IS NULL
		GROUP BY `by_date` ORDER BY `sort` DESC)");
	foreach ($reps as $row) {
		$by = $row->by_date;
		if (!isset($stats[$by])) $stats[$by] = array();
		$stats[$by]['by_date'] = $row->by_date;
		$stats[$by]['reports'] = $row->reports;
		$stats[$by]['posts']   = $row->posts;
	}
	$mods = $wpdb->get_results(
		"(SELECT COUNT(DISTINCT `activity_id`) AS `posts`, COUNT(DISTINCT `user_id`) AS `users`, `moderated` AS `sort`,
		CASE
			WHEN DATE_FORMAT(`moderated`,'%Y-%m-%d') = DATE_FORMAT(CURDATE(),'%Y-%m-%d') THEN 'today'
			WHEN DATE_FORMAT(`moderated`,'%Y-%m-%d') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY),'%Y-%m-%d') THEN 'yesterday'
		END AS `by_date`
		FROM {$wpdb->base_prefix}bp_abuse
		WHERE `moderated` >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
		GROUP BY `by_date` ORDER BY `sort` DESC)
			UNION
		(SELECT COUNT(DISTINCT `activity_id`) AS `posts`, COUNT(DISTINCT `user_id`) AS `users`, `moderated` AS `sort`,
		'last 7 days' AS `by_date`
		FROM {$wpdb->base_prefix}bp_abuse
		WHERE `moderated` >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
		GROUP BY `by_date` ORDER BY `sort` DESC)
			UNION
		(SELECT COUNT(DISTINCT `activity_id`) AS `posts`, COUNT(DISTINCT `user_id`) AS `users`, `moderated` AS `sort`,
		DATE_FORMAT(`moderated`,'%M of %Y') AS `by_date`
		FROM {$wpdb->base_prefix}bp_abuse
		WHERE `moderated` IS NOT NULL AND `moderated` >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
		GROUP BY `by_date` ORDER BY `sort` DESC)");
	foreach ($mods as $row) {
		$by = $row->by_date;
		if (!isset($stats[$by])) $stats[$by] = array();
		$stats[$by]['by_date'] = $row->by_date;
		$stats[$by]['mods']    = $row->posts;
		$stats[$by]['users']   = $row->users;
	}
	$stat_rows = implode("\n", array_map(create_function('$stats'
		,'return "<tr><th>{$stats[by_date]}</th><td style=\"text-align:right;\">{$stats[reports]}</td><td style=\"text-align:right;\">{$stats[posts]}</td><td style=\"text-align:right;\">{$stats[mods]}</td><td style=\"text-align:right;\">{$stats[users]}</td></tr>";'
		), array_values($stats)));

	$sql = $wpdb->prepare(
		"SELECT COUNT(*) AS `reports`, COUNT(DISTINCT `activity_id`) AS `posts`,
		COUNT(DISTINCT IF(ISNULL(`moderated`),NULL,`activity_id`)) AS `mods`,
		COUNT(DISTINCT IF(ISNULL(`moderated`),NULL,`user_id`)) AS `users`
		FROM {$wpdb->base_prefix}bp_abuse WHERE `ignored` IS NULL" );
	$total = $wpdb->get_row( $sql );

echo <<<STATS_TABLE
	<h3>Stats</h3>
	<table class="widefat">
	<thead>
		<tr>
			<th scope="col">&nbsp;</th>
			<th scope="col" style="text-align:right;">Abuse Reports</th>
			<th scope="col" style="text-align:right;">Reported Posts</th>
			<th scope="col" style="text-align:right;">Moderated Posts</th>
			<th scope="col" style="text-align:right;">Moderated Users</th>
		</tr>
	</thead>
	<tbody>$stat_rows</tbody>
	<tfoot>
		<tr>
			<th>Total</th>
			<th style="text-align:right;">{$total->reports}</th>
			<th style="text-align:right;">{$total->posts}</th>
			<th style="text-align:right;">{$total->mods}</th>
			<th style="text-align:right;">{$total->users}</th>
		</tr>
	</tfoot>
	</table>
STATS_TABLE;

echo <<<JAVASCRIPT
<script type="text/javascript">
<!--//
function toggleCheckboxes(box,fieldName) {
	if (box && box.form) {
		var theForm = box.form;
		for (var i = 0; theForm.elements.length > i; ++i) {
			if ('INPUT' == theForm.elements[i].tagName
			&& fieldName == theForm.elements[i].name
			&& 'checkbox' == theForm.elements[i].type)
				theForm.elements[i].checked = box.checked;
		}
	}
}
//-->
</script>
JAVASCRIPT;

echo '<table style="width:100%;margin-top:2em;"><tr valign="top"><td style="padding:1ex;">';

	$reports = $wpdb->get_results(
		"SELECT `abuse`.`activity_id`, `abuse`.`reports`, `activity`.`action`, `activity`.`content`,
			DATE_FORMAT(`activity`.`date_recorded`,'%Y-%m-%d %k:%i') AS `date`
		FROM (
			SELECT `activity_id`, COUNT(DISTINCT `id`) AS `reports`
			FROM {$wpdb->base_prefix}bp_abuse WHERE `moderated` IS NULL AND `ignored` IS NULL GROUP BY `activity_id`
		) AS `abuse`
		LEFT JOIN {$bp->activity->table_name} AS `activity` ON `activity`.`id` = `abuse`.`activity_id`
		WHERE `activity`.`date_recorded` IS NOT NULL
		ORDER BY `abuse`.`reports` DESC, `activity`.`date_recorded` DESC ");
	$reports_rows = implode("\n", array_map(create_function('$r'
		,'return "<tr><td><input type=\"checkbox\" name=\"activity[]\" value=\"{$r->activity_id}\" /></td><td style=\"text-align:right;\">{$r->reports}</td><td><em>({$r->date}) {$r->action}<div></em>{$r->content}</div></td></tr>";'
		), $reports));

echo <<<REPORTS_FORM
	<form action="$action" method="post">
	<table class="widefat">
	<thead>
		<tr>
			<th scope="col" colspan="3">Last Abuse Reports</th>
		</tr>
		<tr class="header alt">
			<td width="20"><input type="checkbox" onclick="toggleCheckboxes(this, 'activity[]')" /></td>
			<td style="text-align:right;">Reports</td>
			<td noWrap="noWrap">On Activity</td>
		</tr>
	</thead>
	<tbody>$reports_rows</tbody>
	<tfoot>
		<tr>
			<th style="text-align:center">&uarr;</th>
			<th colspan="2">
				<input type="submit" name="del_activity" value="Delete Posts by selected reports" />
				<input type="submit" name="ignore_reports" value="Mark Posts as not abusive" />
			</th>
		</tr>
	</tfoot>
	</table>
REPORTS_FORM;
wp_nonce_field( 'bp-abuse-reports' );
echo '</form>';

echo '</td><td style="padding:1ex;width:450px;">';

	$num_of_spammers = 10;
	$spammers = $wpdb->get_results(
		"SELECT `s`.`user_id`, `s`.`mods`, `u`.`user_login`, `u`.`display_name`, COUNT(`a`.`id`) AS `posts`
		FROM (
			SELECT `user_id`, COUNT(DISTINCT `activity_id`) AS `mods`
			FROM {$wpdb->base_prefix}bp_abuse WHERE `moderated` IS NOT NULL GROUP BY `user_id`
		) AS `s`
		LEFT JOIN {$wpdb->base_prefix}users AS `u` ON `u`.`ID` = `s`.`user_id`
		LEFT JOIN {$bp->activity->table_name} AS `a` ON `a`.`user_id` = `s`.`user_id`
		WHERE `u`.`user_login` IS NOT NULL
		GROUP BY `s`.`user_id` ORDER BY `s`.`mods` DESC LIMIT $num_of_spammers ");
	$spammers_rows = implode("\n", array_map(create_function('$u'
		,'return "<tr><td><input type=\"checkbox\" name=\"users[]\" value=\"{$u->user_id}\" /></td><td>{$u->display_name} (<a href=\"".bp_core_get_user_domain($u->user_id)."\">{$u->user_login}</a>)</td><td style=\"text-align:right;\">{$u->posts}</td><td style=\"text-align:right;\">{$u->mods}</td></tr>";'
		), $spammers));

echo <<<SPAMMERS_FORM
	<form action="$action" method="post">
	<table class="widefat">
	<thead>
		<tr>
			<th scope="col" colspan="4">Top $num_of_spammers Spammers</th>
		</tr>
		<tr class="header alt">
			<td width="20"><input type="checkbox" onclick="toggleCheckboxes(this, 'users[]')" /></td>
			<td>User</td>
			<td style="text-align:right;" noWrap="noWrap">Live posts</td>
			<td style="text-align:right;">Moderated</td>
		</tr>
	</thead>
	<tbody>$spammers_rows</tbody>
	<tfoot>
		<tr>
			<th style="text-align:center">&uarr;</th>
			<th colspan="3"><input type="submit" name="del_users" value="Delete selected" /></th>
		</tr>
	</tfoot>
	</table>
SPAMMERS_FORM;
wp_nonce_field( 'bp-abuse-spammers' );
echo '</form><div>&nbsp;</div><div>&nbsp;</div>';

	$num_of_escalators = 10;
	$escalators = $wpdb->get_results(
		"SELECT `s`.`creator_id`, `s`.`reports`, `s`.`num_ignored`, `u`.`user_login`, `u`.`display_name`
		FROM (
			SELECT `creator_id`, COUNT(DISTINCT `activity_id`) AS `reports`, COUNT(`ignored`) AS `num_ignored`
			FROM {$wpdb->base_prefix}bp_abuse GROUP BY `creator_id` HAVING `num_ignored` > 0
		) AS `s`
		LEFT JOIN {$wpdb->base_prefix}users AS `u` ON `u`.`ID` = `s`.`creator_id`
		WHERE `u`.`user_login` IS NOT NULL
		GROUP BY `s`.`creator_id` ORDER BY `s`.`num_ignored` DESC LIMIT $num_of_escalators ");
	$escalators_rows = implode("\n", array_map(create_function('$u'
		,'return "<tr><td><input type=\"checkbox\" name=\"users[]\" value=\"{$u->creator_id}\" /></td><td>{$u->display_name} (<a href=\"".bp_core_get_user_domain($u->creator_id)."\">{$u->user_login}</a>)</td><td style=\"text-align:right;\">{$u->reports}</td><td style=\"text-align:right;\">{$u->num_ignored}</td></tr>";'
		), $escalators));

echo <<<ESCALATORS_FORM
	<form action="$action" method="post">
	<table class="widefat">
	<thead>
		<tr>
			<th scope="col" colspan="4">Top $num_of_escalators Users who exaggerate abuse</th>
		</tr>
		<tr class="header alt">
			<td width="20"><input type="checkbox" onclick="toggleCheckboxes(this, 'users[]')" /></td>
			<td>User</td>
			<td style="text-align:right;" noWrap="noWrap">Reports</td>
			<td style="text-align:right;">Ignored</td>
		</tr>
	</thead>
	<tbody>$escalators_rows</tbody>
	<!--tfoot>
		<tr>
			<th style="text-align:center">&uarr;</th>
			<th colspan="3"><input type="submit" name="del_users" value="Delete selected" /></th>
		</tr>
	</tfoot-->
	</table>
ESCALATORS_FORM;
wp_nonce_field( 'bp-abuse-escalators' );
echo '</form>';
echo '</td></tr></table>';

echo <<<PAGE_FOOTER
</div>
PAGE_FOOTER;
}
?>

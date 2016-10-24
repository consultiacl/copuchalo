<?php
// The source code packaged with this file is Free Software, Copyright (C) 2005 by
// Ricardo Galli <gallir at uib dot es>.
// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
//		http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".

class Report extends LCPBase
{

	const REPORT_TYPE_LINK    = 'link';
	const REPORT_TYPE_COMMENT = 'comment';
	const REPORT_TYPE_POST    = 'post';

	const REPORT_STATUS_PENDING = 'pending';
	const REPORT_STATUS_DEBATE = 'debate';
	const REPORT_STATUS_PENALIZED = 'penalized';
	const REPORT_STATUS_DISMISSED = 'dismissed';

	const REPORT_REASON_VIOLATES_RULES = 'violate_rules';
	const REPORT_REASON_INAPPROPRIATE_CONTENT = 'inappropriate_content';
	const REPORT_REASON_SPAM = 'spam';
	const REPORT_REASON_INSULT_THREAT = 'insult';
	const REPORT_REASON_INCITES_HATRED = 'incites_hatred';
	const REPORT_REASON_ADVERTISING = 'advertising';
	const REPORT_REASON_VIOLENCE_OR_PORN = 'violence_porn';
	const REPORT_REASON_REVEILS_PRIVATE_DATA = 'private_data';
	const REPORT_REASON_BREACH_LEGALITY = 'legality';

	const SQL_BASE = " count(*) as report_num, report_id as id, report_type as type, report_ref_id as ref_id, report_date as date, report_modified as modified, report_status as status, report_reason as reason, reporters.user_id as reporter_id, reporters.user_level as reporter_user_level, reporters.user_login as reporter_user_login, authors.user_id as author_id, authors.user_level as author_user_level, authors.user_login as author_user_login, revisors.user_id as revisor_id, revisors.user_level as revisor_user_level, revisors.user_login as revisor_user_login, report_ip as ip, comment_order, comment_link_id, links.link_uri as comment_link_uri, lnk.link_uri as uri FROM reports
	LEFT JOIN users as reporters on (reporters.user_id = report_user_id)
	LEFT JOIN comments on (comments.comment_id = reports.report_ref_id)
	LEFT JOIN links on (comments.comment_link_id = links.link_id)
	LEFT JOIN links as lnk on (lnk.link_id = reports.report_ref_id)
	LEFT JOIN posts on (posts.post_id = reports.report_ref_id)
	LEFT JOIN users as authors on (authors.user_id = comments.comment_user_id)
	LEFT JOIN users as revisors on (revisors.user_id = reports.report_revised_by) ";

	// sql fields to build an object from mysql
	public $id = 0;
	public $type = '';
	public $date;
	public $status = Report::REPORT_STATUS_PENDING;
	public $reason = '';
	public $reporter_id;
	public $author_id;
	public $ref_id;
	public $revised_by;
	public $modified = null;
	public $ip;

	static function from_db($id)
	{
		global $db;

		$sql = "SELECT" . Report::SQL_BASE . "WHERE report_id = $id";
		return $db->get_object($sql, 'Report');
	}

	static function is_valid_reason($reason)
	{
		return in_array($reason, array(
			self::REPORT_REASON_INAPPROPRIATE_CONTENT,
			self::REPORT_REASON_SPAM,
			self::REPORT_REASON_VIOLATES_RULES,
			self::REPORT_REASON_INSULT_THREAT,
			self::REPORT_REASON_INCITES_HATRED,
			self::REPORT_REASON_ADVERTISING,
			self::REPORT_REASON_VIOLENCE_OR_PORN,
			self::REPORT_REASON_REVEILS_PRIVATE_DATA,
			self::REPORT_REASON_BREACH_LEGALITY
		));
	}

	static function check_min_karma()
	{
		global $current_user, $globals;

		return ($globals['min_karma_for_report'] > $current_user->karma);

	}

	static function already_reported($report_ref_id, $report_type)
	{
		global $db, $current_user;

		$sql = "select count(*) from reports where report_ref_id=$report_ref_id and report_user_id={$current_user->user_id} and report_type='$report_type'";
		return (bool) $db->get_var($sql);
	}

	static function check_report_user_limit()
	{
		global $db, $current_user, $globals;

		$sql = "select count(*) from reports where report_user_id={$current_user->user_id} and (NOW() - report_date) < 86400";  // 24h
		$number_reports = $db->get_var($sql);

		return $number_reports < $globals['max_reports'];
	}

	static function get_total_in_status($status)
	{
		global $db;

		$sql = "select count(*) from reports where report_status='$status'";
		return $db->get_var($sql);
	}

	public function store()
	{
		global $db, $globals;

		if (!$this->date) $this->date = $globals['now'];
		$report_date = $this->date;
		$report_type = $this->type;
		$report_reason = $this->reason;
		$report_user_id = $this->reporter_id;
		$report_ref_id = $this->ref_id;
		$report_status = $this->status;
		$report_modified = $this->modified;
		$report_revised_by = $this->revised_by;
		$report_ip = $this->ip = $globals['user_ip'];

		if ($this->id === 0) {
			$r = $db->query("INSERT INTO reports (report_date, report_type, report_reason, report_user_id, report_ref_id, report_status, report_modified, report_revised_by, report_ip) VALUES(FROM_UNIXTIME($report_date), '$report_type', '$report_reason', $report_user_id, $report_ref_id, '$report_status', null, null, '$report_ip')");
			$this->id = $db->insert_id;
		} else {
			$r = $db->query("UPDATE reports set report_date=FROM_UNIXTIME($report_date), report_type='$report_type', report_reason='$report_reason', report_user_id=$report_user_id, report_ref_id=$report_ref_id, report_status='$report_status', report_modified=FROM_UNIXTIME($report_modified), report_revised_by=$report_revised_by, report_ip='$report_ip' where report_id=$this->id");
		}

		if (!$r) {
			$db->rollback();
			return false;
		}

		$db->commit();
		return true;
	}
}


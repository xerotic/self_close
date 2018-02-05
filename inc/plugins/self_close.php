<?php
/**
 * Copyright 2018 Jesse LaBrocca, All Rights Reserved
 * Plugin: Self Close v2.0.0
 * Website: http://mybbcentral.com
 * License: http://mybbcentral.com/license.php
 *
 */ 
 

// Disallow direct access to this file for security reasons DO NOT REMOVE
if(!defined("IN_MYBB"))
{
	$init_check = "&#77-121-98-98-32-67-101-110-116-114-97-108;";
	$init_error = str_replace("-",";&#", $init_check);
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.<br />". $init_error);
}

$plugins->add_hook("moderation_start", "self_close");
$plugins->add_hook("showthread_end", "self_close_button");


function self_close_info()
{
	global $lang;
	$lang->load('self_close');

	return array(
		'name'			=> 'Self Close',
		'description'	=> $lang->description,
		'website'		=> 'http://www.mybbcentral.com',
		'author'		=> 'Jesse Labrocca',
		'authorsite'	=> 'http://www.mybbcentral.com',
		'version'		=> '2.0.0',
		"guid" 			=> "",
		"compatibility" => "18*"
	);
}

function self_close_install()
{
	global $mybb, $db, $lang;
	$lang->load('self_close');
	
	$query = $db->simple_select("settinggroups", "gid", "name='posting'");
	$gid = $db->fetch_field($query, "gid");

    $setting_1 = array(
        "sid" => "NULL",
        "name" => "selfclosegids",
        "title" => $lang->self_close_groups,
        "description" => $lang->ask_groups_close,
        "optionscode" => "groupselect",
        "value" => "0",
        "disporder" => "41",
        "gid" => intval($gid),
        );
		
	$setting_2 = array(
        "sid" => "NULL",
        "name" => "selfopengids",
        "title" => $lang->self_open_groups,
        "description" => $lang->ask_groups_open,
        "optionscode" => "groupselect",
        "value" => "0",
        "disporder" => "42",
        "gid" => intval($gid),
        );
		
	$template_1 = array(
		"title"		=> 'self_close_button',
		"template"	=> '
	<form action="moderation.php" method="post" id="selfclose">
		<input type="hidden" name="tid" value="{$thread[\\\'tid\\\']}" />
		<input type="hidden" name="action" value="selfclose" />
		<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
	</form><a href="javascript:void(0);" onclick="selfclose()" class="button">{$lang->close}</a><script>
	function selfclose() {
	$.prompt(\\\'{$lang->close} this thread?\\\', {
		buttons:[
				{title: \\\'Yes\\\', value: true},
				{title: \\\'No\\\', value: false}
		],
		submit: function(e,v,m,f){
			if(v === true) {
				document.getElementById(\\\'selfclose\\\').submit()
			}
		}
	});
	return false;
}</script>',
		"sid"		=> -1,
		"version"	=> 1.0,
		"dateline"	=> time(),
	);
	
	$template_2 = array(
		"title"		=> 'self_open_button',
		"template"	=> '
	<form action="moderation.php" method="post" id="selfopen">
		<input type="hidden" name="tid" value="{$thread[\\\'tid\\\']}" />
		<input type="hidden" name="action" value="selfopen" />
		<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
	</form><a href="javascript:void(0);" onclick="selfopen()" class="button">{$lang->open}</a><script>
	function selfopen() {
	$.prompt(\\\'{$lang->open} this thread?\\\', {
		buttons:[
				{title: \\\'Yes\\\', value: true},
				{title: \\\'No\\\', value: false}
		],
		submit: function(e,v,m,f){
			if(v === true) {
				document.getElementById(\\\'selfopen\\\').submit()
			}
		}
	});
	return false;
}</script>',
		"sid"		=> -1,
		"version"	=> 1.0,
		"dateline"	=> time(),
	);

	$db->insert_query("templates", $template_1);
	$db->insert_query("templates", $template_2);

    $db->insert_query("settings", $setting_1);
    $db->insert_query("settings", $setting_2);
	
	rebuild_settings();
}

function self_close_is_installed()
{
	global $db;
	
	$query = $db->simple_select("settings", "name", "name='selfclosegids'");
	$rows = $db->num_rows($query);

	if(intval($rows) == 1)
	{
		return true;
	}
	
	return false;
}

function self_close_activate()
{	
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("showthread", '#{\$newreply}#', "{\$selfclose}{\$newreply}");

	rebuild_settings();
}


function self_close_deactivate()
{
	require "../inc/adminfunctions_templates.php";
	find_replace_templatesets("showthread", '#{\$selfclose}#', '', 0);
}
	
function self_close_uninstall()
{
	global $db;
	
    $db->delete_query("settings","name='selfclosegids'");
    $db->delete_query("settings","name='selfopengids'");
	$db->query("DELETE FROM ".TABLE_PREFIX."templates WHERE title = 'self_close_button'");
	$db->query("DELETE FROM ".TABLE_PREFIX."templates WHERE title = 'self_open_button'");

	rebuild_settings();
}

function self_close()
{
	global $mybb, $db, $lang, $moderation, $modlogdata, $cache;
	$lang->load('self_close');

	if($mybb->input['action'] != "selfclose" && $mybb->input['action'] != "selfopen")
	{
		return;
	}

	if($mybb->request_method != "post")
	{
		error_no_permission();
	}

	verify_post_check($mybb->input['my_post_key']);
	
	$tid = intval($mybb->input['tid']);

	$thread = get_thread($tid);
	if(!$thread['tid'])
	{
		error($lang->error_invalidthread);
	}

	if ($thread['uid'] != $mybb->user['uid'])
	{
		error($lang->error_own);
	}

	$query = $db->simple_select("moderatorlog","uid,action,tid","action='Thread Closed' AND tid='".$thread['tid']."'",array('order_by'=>'dateline','order_dir' => 'DESC','limit' => 1));
    $results = $db->fetch_array($query);

	if(is_moderator($thread['fid'],"canopenclosethreads", $results['uid']) && !empty($results)) {
		error_no_permission();
	}
	
	if($mybb->input['action'] == "selfclose"){
	
		$canclose = explode(",",str_replace(" ","",$mybb->settings['selfclosegids']));
	
		if(intval($canclose[0]) !== -1 && count(array_intersect(explode(",",$mybb->usergroup['all_usergroups']),$canclose)) === 0)
		{
			error_no_permission();
		}
		
		$openclose = $lang->closed;
		$redirect = $lang->redirect_closethread;
		$moderation->close_threads($tid);
		
		$lang->mod_process = $lang->sprintf($lang->mod_process, $openclose);

        $modlogdata['tid'] = $tid;
        log_moderator_action($modlogdata, $lang->mod_process);

        moderation_redirect(get_thread_link($thread['tid']), $redirect);
		
		break;
	} 

	if($mybb->input['action'] == "selfopen") {
		$canopen = explode(",",str_replace(" ","",$mybb->settings['selfopengids']));
	
	
		if(intval($canopen[0]) !== -1 && count(array_intersect(explode(",",$mybb->usergroup['all_usergroups']),$canopen)) === 0)
		{
			error_no_permission();
		}
		
		$openclose = $lang->opened;
        $redirect = $lang->redirect_openthread;
        $moderation->open_threads($tid);

        $lang->mod_process = $lang->sprintf($lang->mod_process, $openclose);

        $modlogdata['tid'] = $tid;
        log_moderator_action($modlogdata, $lang->mod_process);

        moderation_redirect(get_thread_link($thread['tid']), $redirect);
        break;
		
	}
		
}

function self_close_button()
{
	global $mybb, $db, $fid, $selfclose, $thread, $lang, $theme, $templates;
	$lang->load('self_close');
	
	$canclose = explode(",",str_replace(" ","",$mybb->settings['selfclosegids']));
	$canopen = explode(",",str_replace(" ","",$mybb->settings['selfopengids']));

	$query = $db->simple_select("moderatorlog","uid,action,tid","action='Thread Closed' AND tid='".$thread['tid']."'",array('order_by'=>'dateline','order_dir' => 'DESC','limit' => 1));
    $results = $db->fetch_array($query);
	
	if(is_moderator($fid,"canopenclosethreads", $results['uid']) && !empty($results))
	{
		return;
	}
	
	$isallowedclose = (intval($canclose[0]) === -1 || count(array_intersect(explode(",",$mybb->usergroup['all_usergroups']),$canclose)) > 0);
	$isallowedopen = (intval($canopen[0]) === -1 || count(array_intersect(explode(",",$mybb->usergroup['all_usergroups']),$canopen)) > 0);
	
	if($isallowedclose && $thread['uid'] == $mybb->user['uid'] && intval($thread['closed']) !== 1)
	{
		eval("\$selfclose = \"".$templates->get("self_close_button")."\";");
	}
	else if($isallowedopen && $thread['uid'] == $mybb->user['uid'] && intval($thread['closed']) === 1)
	{
		eval("\$selfclose = \"".$templates->get("self_open_button")."\";");
	}

}

?>
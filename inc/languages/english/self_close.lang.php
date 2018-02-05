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

$l['close'] = "Close";
$l['close_thread'] = "Close Thread";
$l['open'] = "Open";
$l['open_thread'] = "Open Thread";
$l['description'] = "Allows groups to be assigned the ability to self close threads.";
$l['self_close_groups'] = "Self Close Groups";
$l['ask_groups_close'] = "Which groups are allowed to close their own threads?";
$l['self_open_groups'] = "Self Open Groups";
$l['ask_groups_open'] = "Which groups are allowed to open their own threads?";
$l['error_own'] = "You can only open/close your own threads.";


?>
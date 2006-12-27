<?php
/******************************
 * WoWRoster.net  Roster
 * Copyright 2002-2006
 * Licensed under the Creative Commons
 * "Attribution-NonCommercial-ShareAlike 2.5" license
 *
 * Short summary
 *  http://creativecommons.org/licenses/by-nc-sa/2.5/
 *
 * Full license information
 *  http://creativecommons.org/licenses/by-nc-sa/2.5/legalcode
 ******************************/

if ( !defined('ROSTER_INSTALLED') )
{
    exit('Detected invalid access to this file!');
}

// -[ Begin testing conditions for what we want to do ]-

if (array_key_exists('action',$_GET))
	$_GET['action'] = strtolower($_GET['action']);
else
	$_GET['action'] = 'altlist';

if ($_GET['action'] == 'install')
{
	$dbversion = '0.0.0';
	$action = 'install';
}
elseif (($_GET['action'] == 'upgrade') && (version_compare($dbversion,$fileversion,"<")))
{
	$action = 'install';
}
elseif ($_GET['action'] == 'upgrade') // but we are already up to date
{
	$action = 'cant_upgrade_message';
}
elseif (version_compare($dbversion,$fileversion,"<"))  // If the database version is older than the file version we need to produce notice we need to update
{
	if ($dbversion == '0.0.0')
		$action = 'install_message';
	else
		$action = 'upgrade_message';
}
else
{
	$action = $_GET['action'];
}

if (($action == 'install') || ($action == 'upgrade') || ($action == 'update') || ($action == 'config'))
{
	include($addonDir.'/login.php');
}

// -[ Begin switch for what we are going to do ]-
switch ($action) {
case 'install':
	include($addonDir.'/install.php');
	break;
case 'update':
	include($addonDir.'/update_wrap.php');
	break;
case 'config':
	include($addonDir.'/config.php');
	break;
case 'altlist':
	include($addonDir.'/altlist_wrap.php');
	break;
case 'debug':
	include($addonDir.'/altlist_wrap.php');
	print_r($addon_conf);
	break;
case 'install_message':
	die_quietly($wordings[$roster_conf['roster_lang']]['AltMonitor_install']."<br />\n".
	'<div style="text-align:center;"><span style="border:1px outset white; padding:2px 6px 2px 6px;"><a href="?roster_addon_name='.$_GET['roster_addon_name'].'&amp;action=install">Install</a></span></div>',
	$wordings[$roster_conf['roster_language']]['AltMonitor_install_page']);
	break;
case 'upgrade_message':
	die_quietly($wordings[$roster_conf['roster_lang']]['AltMonitor_upgrade']."<br />\n".
	'<table><tr><td align="center"><div style="text-align:center; border:1px outset white; padding:2px 6px 2px 6px;"><a href="?roster_addon_name='.$_GET['roster_addon_name'].'&amp;action=upgrade">Update</a></div>'.
	'<td align="center"><div style="text-align:center; border:1px outset white; padding:2px 6px 2px 6px;"><a href="?roster_addon_name='.$_GET['roster_addon_name'].'&amp;action=install">Install</a></div></table>',
	$wordings[$roster_conf['roster_language']]['AltMonitor_install_page']);
	break;
case 'cant_upgrade_message':
	die_quietly($wordings[$roster_conf['roster_lang']]['AltMonitor_no_upgrade']."<br />\n".
	'<div style="text-align:center;"><span style="border:1px outset white; padding:2px 6px 2px 6px;"><a href="?roster_addon_name='.$_GET['roster_addon_name'].'&amp;action=install">Reinstall</a></span></div>',
	$wordings[$roster_conf['roster_language']]['AltMonitor_install_page']);
	break;
default:
	die_quietly($wordings[$roster_conf['roster_lang']]['AltMonitor_NoAction'],'AltMonitor');
}
?>
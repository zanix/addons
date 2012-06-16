<?php
/**
 * WoWRoster.net WoWRoster
 *
 * english localisaton thx to zanix@wowroster.net
 *
 * LICENSE: Licensed under the Creative Commons
 *          "Attribution-NonCommercial-ShareAlike 2.5" license
 *
 * @copyright  2002-2007 WoWRoster.net
 * @license    http://creativecommons.org/licenses/by-nc-sa/2.5   Creative Commons "Attribution-NonCommercial-ShareAlike 2.5"
 * @version    SVN: $Id$
 * @link       http://www.wowroster.net
 * @package    ApiSync
*/

// -[ enUS Localization ]-

// Button names
$lang['async_button1']			= 'ApiSync Character|Synchronize your character with Blizzard\'s Armory';
$lang['async_button2']			= 'ApiSync Character|Synchronize your guild\'s characters with Blizzard\'s Armory';
$lang['async_button3']			= 'ApiSync Character|Synchronize your realm\'s character with Blizzard\'s Armory';
$lang['async_button4']			= 'ApiSync Memberlist|Synchronize your memberlist with Blizzard\'s Armory';
$lang['async_button5']			= 'ApiSync Memberlist for a new guild|Add a new guild and synchronize<br />the memberlist with Blizzard\'s Armory';
$lang['async_button6']			= 'ApiSync Guild Players|Synchronize your guild\'s Players <br />data with Blizzard\'s Armory';

$lang['faction'] = 'Faction';
// Config strings
$lang['admin']['ApiSync_conf']			= 'General|Configure base settings for ApiSync';
$lang['admin']['ApiSync_ranks']			= 'Ranks|Configure guild ranks for ApiSync';
$lang['admin']['ApiSync_images']			= 'Images|Configure image displaying for ApiSync';
$lang['admin']['ApiSync_access']			= 'Access Rights|Configure access rights for ApiSync';
$lang['admin']['ApiSync_debug']			= 'Debugging|Configure debug settings for ApiSync';

$lang['admin']['ApiSync_host']			= 'Host|Host to Synchronize with';
$lang['admin']['ApiSync_minlevel']		= 'Minimum Level|Minimum level of characters to synchronize<br />Currently this should be no lower than 10 since<br />the armory doesn\'t list characters lower than level 10';
$lang['admin']['ApiSync_synchcutofftime']	= 'Sync cutoff time|Time in days<br />All characters not updated in last (x) days will be synchronized';
$lang['admin']['ApiSync_use_ajax']	= 'Use AJAX|Whether to use AJAX for status update or not.';
$lang['admin']['ApiSync_reloadwaittime']	= 'Reload wait time|Time in seconds<br />Time in seconds before next synchronization during a sync job 24+ recommended';
$lang['admin']['ApiSync_fetch_timeout'] = 'Armory Fetch timeout|Time in seconds till a fetch of a single XML file is aborted.';
$lang['admin']['ApiSync_skip_start'] = 'Skip start page|Skip start page on ApiSync updates.';
$lang['admin']['ApiSync_status_hide'] = 'Hide status windows initially|Hide the status windows of ApiSync on the first load.';
$lang['admin']['ApiSync_protectedtitle']	= 'Protected Guild Title|Characters with these guild titles are protected<br />from being deleted by a synchronization against the armory.<br />This problem often occours with bank characters.<br />Multiple values seperated by a comma (,) \"Banker,Stock\"';

$lang['admin']['ApiSync_rank_set_order']	= "Guild Rank Set Order|Defines in which order the guild titles will be set.";
$lang['admin']['ApiSync_rank_0']	= "Title Guild Leader|This title will be set if in WoWRoster for that guild none is defined.";
$lang['admin']['ApiSync_rank_1']	= "Title Rank 1|This title will be set if in WoWRoster for that guild none is defined.";
$lang['admin']['ApiSync_rank_2']	= "Title Rank 2|This title will be set if in WoWRoster for that guild none is defined.";
$lang['admin']['ApiSync_rank_3']	= "Title Rank 3|This title will be set if in WoWRoster for that guild none is defined.";
$lang['admin']['ApiSync_rank_4']	= "Title Rank 4|This title will be set if in WoWRoster for that guild none is defined.";
$lang['admin']['ApiSync_rank_5']	= "Title Rank 5|This title will be set if in WoWRoster for that guild none is defined.";
$lang['admin']['ApiSync_rank_6']	= "Title Rank 6|This title will be set if in WoWRoster for that guild none is defined.";
$lang['admin']['ApiSync_rank_7']	= "Title Rank 7|This title will be set if in WoWRoster for that guild none is defined.";
$lang['admin']['ApiSync_rank_8']	= "Title Rank 8|This title will be set if in WoWRoster for that guild none is defined.";
$lang['admin']['ApiSync_rank_9']	= "Title Rank 9|This title will be set if in WoWRoster for that guild none is defined.";

$lang['admin']['ApiSync_char_update_access'] = 'Char Update Access|Who is able to do character updates';
$lang['admin']['ApiSync_guild_update_access'] = 'Guild Update Access|Who is able to do guild updates';
$lang['admin']['ApiSync_guild_memberlist_update_access'] = 'Guild Memberlist Update Access|Who is able to do guild memberlist updates';
$lang['admin']['ApiSync_realm_update_access'] = 'Realm Update Access|Who is able to do realm updates';
$lang['admin']['ApiSync_guild_add_access'] = 'Guild Add Access|Who is able to add new guilds';

$lang['admin']['ApiSync_logo'] = 'ApiSync Logo|';
$lang['admin']['ApiSync_pic1'] = 'ApiSync Image 1|';
$lang['admin']['ApiSync_pic2'] = 'ApiSync Image 2|';
$lang['admin']['ApiSync_pic3'] = 'ApiSync Image 3|';
$lang['admin']['ApiSync_effects'] = 'ApiSync Image Effects|';
$lang['admin']['ApiSync_logo_show'] = 'Show Logo|';
$lang['admin']['ApiSync_pic1_show'] = $lang['admin']['ApiSync_pic2_show'] = $lang['admin']['ApiSync_pic3_show'] = 'Show Image|';
$lang['admin']['ApiSync_pic_effects'] = 'Activated|Use JavaScript effects for images.';
$lang['admin']['ApiSync_logo_pos_left'] = $lang['admin']['ApiSync_pic1_pos_left'] = $lang['admin']['ApiSync_pic2_pos_left'] = $lang['admin']['ApiSync_pic3_pos_left'] = 'Image position horizontal|';
$lang['admin']['ApiSync_logo_pos_top'] = $lang['admin']['ApiSync_pic1_pos_top'] = $lang['admin']['ApiSync_pic2_pos_top'] = $lang['admin']['ApiSync_pic3_pos_top'] = 'Image position vertical|';
$lang['admin']['ApiSync_logo_size'] = $lang['admin']['ApiSync_pic1_size'] = $lang['admin']['ApiSync_pic2_size'] = $lang['admin']['ApiSync_pic3_size'] = 'Image size|';
$lang['admin']['ApiSync_pic1_min_rows'] = $lang['admin']['ApiSync_pic2_min_rows'] = $lang['admin']['ApiSync_pic3_min_rows'] = 'Minimun Rows|Minimum number of rows in the status display<br />to display the image.';

$lang['admin']['ApiSync_debuglevel']		= 'Debug Level|Adjust the debug level for ApiSync.<br /><br />Quiete - No Messages<br />Base Info - Base messages<br />Armory & Job Method Info - All messages of Armory and Job methods<br />All Methods Info - Messages of all Methods  <b style="color:red;">(Be careful - very much data)</b>';
$lang['admin']['ApiSync_debugdata']		= 'Debug Data|Raise debug output by methods arguments and returns<br /><b style="color:red;">(Be careful - much more info on high debug level)</b>';
$lang['admin']['ApiSync_javadebug']		= 'Debug Java|Enable JavaScript debugging.<br />Not implemented yet.';
$lang['admin']['ApiSync_xdebug_php']		= 'XDebug Session PHP|Enable sending XDEBUG variable with POST.';
$lang['admin']['ApiSync_xdebug_ajax']	= 'XDebug Session AJAX|Enable sending XDEBUG variable with AJAX POST.';
$lang['admin']['ApiSync_xdebug_idekey']	= 'XDebug Session IDEKEY|Define IDEKEY for Xdebug sessions.';
$lang['admin']['ApiSync_sqldebug']		= 'SQL Debug|Enable SQL debugging for ApiSync.<br />Not useful in combination with roster SQL debugging / duplicate data.';
$lang['admin']['ApiSync_updateroster']	= "Update Roster|Enable roster updates.<br />Good for debugging<br />Not implemented yet.";


$lang['bindings']['bind_on_pickup'] = "Binds when picked up";
$lang['bindings']['bind_on_equip'] = "Binds when equipped";
$lang['bindings']['bind'] = "Soulbound";

// Addon strings
$lang['RepStanding']['Exalted'] = 'Exalted';
$lang['RepStanding']['Revered'] = 'Revered';
$lang['RepStanding']['Honored'] = 'Honored';
$lang['RepStanding']['Friendly'] = 'Friendly';
$lang['RepStanding']['Neutral'] = 'Neutral';
$lang['RepStanding']['Unfriendly'] = 'Unfriendly';
$lang['RepStanding']['Hostile'] = 'Hostile';
$lang['RepStanding']['Hated'] = 'Hated';

$lang['Skills']['Class Skills'] = "Class Skills";
$lang['Skills']['Professions'] = "Professions";
$lang['Skills']['Secondary Skills'] = "Secondary Skills";
$lang['Skills']['Weapon Skills'] = "Weapon Skills";
$lang['Skills']['Armor Proficiencies'] = "Armor Proficiencies";
$lang['Skills']['Languages'] = "Languages";


$lang['Classes']['Druid'] = 'Druid';
$lang['Classes']['Hunter'] = 'Hunter';
$lang['Classes']['Mage'] = 'Mage';
$lang['Classes']['Paladin'] = 'Paladin';
$lang['Classes']['Priest'] = 'Priest';
$lang['Classes']['Rogue'] = 'Rogue';
$lang['Classes']['Shaman'] = 'Shaman';
$lang['Classes']['Warlock'] = 'Warlock';
$lang['Classes']['Warrior'] = 'Warrior';
$lang['Classes']['Death Knight'] = 'Death Knight';

$lang['Talenttrees']['Death Knight']['Blood'] = "Blood";
$lang['Talenttrees']['Death Knight']['Frost'] = "Frost";
$lang['Talenttrees']['Death Knight']['Unholy'] = "Unholy";
//$lang['Talenttrees']['DeathKnight']['Blood'] = "Blood";
//$lang['Talenttrees']['DeathKnight']['Frost'] = "Frost";
//$lang['Talenttrees']['DeathKnight']['Unholy'] = "Unholy";
$lang['Talenttrees']['Druid']['Balance'] = "Balance";
$lang['Talenttrees']['Druid']['Feral Combat'] = "Feral Combat";
$lang['Talenttrees']['Druid']['Restoration'] = "Restoration";
$lang['Talenttrees']['Hunter']['Beast Mastery'] = "Beast Mastery";
$lang['Talenttrees']['Hunter']['Marksmanship'] = "Marksmanship";
$lang['Talenttrees']['Hunter']['Survival'] = "Survival";
$lang['Talenttrees']['Mage']['Arcane'] = "Arcane";
$lang['Talenttrees']['Mage']['Fire'] = "Fire";
$lang['Talenttrees']['Mage']['Frost'] = "Frost";
$lang['Talenttrees']['Paladin']['Holy'] = "Holy";
$lang['Talenttrees']['Paladin']['Protection'] = "Protection";
$lang['Talenttrees']['Paladin']['Retribution'] = "Retribution";
$lang['Talenttrees']['Priest']['Discipline'] = "Discipline";
$lang['Talenttrees']['Priest']['Holy'] = "Holy";
$lang['Talenttrees']['Priest']['Shadow'] = "Shadow";
$lang['Talenttrees']['Rogue']['Assassination'] = "Assassination";
$lang['Talenttrees']['Rogue']['Combat'] = "Combat";
$lang['Talenttrees']['Rogue']['Subtlety'] = "Subtlety";
$lang['Talenttrees']['Shaman']['Elemental'] = "Elemental";
$lang['Talenttrees']['Shaman']['Enhancement'] = "Enhancement";
$lang['Talenttrees']['Shaman']['Restoration'] = "Restoration";
$lang['Talenttrees']['Warlock']['Affliction'] = "Affliction";
$lang['Talenttrees']['Warlock']['Demonology'] = "Demonology";
$lang['Talenttrees']['Warlock']['Destruction'] = "Destruction";
$lang['Talenttrees']['Warrior']['Arms'] = "Arms";
$lang['Talenttrees']['Warrior']['Fury'] = "Fury";
$lang['Talenttrees']['Warrior']['Protection'] = "Protection";

$lang['misc']['Rank'] = "Rank";

$lang['guild_short'] = "Guild";
$lang['character_short'] = "Char";
$lang['skill_short'] = "Skill";
$lang['reputation_short'] = "Rep";
$lang['equipment_short'] = "Equip";
$lang['talents_short'] = "Talent";

$lang['started'] = "Started";
$lang['finished'] = "Finished";

$lang['ApiSyncTitle_Char'] = "ApiSync for Characters";
$lang['ApiSyncTitle_Guild'] = "ApiSync for Guilds";
$lang['ApiSyncTitle_Guildmembers'] = "ApiSync for Guild Member Lists";
$lang['ApiSyncTitle_Realm'] = "ApiSync for Realms";

$lang['next_to_update'] = "Next Update: ";
$lang['nothing_to_do'] = "Nothing to do at the moment";

$lang['error'] = "Error";
$lang['error_no_character'] = "No Character referred.";
$lang['error_no_guild'] = "No Guild referred.";
$lang['error_no_realm'] = "No Realm referred.";
$lang['error_use_menu'] = "Use menu to Synchronize.";

$lang['error_guild_insert'] = "Error creating guild.";
$lang['error_uploadrule_insert'] = "Error creating upload rule.";
$lang['error_guild_notexist'] = "The guild given does not exist in the Armory.";
$lang['error_missing_params'] = "Missing parameters. Please try again";
$lang['error_wrong_region'] = "Invalid region. Only EU and US are valid regions.";
$lang['ApiSync_guildadd'] = "Adding Guild and synchronize<br />memberlist with the Armory.";
$lang['ApiSync_charadd'] = "Adding Character and synchronize<br />with the Armory.";
$lang['ApiSync_add_help'] = "Information";
$lang['ApiSync_add_helpText'] = "Spell the character / guild and the server names exactly how they are listed on the Armory.<br />Region is EU for European and US for American/Oceanic.<br />First, ApiSync will check if the guild exists in the Armory.<br />Next, a synchronization of the memberlist will be done.";

$lang['guildleader'] = "Guildleader";

$lang['rage'] = "Rage";
$lang['energy'] = "Energy";
$lang['focus'] = "Focus";

$lang['ApiSync_credits'] = 'ApiSync Based off of armorysync built on blizzards API.';

$lang['start'] = "Start";
$lang['start_message'] = "You're about to start ApiSync for %s %s.<br /><br />By doing this, all data for %s will be overwritten<br />with details from Blizzard's Armory. This can only be undone<br />by uploading a current CharacterProfiler.lua.<br /><br />Do you want to start this process yet?";

$lang['start_message_the_char'] = "the character";
$lang['start_message_this_char'] = "this character";
$lang['start_message_the_guild'] = "the guild";
$lang['start_message_this_guild'] = "all characters of this guild";
$lang['start_message_the_realm'] = "the realm";
$lang['start_message_this_realm'] = "all characters of this realm";

$lang['id_to_faction'] = array(
    "0" => "Alliance",
    "1" => "Horde"
);

$lang['month_to_en'] = array(
    "January" => "January",
    "February" => "February",
    "March" => "March",
    "April" => "April",
    "May" => "May",
    "June" => "June",
    "July" => "July",
    "August" => "August",
    "September" => "September",
    "October" => "October",
    "November" => "November",
    "December" => "December"
);

$lang['roster_deprecated'] = "WoWRoster deprecated";
$lang['roster_deprecated_message'] = "<br />You are using <b>WoWRoster</b><br /><br />Version: <strong style=\"color:red;\" >%s</strong><br /><br />To use <b>ApiSync</b> Version <strong style=\"color:yellow;\" >%s</strong><br />you will at least need <b>WoWRoster</b><br /><br />Version <strong style=\"color:green;\" >%s</strong><br /><br />Please update your <b>WoWRoster</b><br />&nbsp;";

$lang['ApiSync_not_upgraded'] = "ApiSync not upgraded";
$lang['ApiSync_not_upgraded_message'] = "<br />You have installed <b>ApiSync</b><br /><br />Version: <strong style=\"color:green;\" >%s</strong><br /><br />Right now there is <b>ApiSync</b><br /><br />Version <strong style=\"color:red;\" >%s</strong><br /><br />registered with <b>WoWRoster</b>.<br /><br />Please go to <b>WoWRoster</b> configuration<br />and upgrade your <b>ApiSync</b><br />&nbsp;";

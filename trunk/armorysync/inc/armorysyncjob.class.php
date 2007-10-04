<?php
/**
 * WoWRoster.net WoWRoster
 *
 * ArmorySyncJob Library
 *
 * LICENSE: Licensed under the Creative Commons
 *          "Attribution-NonCommercial-ShareAlike 2.5" license
 *
 * @copyright  2002-2007 WoWRoster.net
 * @license    http://creativecommons.org/licenses/by-nc-sa/2.5   Creative Commons "Attribution-NonCommercial-ShareAlike 2.5"
 * @version    SVN: $Id$
 * @link       http://www.wowroster.net
 * @package    ArmorySync
*/

if( !defined('IN_ROSTER') )
{
    exit('Detected invalid access to this file!');
}

class ArmorySyncJob {

    var $jobid;
    var $members = array();
    var $active_member = array();
    var $time_started;
    var $title;

    var $done;
    var $total;

    var $message;

    var $ArmorySync;
    var $id = 0;
    var $isMemberList = 0;
    var $isAuth = 0;
    var $link;
    var $dataNotAccepted = 0;

    var $header;

    var $ajax = 1;
    var $ajaxDebug = 1;

    var $functions = array(
                        array(
                            'link' => '_link',
                            'prepare_update' => '_prepare_update',
                            'update_status' => '_update_status',
                            'show_status' => '_show_status',
                            'get_ajax_status' => '_get_ajax_status'
                        ),
                        array(
                            'link' => '_link',
                            'prepare_update' => '_prepare_updateMemberlist',
                            'update_status' => '_update_statusMemberlist',
                            'show_status' => '_show_statusMemberlist',
                            'get_ajax_status' => '_get_ajax_statusMemberList'
                        ),
                    );


    function _init() {
        global $addon;

        if ( ! is_object( $this->ArmorySync ) ) {
            require_once ($addon['dir'] . 'inc/armorysync.class.php');
            $this->ArmorySync = new ArmorySync();
        }
    }

    /**
     * Show error on deprecated Roster
     *
     */
    function _showErrorRosterDeprecated() {
        global $roster, $addon;

        $html = sprintf( $roster->locale->act['roster_deprecated_message'], ROSTER_VERSION, ARMORYSYNC_VERSION, ARMORYSYNC_REQUIRED_ROSTER_VERSION);
        print messagebox( $html , "<span class=\"title_text\">". $roster->locale->act['roster_deprecated']."</span>" , $style='sred' , '400px' );
    }

    /**
     * Check if Roster is new enough
     *
     */
    function _isRequiredRosterVersion() {
        return version_compare( ARMORYSYNC_REQUIRED_ROSTER_VERSION, ROSTER_VERSION, '<=');
    }

    /**
     * Show error on deprecated Roster
     *
     */
    function _showErrorArmorySyncNotUpgraded() {
        global $roster, $addon;

        $html = sprintf( $roster->locale->act['armorysync_not_upgraded_message'], ARMORYSYNC_VERSION, $addon['version']);
        print messagebox( $html , "<span class=\"title_text\">". $roster->locale->act['armorysync_not_upgraded']."</span>" , $style='sred' , '400px' );
    }

    /**
     * Check if ArmorySync is new enough
     *
     */
    function _isRequiredArmorySyncVersion() {
        global $addon;
        return version_compare( ARMORYSYNC_VERSION, $addon['version'], '<=');
    }

    /**
     * fetch insert jobid, fill jobqueue
     *
     */
    function start() {
        global $roster, $addon;

        require_once ($addon['dir'] . 'inc/constants.php');

        $this->_check_env();

        if ( ! $this->isAuth ) {
            $this->_showFooter();
            return;
        }
        if ( ! $this->_isRequiredRosterVersion() ) {
            $this->_showErrorRosterDeprecated();
            return;
        }

        if ( ! $this->_isRequiredArmorySyncVersion() ) {
            $this->_showErrorArmorySyncNotUpgraded();
            return;
        }

        if ( isset($roster->pages[2]) && $roster->pages[2] == 'guildadd' && !isset($_POST['process']) ) {

            $this->_showAddGuildScreen();

        } elseif ( isset($roster->pages[2]) && $roster->pages[2] == 'guildadd' && isset($_POST['process']) && $_POST['process'] == 'process' ) {

            $this->_startAddGuild();

        } elseif ( $this->id && $addon['config']['armorysync_skip_start'] == 0 && !( isset($_GET['job_id']) || isset($_POST['job_id']) ) ) {

            $this->_showStartPage();

        } elseif ( $this->id && ( isset($_GET['job_id']) || isset($_POST['job_id']) || $addon['config']['armorysync_skip_start'] == 1 ) ) {

            $this->_startSyncing();

        } else {

            $this->_showErrors();
        }
        $roster->output['html_head'] = $this->header;
        $this->_showFooter();
    }


    /**
     * fetch insert jobid, fill jobqueue
     *
     */
    function _showErrors() {
        global $roster;
        if ( $roster->scope == 'char' ) {
            $html = $roster->locale->act['error_no_character']. "<br />&nbsp;&nbsp;".
                    $roster->locale->act['error_use_menu']. "&nbsp;&nbsp;";
        } elseif ( $roster->scope == 'guild' ) {
            $html = $roster->locale->act['error_no_guild']. "<br />&nbsp;&nbsp;".
                    $roster->locale->act['error_use_menu']. "&nbsp;&nbsp;";
        } elseif ( $roster->scope == 'realm' ) {
            $html = $roster->locale->act['error_no_realm']. "<br />&nbsp;&nbsp;".
                    $roster->locale->act['error_use_menu']. "&nbsp;&nbsp;";
        }
        print messagebox( $html , $roster->locale->act['error'] , $style='sred' , '' );
    }


    /**
     * fetch insert jobid, fill jobqueue
     *
     */
    function _startSyncing() {
        global $roster;

        if ( isset($_GET['job_id']) ) {
            $this->jobid = $_GET['job_id'];
        }
        if ( isset($_POST['job_id']) ) {
            $this->jobid = $_POST['job_id'];
        }
        $functions = $this->functions[$this->isMemberList];
        if ( $this->jobid == 0 ) {
            if ( $this->$functions['prepare_update']() ) {
                $ret = $this->$functions['update_status']();
                $this->$functions['show_status']();
                if ( $ret ) {
                    $this->$functions['link']();
                }
            } else {
                $this->_nothing_to_do();
            }
        } else {
            $ret = $this->$functions['update_status']();
            if ( $ret ) {
                $ret = $this->$functions['update_status']();
            }
            $this->$functions['show_status']();
            if ( $ret ) {
                $this->$functions['link']();
            }
        }
    }

    /**
     * fetch insert jobid, fill jobqueue
     *
     */
    function start_ajax_status_update( $id = 0 ) {
        global $roster, $addon;

        $this->_check_env();

        if ( ! $this->isAuth ) {
            return array( 'status' => 103, 'errmsg' => 'Not authorized' );
        }
        //return array( 'result' => 'Das ist ein Test', 'status' => 2 );

        if ( isset($_GET['job_id']) ) {
            $this->jobid = $_GET['job_id'];
        }
        if ( isset($_POST['job_id']) ) {
            $this->jobid = $_POST['job_id'];
        }

        $functions = $this->functions[$this->isMemberList];
        $ret = $this->$functions['update_status']();

        if ( $ret ) {
            $reloadTime = $addon['config']['armorysync_reloadwaittime'] * 500;
            return array(   'result' => $this->$functions['get_ajax_status']().
                            '<reload>'. $reloadTime. '</reload>',
                            'status' => 0 );
        } else {
            return array(   'result' => $this->$functions['get_ajax_status'](),
                            'status' => 0 );
            //$this->$functions['get_ajax_status']()
        }
    }

    /**
     * fetch insert jobid, fill jobqueue
     *
     */
    function _startAddGuild() {
        global $roster;
        if ( isset($_POST['action']) && $_POST['action'] == 'add' ) {

            if ( isset($_POST['name']) && isset($_POST['server']) && isset($_POST['region']) ) {

                $name = urldecode(trim(stripslashes( $_POST['name'] )));
                $server = urldecode(trim(stripslashes( $_POST['server'] )));
                $region = strtoupper($_POST['region']);

                if ( $region == "EU" || $region == "US" ) {
                    if ( $this->_check_guild_exist( $name, $server, $region ) ) {

                        if ( $id = $this->_insert_guild( $name, $server, $region ) ) {

                            if ( $this->_insert_uploadRule( $name, $server, $region ) ) {
                                if ( $this->_prepare_updateMemberlist( $id, $name, $server, $region ) ) {
                                    $ret = $this->_update_statusMemberlist();
                                    $link = makelink('guild-armorysync-memberlist&guild='. $id);
                                    $this->_show_statusMemberlist();
                                    if ( $ret ) {
                                        $this->_link();//_guildMemberlist( $id )
                                    }
                                } else {
                                    $this->_nothing_to_do();
                                }
                            } else {
                                $html = "&nbsp;&nbsp;".
                                        $roster->locale->act['error_uploadrule_insert'].
                                        "&nbsp;&nbsp;";
                                print messagebox( $html , $roster->locale->act['error'] , $style='sred' , '' );
                            }
                        } else {
                            $html = "&nbsp;&nbsp;".
                                    $roster->locale->act['error_guild_insert'].
                                    "&nbsp;&nbsp;";
                            print messagebox( $html , $roster->locale->act['error'] , $style='sred' , '' );
                        }
                    } else {
                        $html = "&nbsp;&nbsp;".
                                $roster->locale->act['error_guild_notexist'].
                                "&nbsp;&nbsp;";
                        print messagebox( $html , $roster->locale->act['error'] , $style='sred' , '' );
                    }
                } else {
                    $html = "&nbsp;&nbsp;".
                            $roster->locale->act['error_wrong_region'].
                            "&nbsp;&nbsp;";
                    print messagebox( $html , $roster->locale->act['error'] , $style='sred' , '' );
                }
            } else {
                $html = "&nbsp;&nbsp;".
                        $roster->locale->act['error_missing_params'].
                        "&nbsp;&nbsp;";
                print messagebox( $html , $roster->locale->act['error'] , $style='sred' , '' );
            }
        }
    }

    /**
     * fetch insert jobid, fill jobqueue
     *
     */
    function _check_env() {
        global $roster;
        if ( isset($_GET['ROSTER_PAGE']) && $_GET['ROSTER_PAGE'] == 'ajax') {
            $this->isMemberlist = $_POST['memberlist'];
            if ( $_POST['scope'] == 'char') {
                $this->isAuth = $this->_checkAuth('armorysync_char_update_access');
            } elseif ( $_POST['scope'] == 'guild') {
                if ( isset( $_POST['page']) && $_POST['page'] == 'memberlist' ) {
                    $this->isAuth = $this->_checkAuth('armorysync_guild_memberlist_update_access');
                    $this->isMemberList = 1;
                } else {
                    $this->isAuth = $this->_checkAuth('armorysync_guild_update_access');
                }
            } elseif ( $_POST['scope'] == 'realm') {
                $this->isAuth = $this->_checkAuth('armorysync_realm_update_access');
            } elseif ( $_POST['scope'] == 'util') {
                $this->isMemberList = 1;
                $this->isAuth = $this->_checkAuth('armorysync_guild_add_access');
            }
        } elseif ( $roster->scope == 'char' ) {
            $this->id = $roster->data['member_id'];
            $this->title = "<span class=\"title_text\">". $roster->locale->act['armorySyncTitle_Char']. "</span>\n";
            $this->isAuth = $this->_checkAuth('armorysync_char_update_access');
        } elseif ( $roster->scope == 'guild' && isset( $roster->pages[2] ) && $roster->pages[2] == 'memberlist' ) {
            $this->id = $roster->data['guild_id'];
            $this->title = "<span class=\"title_text\">". $roster->locale->act['armorySyncTitle_Guildmembers']. "</span>\n";
            $this->isMemberList = 1;
            $this->isAuth = $this->_checkAuth('armorysync_guild_update_access');
        } elseif ( $roster->scope == 'guild' ) {
            $this->id = $roster->data['guild_id'];
            $this->title = "<span class=\"title_text\">". $roster->locale->act['armorySyncTitle_Guild']. "</span>\n";
            $this->isAuth = $this->_checkAuth('armorysync_guild_memberlist_update_access');
        } elseif ( $roster->scope == 'realm' ) {
            $this->id = $roster->data['server'];
            $this->title = "<span class=\"title_text\">". $roster->locale->act['armorySyncTitle_Realm']. "</span>\n";
            $this->isAuth = $this->_checkAuth('armorysync_realm_update_access');
        } elseif ( $roster->scope == 'util' ) {
            $this->title = "<span class=\"title_text\">". $roster->locale->act['armorySyncTitle_Guildmembers']. "</span>\n";
            $this->isMemberList = 1;
            $this->isAuth = $this->_checkAuth('armorysync_guild_add_access');
        }
    }

    /**
     * fetch insert jobid, fill jobqueue
     *
     */
    function _prepare_update() {
        global $roster, $addon;

        $this->time_started = gmdate('Y-m-d H:i:s');

        if ( $roster->scope == 'char' ) {

            $this->members = array(
                        array(
                                'member_id' => $roster->data['member_id'],
                                'name' => $roster->data['name'],
                                'guild_id' => $roster->data['guild_id'],
                                'guild_name' => $roster->data['guild_name'],
                                'server' => $roster->data['server'],
                                'region' => $roster->data['region'] ) );
        } elseif ( $roster->scope == 'guild' ) {

            $this->members = $this->_getGuildMembersToUpdate();
        } elseif ( $roster->scope == 'realm' ) {

            $this->members = $this->_getRealmMembersToUpdate();
        }

        if ( array_keys( $this->members ) ) {

            $this->jobid = $this->_insertJobID($this->time_started);
            $this->_insertMembersToJobqueue($this->jobid, $this->members);
            return true;
        }
        return false;
    }

    /**
     * fetch insert jobid, fill jobqueue
     *
     */
    function _prepare_updateMemberlist( $id = 0, $name = false , $server = false , $region = false ) {
        global $roster, $addon;

        if ( ! $id ) {
            $id = $roster->data['guild_id'];
        }
        if ( ! $name ) {
            $name = $roster->data['guild_name'];
        }
        if ( ! $server ) {
            $server = $roster->data['server'];
        }
        if ( ! $region ) {
            $region = $roster->data['region'];
        }


        $this->time_started = gmdate('Y-m-d H:i:s');

        $this->members = array(
                    array(
                            'name' => false,
                            'member_id' => false,
                            'guild_id' => $id,
                            'guild_name' => $name,
                            'server' => $server,
                            'region' => $region ) );

        if ( array_keys( $this->members ) ) {

            $this->jobid = $this->_insertJobID($this->time_started);
            $this->_insertMembersToJobqueue($this->jobid, $this->members);
            return true;
        }
        return false;
    }

    /**
     * statusbox output
     *
     * @param int $jobid
     */
    function _nothing_to_do() {
        global $roster;

        $html = '<span class="title_text">&nbsp;&nbsp;'. $roster->locale->act['nothing_to_do']. '&nbsp;&nbsp;</span>';

        print messagebox( $html , $title=$this->title , $style='syellow' , $width='' );
    }


    /**
     * statusbox output with templates
     *
     * @param int $jobid
     */
    function _show_status( $jobid = 0, $memberlist = false ) {
        global $roster, $addon;

        $jscript = "<script type=\"text/javascript\" src=\"". $addon['url_path']. "js/armorysync.js\"></script>\n";
        $this->header .= $jscript;

        $members = $this->members;

        $status = isset($_POST['StatusHidden']) ? $_POST['StatusHidden'] :
                    ( $addon['config']['armorysync_status_hide'] ? 'ON' : 'OFF' );
        $display = ( $status == 'ON' ) ? 'none' : '';
        $icon = ( $status == 'ON' ) ? 'img/plus.gif' : 'img/minus.gif';
        $style = 'syellow';

        $roster->tpl->assign_vars(array(
                'IMAGE_PATH' => $addon['image_path'],
                'PICLOGO' => 1,
                'PIC1' => $this->total >= 20 ? true: false,
                'PIC2' => $this->total >= 40 ? true: false,
                'PIC3' => $this->total >= 60 ? true: false,
                'LINK' => ( $this->link ? $this->link : makelink() ),
                'STATUSHIDDEN' => $status,
                'JOB_ID' => $this->jobid,
                'DISPLAY' => $display,
                'ICON' => $icon,
                'START_BORDER' => border( $style, 'start', '', '848px' ),
                'STYLE' => $style,
                'TITLE' => $this->title,
                'PROGRESSBAR' => $this->_getProgressBar($this->done, $this->total),
                )
                                 );

        if ($this->active_member['name'] || $this->active_member['guild_name']) {
            $roster->tpl->assign_var( 'NEXT', $roster->locale->act['next_to_update']. ( $memberlist ? $this->active_member['guild_name'] : $this->active_member['name'] ) );
        } else {
            $roster->tpl->assign_var( 'NEXT', false );
        }

        if ( !$memberlist ) {
            $roster->tpl->assign_block_vars('head_col', array('HEAD_TITLE' => $roster->locale->act['name']));
        }
        $roster->tpl->assign_block_vars('head_col', array('HEAD_TITLE' => $roster->locale->act['guild']));
        $roster->tpl->assign_block_vars('head_col', array('HEAD_TITLE' => $roster->locale->act['realm']));
        $roster->tpl->assign_block_vars('head_col', array('HEAD_TITLE' => "Infos<br />". $roster->locale->act['guild_short']));

        if ( ! $memberlist ) {
            $roster->tpl->assign_block_vars('head_col', array('HEAD_TITLE' => "Infos<br />". $roster->locale->act['character_short']));
            $roster->tpl->assign_block_vars('head_col', array('HEAD_TITLE' => "Infos<br />". $roster->locale->act['skill_short']));
            $roster->tpl->assign_block_vars('head_col', array('HEAD_TITLE' => "Infos<br />". $roster->locale->act['reputation_short']));
            $roster->tpl->assign_block_vars('head_col', array('HEAD_TITLE' => "Infos<br />". $roster->locale->act['equipment_short']));
            $roster->tpl->assign_block_vars('head_col', array('HEAD_TITLE' => "Infos<br />". $roster->locale->act['talents_short']));
        }

        $roster->tpl->assign_block_vars('head_col', array('HEAD_TITLE' => $roster->locale->act['started']));
        $roster->tpl->assign_block_vars('head_col', array('HEAD_TITLE' => $roster->locale->act['finished']));
        $roster->tpl->assign_block_vars('head_col', array('HEAD_TITLE' => "Log" ));

        $l = 1;
        $roster->tpl->assign_var('CHARLIST', !$memberlist);
        foreach ( $members as $member ) {

            $array = array();
            $array['COLOR'] = $roster->switch_row_class();
            $array['ASID'] = $memberlist ? $member['guild_id'] : $member['member_id'];
            $array['NAME'] = $member['name'];
            $array['GUILD'] = $member['guild_name'];
            $array['SERVER'] = $member['region']. "-". $member['server'];

            foreach ( array( 'guild_info', 'character_info', 'skill_info', 'reputation_info', 'equipment_info', 'talent_info' ) as $key ) {
                if ( $memberlist && $key !== 'guild_info' ) {
                    continue;
                }
                if ( isset( $member[$key] ) && $member[$key] == 1 ) {
                    $array[strtoupper($key)] = "<img src=\"img/pvp-win.gif\" alt=\"\"/>";
                } elseif ( isset( $member[$key] ) && $member[$key] >= 1 ) {
                    $array[strtoupper($key)] = $member[$key];
                } elseif ( isset( $member[$key] ) ) {
                    $array[strtoupper($key)] = "<img src=\"img/pvp-loss.gif\" alt=\"\" />";
                } else {
                    $array[strtoupper($key)] = "<img src=\"img/blue-question-mark.gif\" alt=\"?\" />";
                }
            }

            $array['STARTTIMEUTC'] = isset( $member['starttimeutc'] ) ? $this->_getLocalisedTime($member['starttimeutc']) : "<img src=\"img/blue-question-mark.gif\" alt=\"?\"/>";
            $array['STOPTIMEUTC'] = isset( $member['stoptimeutc'] ) ? $this->_getLocalisedTime($member['stoptimeutc']) : "<img src=\"img/blue-question-mark.gif\" alt=\"?\"/>";

            if ( !$memberlist && $member['log'] ) {
                $array['LOG'] = "<img src=\"img/note.gif\"". makeOverlib( $member['log'] , $roster->locale->act['update_log'] , '' ,0 , '' , ',WRAP' ). " alt=\"\" />";
            } elseif( $member['log'] ) {
                $array['LOG'] = "<img src=\"img/note.gif\"". makeOverlib( "<div style=\"height:300px;width:500px;overflow:auto;\">". $member['log']. "</div>", $roster->locale->act['update_log'] , '' ,0 , '' , ',STICKY, OFFSETX, 250, CLOSECLICK' ). " alt=\"\" />";
            } else {
                $array['LOG'] = "<img src=\"img/no_note.gif\" alt=\"\" />";
            }


            $roster->tpl->assign_block_vars('body_row', $array );
            $l++;
        }

        $roster->tpl->assign_var('STOP_BORDER', border( 'syellow', 'end' ));


        $roster->tpl->set_filenames(array(
                'status_head' => $addon['basename'] . '/status_head.html',
                'status_body' => $addon['basename'] . '/status_body.html',
                ));

        $roster->tpl->display('status_head');
        $roster->tpl->display('status_body');
    }

    /**
     * statusbox Memberlist output with ajax ( experimental )
     *
     * @param int $jobid
     */
    function _get_ajax_statusMemberlist( $jobid = 0 ) {
        global $roster;

        return $this->_get_ajax_status( $jobid, 1 );
    }

    /**
     * statusbox output with ajax ( experimental )
     *
     * @param int $jobid
     */
    function _get_ajax_status( $jobid = 0, $memberlist = false ) {
        global $roster, $addon;

        $result = '';

        $perc = 0;
        if ( $this->total == 0 ) {
            $perc = 100;
        } else {
            $perc = round ($this->done / $this->total * 100);
        }

        $perc_left = 100 - $perc;

        $result .= "<progress_perc>". $perc. "</progress_perc>\n";
        $result .= "<progress_perc_left>". $perc_left. "</progress_perc_left>\n";
        $result .= "<progress_text>";
        $result .= urlencode("$perc% ". $roster->locale->act['complete']. " ($this->done / $this->total)");
        $result .= "</progress_text>\n";

        $result .= "<armorysync_status>\n";

        if ($this->active_member['name']) {
            $result .= "<progress_next>". urlencode($roster->locale->act['next_to_update']. $this->active_member['name']). "</progress_next>\n";
        } else {
            $result .= "<progress_next></progress_next>\n";
        }

        $member = $this->active_member;

        $id = $memberlist ? $member['guild_id'] : $member['member_id'];

        if ( $id ) {
            foreach ( array( 'guild_info', 'character_info', 'skill_info', 'reputation_info', 'equipment_info', 'talent_info' ) as $key ) {
                if ( $memberlist && $key !== 'guild_info' ) {
                    continue;
                }
                $result .= "<as_status_". $key. "_". $id. ">\n";
                if ( isset( $member[$key] ) && $member[$key] == 1 ) {
                    $result .= "<image>". urlencode("img/pvp-win.gif"). "</image>\n";
                } elseif ( isset( $member[$key] ) && $member[$key] >= 1 ) {
                    $result .= $member[$key];
                } elseif ( isset( $member[$key] ) ) {
                    $result .= "<image>". urlencode("img/pvp-loss.gif"). "</image>\n";
                } else {
                    $result .= "<image>". urlencode("img/blue-question-mark.gif"). "</image>\n";
                }
                $result .= "</as_status_". $key. "_". $id. ">\n";
            }

            $result .= "<as_status_starttimeutc_". $id. ">". ( isset( $member['starttimeutc'] ) ? $this->_getLocalisedTime($member['starttimeutc']) : "<image>". urlencode('img/blue-question-mark.gif'). "</image>" ). "</as_status_starttimeutc_". $id. ">\n";
            $result .= "<as_status_stoptimeutc_". $id. ">". ( isset( $member['stoptimeutc'] ) ? $this->_getLocalisedTime($member['stoptimeutc']) : "<image>". urlencode('img/blue-question-mark.gif'). "</image>"  ). "</as_status_stoptimeutc_". $id. ">\n";

            if ( !$memberlist && $member['log'] ) {
                $result .= "<as_status_log_". $id. ">\n";
                $result .= $this->_xmlEncode("image", 'img/note.gif');
                $result .= "<overlib>";
                $result .= $this->_xmlEncode( "char", str_replace("'", '"', $member['log'] ) );
                $result .= "</overlib>\n";
                $result .= "</as_status_log_". $id. ">\n";
            } elseif( $member['log'] ) {
                $result .= "<as_status_log_". $id. ">\n";
                $result .= $this->_xmlEncode("image", 'img/note.gif');
                $result .= "<overlib>";
                $result .= $this->_xmlEncode( "memberlist", str_replace("'", '"', $member['log'] ) );
                $result .= "</overlib>\n";
                $result .= "</as_status_log_". $id. ">\n";
            }
        }

        $result .= "</armorysync_status>\n";
        return $result;
    }

    /**
     * Encode for ajax XML transfer
     *
     * @param string $tagname
     * @param string $text
     */
    function _xmlEncode( $tagname, $text ) {

        $tag = '';
        $text = urlencode( $text );
        while ( strlen($text) > 0 ) {
             $substr = substr($text, 0, 4000);
             $text = substr($text, 4000);
             $tag .= "<". $tagname. ">";
             $tag .= $substr;
             $tag .= "</". $tagname. ">";
        }
        return $tag;
    }

    /**
     * create footer
     *
     * @param int $jobid
     */
    function _showFooter() {
        global $roster, $addon;

        $roster->tpl->assign_var('ARMORYSYNC_VERSION',$addon['version']. ' by poetter');
        $roster->tpl->assign_var('ARMORYSYNC_CREDITS',$roster->locale->act['armorysync_credits']);
        $roster->tpl->set_filenames(array(
                'footer' => $addon['basename'] . '/footer.html',
                ));
        $roster->tpl->display('footer');
    }

    /**
     * create footer
     *
     * @param int $jobid
     */
    function _showAddGuildScreen() {
        global $roster, $addon;

        $body = '';
        $body .= '<form action="' . makelink() . '" method="post" id="allow">
        <input type="hidden" id="addguild" name="action" value="" />
        <input type="hidden" name="process" value="process" />
        <input type="hidden" name="block" value="allow" />';

        $body .= $this->_ruletable_head('sgreen',$roster->locale->act['armorysync_guildadd'],'addguild','');
        $body .= $this->_ruletable_foot('sgreen','addguild','');

        $body .= '</form>';

        $body .= "<br />\n";
        $body .= "<br />\n";
        $body .= "<br />\n";
        $body .= messagebox($roster->locale->act['armorysync_guildadd_helpText'],'<img src="' . $roster->config['img_url'] . 'blue-question-mark.gif" alt="?" style="float:right;" />' . $roster->locale->act['armorysync_guildadd_help'],'sgray');
        $body .= "<br />\n";

        print '<div style="height:1px; width:1px; overflow:visible;">';
        print '<img src="'. $addon['image_path']. 'as_logo.png" style="position: relative; left:420px; bottom:125px; height:250px;" alt="" />';
        print '</div>';
        print $body;
    }

    /**
     * statusbox Memberlist output
     *
     * @param int $jobid
     */
    function _ruletable_head( $style , $title , $type , $mode )
    {
            global $roster;

            $output = border($style,'start',$title) . '
    <table class="bodyline" cellspacing="0" cellpadding="0">
            <thead>
                    <tr>
    ';

            $name = $roster->locale->act['guildname'];

            $output .= '
                            <th class="membersHeader" ' . makeOverlib($name) . '> ' . $roster->locale->act['guildname'] . '</th>
                            <th class="membersHeader" ' . makeOverlib($roster->locale->act['realmname']) . '> ' . $roster->locale->act['server'] . '</th>
                            <th class="membersHeader" ' . makeOverlib($roster->locale->act['regionname']) . '> ' . $roster->locale->act['region'] . '</th>
                            <th class="membersHeaderRight">&nbsp;</th>
                    </tr>
            </thead>
            <tbody>' . "\n";
            return $output;
    }


    /**
     * statusbox Memberlist output
     *
     * @param int $jobid
     */
    function _ruletable_foot( $style , $type , $mode )
    {
            global $roster;

            $output = "\n\t\t<tr>\n";

            $output .= '
                            <td class="membersRow2"><input class="wowinput128" type="text" name="name" value="" /></td>
                            <td class="membersRow2"><input class="wowinput128" type="text" name="server" value="" /></td>
                            <td class="membersRow2"><input class="wowinput64" type="text" name="region" value="" /></td>
                            <td class="membersRowRight2"><button type="submit" class="input" onclick="setvalue(\'' . $type . '\',\'add\');">' . $roster->locale->act['add'] . '</button></td>
                    </tr>
            </tbody>
    </table>
    ' . border($style,'end');
            return $output;
    }

    /**
     * statusbox Memberlist output
     *
     * @param int $jobid
     */
    function _show_statusMemberlist( $jobid = 0 ) {
        global $roster;

        $this->_show_status( $jobid, 1 );
    }

    /**
     * this is the main logic of the syncjob.
     *
     *
     * @param int $jobid
     */
    function _update_status( $jobid = 0 ) {
        global $roster;

        $this->_init();
        $this->active_member = $this->_isPostSyncStatus( $this->jobid );
        $active_member = $this->active_member;

        if ( ! isset ($active_member['name']) ) {
            $this->active_member = $this->_getNextMemberToUpdate( $this->jobid );
            $active_member = $this->active_member;
            $cleanup = 0;
            if ( isset ($active_member['name']) ) {
                $this->active_member['starttimeutc'] = gmdate('Y-m-d H:i:s');
                if ( $this->_updateMemberJobStatus( $this->jobid, $this->active_member ) ) {
                    $ret = true;
                }
            } else {
                $cleanup = 1;
                $ret = false;
            }
            $this->members = $this->_getMembersFromJobqueue( $this->jobid );
            list ( $this->done, $this->total ) = $this->_getJobProgress($this->jobid);
            if ( $cleanup ) {
                $this->_cleanUpJob( $this->jobid );
            }
            return $ret;
        } else {
            if ( ! $this->ArmorySync->synchMemberByID( $active_member['server'], $active_member['member_id'], $active_member['name'], $active_member['region'], $active_member['guild_id']) ) {
                $this->dataNotAccepted = 1;
            }

            $this->active_member['guild_info'] = $this->ArmorySync->status['guildInfo'];
            $this->active_member['character_info'] = $this->ArmorySync->status['characterInfo'];;
            $this->active_member['skill_info'] = $this->ArmorySync->status['skillInfo'];;
            $this->active_member['reputation_info'] = $this->ArmorySync->status['reputationInfo'];;
            $this->active_member['equipment_info'] = $this->ArmorySync->status['equipmentInfo'];;
            $this->active_member['talent_info'] = $this->ArmorySync->status['talentInfo'];;
            $this->active_member['stoptimeutc'] = gmdate('Y-m-d H:i:s');
            $this->active_member['log'] = $this->ArmorySync->message;
            if ( $this->_updateMemberJobStatus( $this->jobid, $this->active_member ) ) {
                $this->members = $this->_getMembersFromJobqueue( $this->jobid );
                list ( $this->done, $this->total ) = $this->_getJobProgress($this->jobid);
                return true;
            }
        }
    }

    function _update_statusMemberlist( $jobid = 0 ) {
        global $roster;

        $this->_init();
        $this->active_member = $this->_isPostSyncStatus( $this->jobid );
        $active_member = $this->active_member;

        if ( ! isset ($active_member['guild_name']) ) {
            $this->active_member = $this->_getNextGuildToUpdate( $this->jobid );
            $active_member = $this->active_member;
            $cleanup = 0;
            if ( isset ($active_member['guild_name']) ) {
                $this->active_member['starttimeutc'] = gmdate('Y-m-d H:i:s');
                if ( $this->_updateGuildJobStatus( $this->jobid, $this->active_member ) ) {
                    $ret = true;
                }
            } else {
                $cleanup = 1;
                $ret = false;
            }
            $this->members = $this->_getMembersFromJobqueue( $this->jobid );
            list ( $this->done, $this->total ) = $this->_getJobProgress($this->jobid);
            if ( $cleanup ) {
                $this->_cleanUpJob( $this->jobid );
            }
            return $ret;
        } else {
            if ( ! $this->ArmorySync->synchGuildByID( $active_member['server'], $active_member['guild_id'], $active_member['guild_name'], $active_member['region']) ) {
                $this->dataNotAccepted = 1;
            }

            $this->active_member['guild_info'] = $this->ArmorySync->status['guildInfo'];
            $this->active_member['stoptimeutc'] = gmdate('Y-m-d H:i:s');
            $this->active_member['log'] = $this->ArmorySync->message;
            if ( $this->_updateGuildJobStatus( $this->jobid, $this->active_member ) ) {
                $this->members = $this->_getMembersFromJobqueue( $this->jobid );
                list ( $this->done, $this->total ) = $this->_getJobProgress($this->jobid);
                return true;
            }
        }
    }

    // Helper functions

    /**
     * Create localised time based on utc + offset;
     *
     * @param string $time
     * @return string
     */
    function _check_guild_exist( $name, $server, $region ) {
        global $addon;

        require_once ($addon['dir'] . 'inc/armorysync.class.php');

        $as = new ArmorySync;
        return $as->checkGuildInfo( $name, $server, $region );
    }
    /**
     * Create localised time based on utc + offset;
     *
     * @param string $time
     * @return string
     */
    function _getLocalisedTime ( $time = false ) {
        global $roster;

        $offset = $roster->config['localtimeoffset'] * 60 * 60;
        $stamp = strtotime( $time );
        $stamp += $offset;
        $ret = date("d.m H:i:s", $stamp);
        return $ret;
    }
    /**
     * Creates a progress bar
     *
     */
    function _getProgressBar($step, $total) {
        global $roster;

        $perc = 0;
        if ( $total == 0 ) {
            $perc = 100;
        } else {
            $perc = round ($step / $total * 100);
        }
        $per_left = 100 - $perc;
        $pb = "<table class=\"main_roster_menu\" cellspacing=\"0\" cellpadding=\"0\" border=\"1\" align=\"center\" width=\"200\" id=\"Table1\">";
        $pb .= "<tr>";
        $pb .= "    <td id=\"progress_text\" class=\"header\" colspan=\"2\" align=\"center\">";
        $pb .= "        $perc% ". $roster->locale->act['complete']. " ($step / $total)";
        $pb .= "    </td>";
        $pb .= "</tr>";
        $pb .= "<tr id=\"progress_bar\">";
        if ( $perc ) {
            $pb .= "	<td bgcolor=\"#660000\" height=\"12px\" width=\"$perc%\">" ;
            $pb .= "	</td>";
        }
        if ( $per_left ) {
            $pb .= "	<td bgcolor=\"#FFF7CE\" height=\"12px\" width=\"$per_left%\">";
            $pb .= "        </td>";
        }
        $pb .= "</tr>";
        $pb .= "</table>";
        return $pb;
    }


    /**
     * scope based __link call
     *
     */
    function _checkAuth( $scope = false ) {
        global $roster, $addon;

        if ( !$scope ) {
            return false;
        }

        $roster_login = new RosterLogin();
        if( $roster_login->getAuthorized() < $addon['config'][$scope] )
        {
            print
            '<span class="title_text">'. $this->title. '</span><br />'.
            $roster_login->getMessage().
            $roster_login->getLoginForm($addon['config'][$scope]);
            return false;
        } else {
            return true;
        }


    }

    /**
     * Create java reload code
     *
     * @param string $link
     */

    function _link ( $link = '' ) {
        global $roster, $addon;

        $reloadTime = $addon['config']['armorysync_reloadwaittime'] * 500;
        $link = 'ajax.php?addon=armorysync&method=armorysync_status_update&cont=doUpdateStatus';
        if ( $this->ajaxDebug ) {
            $link .= '&XDEBUG_SESSION_START=test';
        }

        if ( $addon['config']['armorysync_use_ajax'] ) {
            $header = '
<script type="text/javascript">
<!--
    function nextStep() {
        loadXMLDoc(\''. ROSTER_URL. $link. '\',\'job_id='. $this->jobid. '&memberlist='. $this->isMemberList. '&scope='. $roster->scope. '&page='. ( isset($roster->pages[2]) ? $roster->pages[2] : '' ). '\');
    }
    self.setTimeout(\'nextStep()\', '. $reloadTime. ');
//-->
    </script>
';
//XDEBUG_SESSION_START=test

        } else {

            $header = '
<script type="text/javascript">
<!--
    function nextMember() {
        document.linker.submit();
    }
    self.setTimeout(\'nextMember()\', '. $reloadTime. ');
//-->
</script>
';
        }
        $this->header .= $header;
    }

    /**
     * Create java reload code
     *
     * @param string $link
     */

    function _showStartPage () {
        global $roster, $addon;

        $message = '<br />';
        if ( $roster->scope == 'char' ) {
            $message .= sprintf( $roster->locale->act['start_message'], $roster->locale->act['start_message_the_char'], $roster->data['name'], $roster->locale->act['start_message_this_char']);
        } elseif ( $roster->scope == 'guild' ) {
            $message .= sprintf( $roster->locale->act['start_message'], $roster->locale->act['start_message_the_guild'], $roster->data['guild_name'], $roster->locale->act['start_message_this_guild']);
        } elseif ( $roster->scope == 'realm' ) {
            $message .= sprintf( $roster->locale->act['start_message'], $roster->locale->act['start_message_the_realm'], $roster->data['region'].'-'.$roster->data['server'], $roster->locale->act['start_message_this_realm']);
        }

        $message .= '<img src="' . $roster->config['img_url'] . 'blue-question-mark.gif" alt="?" />
                    <br /><br />
                    <form action="' . makelink() . '" method="post" id="allow">
                    <input type="hidden" id="start" name="action" value="" />
                    <input type="hidden" name="job_id" value="" />
                    <button type="submit" class="input" onclick="setvalue(\'job_id\',\'0\');setvalue(\'start\',\'start\');">' . $roster->locale->act['start'] . '</button>
                    </form>
                    <br />';


        print '<div style="height:1px; width:1px; overflow:visible;">';
        print '<img src="'. $addon['image_path']. 'as_logo.png" style="position: relative; left:420px; bottom:125px; height:250px;" alt="" />';
        print '</div>';
        print messagebox( $message, $this->title,'sred', '500px');
    }

    // DB functions

    /**
     * Get realm members that match prerequesists from db for update
     *
     * @return array ()
     */
    function _getRealmMembersToUpdate(){
        global $roster;

        return $this->_getMembersToUpdate("members.server = \"". $roster->data['server']. "\" AND members.region = \"". $roster->data['region']. "\" AND NOT members.guild_id = 0 AND " );
    }

    /**
     * Get guild members that match prerequesists from db for update
     *
     * @return array ()
     */
    function _getGuildMembersToUpdate(){
        global $roster;

        return $this->_getMembersToUpdate("members.guild_id = ". $roster->data['guild_id']. " AND " );
    }

    /**
     * Get that match prerequesists from db for update
     *
     * @return array ()
     */
    function _getMembersToUpdate( $where = false ){
        global $roster, $addon;

        $query =    "SELECT members.member_id, members.name, " .
                    "guild.guild_id, guild.guild_name, guild.server, guild.region ".
                    "FROM `".$roster->db->table('members')."` members ".
                    "LEFT JOIN `".$roster->db->table('guild')."` guild " .
                    "ON members.guild_id = guild.guild_id " .
                    "LEFT JOIN `". $roster->db->table('updates',$addon['basename']). "` updates ".
                    "ON members.member_id = updates.member_id ".
                    "WHERE ". $where.
                    "members.level >= " . $addon['config']['armorysync_minlevel'] . " " .
                    "AND ( ".
                    "   ISNULL(updates.dateupdatedutc) ".
                    "   OR ".
                    "   updates.dateupdatedutc <= DATE_SUB(UTC_TIMESTAMP(), INTERVAL " . $addon['config']['armorysync_synchcutofftime'] . " DAY) ".
                    " ) ".
                    "ORDER BY members.member_id;";
                    //"ORDER BY members.member_id ".
                    //"LIMIT 5;";



        $result = $roster->db->query($query);
        if( $roster->db->num_rows($result) > 0 ) {
            return $roster->db->fetch_all();
        } else {
            return array();
        }

    }

    /**
     * Get guild members that match prerequesists from db for update
     *
     * @param string $starttimeutc
     * @return int $jobid
     */
    function _insertJobID( $starttimeutc = '' ) {
        global $roster, $addon;

        $query =    "INSERT INTO ". $roster->db->table('jobs',$addon['basename']). " ".
                    "SET starttimeutc=".'"'. $starttimeutc. '"'.";";

        $result = $roster->db->query($query);
        if ( $result ) {
            $query = "SELECT LAST_INSERT_ID();";
            $jobid = $roster->db->query_first($query);
            if ( $jobid ) {
                return $jobid;
            } else {
                print "Error fetching id <br />\n";
                return false;
            }
        } else {
            print "Error inserting jobid<br />\n";
            return false;
        }

    }

    /**
     * Get job starttime from db
     *
     * @param int $jobid
     * @return string $starttime
     */
    function _getJobStartTime( $jobid = 0 ) {
        global $roster, $addon;

        $query =    "SELECT starttimeutc ".
                    "FROM `". $roster->db->table('jobqueue',$addon['basename']). "` ".
                    "WHERE job_id=". $jobid;
        $starttime = $roster->db->query_first($query);
        if ( $starttime ) {
            return $starttime;
        } else {
            return false;
        }
    }

    /**
     * Inserts members to jobqueue
     *
     * @param int $jobid
     * @param array $members
     * @return bool
     */
    function _insertMembersToJobqueue( $jobid = 0, $members = array() ) {
        global $roster, $addon;

        if ( array_keys( $members ) ) {

            $query =    "INSERT INTO ". $roster->db->table('jobqueue',$addon['basename']). " ".
                        "VALUES ";
            foreach ( $members as $member ) {
                $query .= "(".
                            $jobid. ", ".
                            ( $member['member_id'] ? $member['member_id'] : 0 ). ", ".
                            '"'.$roster->db->escape($member['name']). '"'.", ".
                            $member['guild_id']. ", ".
                            '"'.$roster->db->escape($member['guild_name']). '"'.", ".
                            '"'.$roster->db->escape($member['server']). '"'.", ".
                            '"'.$roster->db->escape($member['region']). '"'.", ".
                            "NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL), ";
            }
            $query = preg_replace('/, $/', ';', $query);
            $result = $roster->db->query($query);
            if ( $result ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Fetches members from jobqueue
     *
     * @param int $jobid
     * @return array $members
     */
    function _getMembersFromJobqueue( $jobid = 0 ) {
        global $roster, $addon;

        $query =    "SELECT * ".
                    "FROM `". $roster->db->table('jobqueue',$addon['basename']). "` ".
                    "WHERE job_id=". $jobid. " ".
                    "ORDER BY member_id;";


        $result = $roster->db->query($query);
        if( $roster->db->num_rows($result) > 0 ) {
            return $roster->db->fetch_all();
        } else {
            return array();
        }

    }

    /**
     * Fetches member which status was updated last
     *
     * @param int $jobid
     * @return array $member
     */
    function _isPostSyncStatus ( $jobid = 0 ) {
        global $roster, $addon;

        $query =    "SELECT * ".
                    "FROM `". $roster->db->table('jobqueue',$addon['basename']). "` ".
                    "WHERE job_id=". $jobid. " ".
                    "AND NOT ISNULL(starttimeutc) AND ISNULL(stoptimeutc);";

        $result = $roster->db->query($query);
        if( $roster->db->num_rows($result) > 0 ) {
            $member = $roster->db->fetch_all();
            return $member[0];
        } else {
            return false;
        }
    }

    /**
     * Fetches job progress
     *
     * @param int $jobid
     * @return array $progress
     */
    function _getJobProgress ( $jobid = 0 ) {
        return array($this->_getJobDone($jobid), $this->_getJobTotal($jobid));
    }

    /**
     * Fetches total number of members to sync
     *
     * @param int $jobid
     * @return array $progress
     */
    function _getJobTotal ( $jobid = 0 ) {
        global $roster, $addon;

        $query =    "SELECT ".
                    "COUNT(member_id) as total ".
                    "FROM `". $roster->db->table('jobqueue',$addon['basename']). "` ".
                    "WHERE job_id=". $jobid. ";";

        $result = $roster->db->query_first($query);
        if( $result ) {
            return $result;
        } else {
            return 0;
        }
    }

    /**
     * Fetches total number of members to sync
     *
     * @param int $jobid
     * @return array $progress
     */
    function _getJobDone ( $jobid = 0 ) {
        global $roster, $addon;

        $query =    "SELECT ".
                    "COUNT(member_id) as done ".
                    "FROM `". $roster->db->table('jobqueue',$addon['basename']). "` ".
                    "WHERE job_id=". $jobid. " ".
                    "AND NOT ISNULL(stoptimeutc);";


        $result = $roster->db->query_first($query);
        if( $result ) {
            return $result;
        } else {
            return 0;
        }
    }

    /**
     * Fetches member which status will be updated next
     *
     * @param int $jobid
     * @return array $member
     */
    function _getNextMemberToUpdate ( $jobid = 0 ) {
        return $this->_getNextToUpdate( $jobid, 'member_id' );
    }

    /**
     * Fetches guild which status will be updated next
     *
     * @param int $jobid
     * @return array $member
     */
    function _getNextGuildToUpdate ( $jobid = 0 ) {
        return $this->_getNextToUpdate( $jobid, 'guild_id' );
    }

    /**
     * Fetches next whatever which status will be updated next
     *
     * @param int $jobid
     * @param string $field
     * @return array $member
     */
    function _getNextToUpdate ( $jobid = 0, $field = false ) {
        global $roster, $addon;

        if ( $field == false ) {
            return false;
        }

        $query =    "SELECT MIN(". $field. ") ". $field. " ".
                    "FROM `". $roster->db->table('jobqueue',$addon['basename']). "` ".
                    "WHERE job_id=". $jobid. " ".
                    "AND ISNULL(starttimeutc) AND ISNULL(stoptimeutc);";
        $id = $roster->db->query_first($query);
        if ( $id ) {

            $query =    "SELECT * ".
                        "FROM `". $roster->db->table('jobqueue',$addon['basename']). "` ".
                        "WHERE job_id=". $jobid. " ".
                        "AND ". $field. "=". $id. ";";
            $result = $roster->db->query($query);
            if( $roster->db->num_rows($result) > 0 ) {
                $next = $roster->db->fetch_all();
                return $next[0];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Updates Members job status in jobqueue
     *
     * @param int $jobid
     * @param array $member
     * @return bool
     */
    function _updateMemberJobStatus ( $jobid = 0, $member = array() ) {
        return $this->_updateJobStatus( $jobid, $member, 'member_id' );
    }

    /**
     * Updates Guilds job status in jobqueue
     *
     * @param int $jobid
     * @param array $member
     * @return bool
     */
    function _updateGuildJobStatus ( $jobid = 0, $member = array() ) {
        return $this->_updateJobStatus( $jobid, $member, 'guild_id' );
    }

    /**
     * Updates job status in jobqueue
     *
     * @param int $jobid
     * @param array $member
     * @return bool
     */
    function _updateJobStatus ( $jobid = 0, $member = array(), $field = false ) {
        global $roster, $addon;

        if ( $field == false ) {
            return false;
        }

        $query =    "UPDATE `". $roster->db->table('jobqueue',$addon['basename']). "` ".
                    "SET ";

        $set = '';
        isset ( $member['guild_info'] ) ? $set .= "guild_info=". '"'.$roster->db->escape($member['guild_info']). '"'. ", " : 1;
        isset ( $member['character_info'] ) ? $set .= "character_info=". $member['character_info']. ", " : 1;
        isset ( $member['skill_info'] ) ? $set .= "skill_info=". $member['skill_info']. ", " : 1;
        isset ( $member['reputation_info'] ) ? $set .= "reputation_info=". $member['reputation_info']. ", " : 1;
        isset ( $member['equipment_info'] ) ? $set .= "equipment_info=". $member['equipment_info']. ", " : 1;
        isset ( $member['talent_info'] ) ? $set .= "talent_info=". $member['talent_info']. ", " : 1;

        isset ( $member['starttimeutc'] ) ? $set .= "starttimeutc=".'"'. $roster->db->escape($member['starttimeutc']). '"'.", " : 1;
        isset ( $member['stoptimeutc'] ) ? $set .= "stoptimeutc=".'"'. $roster->db->escape($member['stoptimeutc']). '"'.", " : 1;
        isset ( $member['log'] ) ? $set .= "log=".'"'. $roster->db->escape($member['log']). '"'.", " : 1;
        $set = preg_replace( '/, $/', ' ', $set );
        $query .= $set;

        $query .=   "WHERE job_id=". $jobid. " ".
                    "AND ". $field. "=". $member[$field]. ";";

        $result = $roster->db->query($query);
        if ( $result ) {
            if ( ! $this->dataNotAccepted && isset ( $member['stoptimeutc'] ) && $field == 'member_id' && isset ( $member['character_info'] ) ) {
                $query =    "INSERT INTO `". $roster->db->table('updates',$addon['basename']). "` ".
                            "SET ".
                            "member_id=". $member[$field].", ".
                            "dateupdatedutc='". $roster->db->escape(gmdate('Y-m-d H:i:s')). "' ".
                            "ON DUPLICATE KEY UPDATE ".
                            "dateupdatedutc='". $roster->db->escape(gmdate('Y-m-d H:i:s')). "';";
                if ( !$roster->db->query($query) ) {
                    die_quietly($roster->db->error(),'Database Error',__FILE__,__LINE__,$query);
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Deletes job from jobqueue
     *
     * @param int $jobid
     */
    function _cleanUpJob ( $jobid = 0 ) {
        global $roster, $addon;

        $query =    "DELETE FROM `". $roster->db->table('jobqueue',$addon['basename']). "` ".
                    "WHERE job_id=". $jobid. ";";
        $result = $roster->db->query($query);

        $query =    "DELETE FROM `". $roster->db->table('jobs',$addon['basename']). "` ".
                    "WHERE job_id=". $jobid. ";";
        $result = $roster->db->query($query);

        $query =    "SELECT job_id ".
                    "FROM `". $roster->db->table('jobs',$addon['basename']). "` ".
                    "WHERE starttimeutc <= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 3 HOUR);";
        $result = $roster->db->query($query);
        if( $roster->db->num_rows($result) > 0 ) {
            $array = $roster->db->fetch_all();
            foreach ( $array as $job ) {
                $job_id = $job['job_id'];
                $query =    "DELETE FROM `". $roster->db->table('jobqueue',$addon['basename']). "` ".
                            "WHERE job_id=". $job_id. ";";
                $result = $roster->db->query($query);

                $query =    "DELETE FROM `". $roster->db->table('jobs',$addon['basename']). "` ".
                            "WHERE job_id=". $job_id. ";";
                $result = $roster->db->query($query);
            }
        }
    }

    /**
     * Inserts UploadRule
     *
     * @param string $name
     * @param string $server
     * @param string $region
     */
    function _insert_uploadRule( $name, $server, $region ) {
        global $roster;

        $query =    "SELECT ".
                    "rule_id ".
                    "FROM `". $roster->db->table('upload'). "` ".
                    "WHERE ".
                    "name='". $roster->db->escape($name). "' ".
                    "AND server='". $roster->db->escape($server). "' ".
                    "AND region='". strtoupper($region). "';";
        $id = $roster->db->query_first($query);

        if ( ! $id ) {
            $query =    "INSERT ".
                        "INTO `". $roster->db->table('upload'). "` ".
                        "(`name`,`server`,`region`,`type`,`default`) VALUES ".
                        "('" . $roster->db->escape($name) . "','" . $roster->db->escape($server) . "','" . strtoupper($region) . "','0','0');";

            if ( !$roster->db->query($query) ) {
                    die_quietly($roster->db->error(),'Database Error',__FILE__,__LINE__,$query);
            } else {
                return true;
            }
        } else {
            return true;
        }

    }

    /**
     * Inserts new guild
     *
     * @param string $name
     * @param string $server
     * @param string $region
     */
    function _insert_guild( $name, $server, $region ) {
        global $roster;

        $query =    "SELECT ".
                    "guild_id ".
                    "FROM `". $roster->db->table('guild'). "` ".
                    "WHERE ".
                    "`guild_name`='". $roster->db->escape($name). "' ".
                    "AND `server`='". $roster->db->escape($server). "' ".
                    "AND `region`='". $roster->db->escape($region). "';";
        $id = $roster->db->query_first($query);

        if ( $id ) {
            return $id;
        }

        $query =    "INSERT ".
                    "INTO `". $roster->db->table('guild'). "` ".
                    "SET ".
                    "`guild_name`='". $roster->db->escape($name). "', ".
                    "`server`='". $roster->db->escape($server). "', ".
                    "`region`='". $roster->db->escape($region). "';";

        if ( !$roster->db->query($query) ) {
            die_quietly($roster->db->error(),'Database Error',__FILE__,__LINE__,$query);
        } else {
            $query = "SELECT LAST_INSERT_ID();";
            $jobid = $roster->db->query_first($query);
            if ( $jobid ) {
                return $jobid;
            } else {
                return false;
            }
        }
    }

}

<?php
/** 
 * Dev.PKComp.net WoWRoster Addon
 * 
 * LICENSE: Licensed under the Creative Commons 
 *          "Attribution-NonCommercial-ShareAlike 2.5" license 
 * 
 * @copyright  2005-2007 Pretty Kitty Development 
 * @license    http://creativecommons.org/licenses/by-nc-sa/2.5   Creative Commons "Attribution-NonCommercial-ShareAlike 2.5" 
 * @link       http://dev.pkcomp.net 
 * @package    Accounts 
 * @subpackage User Class
 */
if( !defined('IN_ROSTER') )
{
    exit('Detected invalid access to this file!');
}

class accountsUser extends accounts
{
	var $id = 0; // the current user's id
	var $info = array();
	var $active = false;
	var $message = '';
	
	function accountsUser()
	{
		global $roster, $addon;
		
	}

	function checkUser($user, $password, $pass = '')
	{
		global $roster, $addon, $accounts;

		switch ($pass)
		{
			case 'new': 
				$sql = sprintf("SELECT COUNT(*) AS `check` FROM %s WHERE `email` = '%s' OR `uname` = '%s'", $accounts->db['usertable'], $this->info['email'], $this->info['uname']);
				break;
			case 'lost':
				$sql = sprintf("SELECT COUNT(*) AS `check` FROM %s WHERE `email` = '%s' AND `active` = '1'", $accounts->db['usertable'], $this->info['email']);
				break;
			case 'new_pass':
				$sql = sprintf("SELECT COUNT(*) AS `check` FROM %s WHERE `pass` = '%s' AND `uid` = %d", $accounts->db['usertable'], $this->info['pass'], $this->id);
				break;
			case 'active':
				$sql = sprintf("SELECT COUNT(*) AS `check` FROM %s WHERE `uid` = %d AND `active` = '0'", $accounts->db['usertable'], $this->id);
				break;
			case 'validate':
				$sql = sprintf("SELECT COUNT(*) AS `check` FROM %s WHERE `uid` = %d AND `tmp_mail` <> ''", $accounts->db['usertable'], $this->id);
				break;
			default:
				$password = (strlen($password) < 32) ? $password : $password;
				$sql = sprintf("SELECT COUNT(*) AS `check` FROM %s WHERE `uname` = '%s' AND `pass` = '%s' AND `active` = '1'", $accounts->db['usertable'], $user, $password);
		}
		$result = $roster->db->query($sql) or die_quietly($roster->db->errno() . $roster->db->error());
		if ($roster->db->result($result, 0, "check") == 1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function getInfo($user, $password)
	{
		global $roster, $addon, $accounts;
		
		$sql_info = sprintf("SELECT * FROM %s WHERE `uname` = '%s' AND `pass` = '%s'", $accounts->db['usertable'], $user, $password);
		$results = $roster->db->query($sql_info);
		
		if( !$results || $roster->db->num_rows($results) == 0 )
		{
			die_quietly("Cannot get user information from database<br />\nMySQL Said: " . $roster->db->error() . "<br /><br />\n");
		}

		while( $row = $roster->db->fetch($results, SQL_ASSOC) )
		{
			foreach($row as $info)
			{
				foreach($row as $key => $value)
				{
					$this->info[$key] = $value;
				}
			}
		}
		$roster->db->free_result($results);
		
		return;
	}

	function checkNewGroup( $pass )
	{
		global $roster, $addon, $accounts;

		$query = "SELECT * FROM `".$roster->db->table('account')."` ORDER BY `account_id` DESC;";
		$result = $roster->db->query($query);

		if( !$result )
		{
			$this->message = 'The Group Password could not be found!';
		}

		while( $row = $roster->db->fetch($result) )
		{
			if( ( $row['hash'] == md5($pass) ) ||
				( $row['hash'] == $pass )
			)
			{
				$allow_login = $row['account_id'];

				$roster->db->free_result($result);
				return $allow_login;
			}
			else
			{
				$allow_login = $addon['config']['acc_min_access'];
			}
		}
		$roster->db->free_result($result);

		return $allow_login;
	}

	function checkNewPass($pass, $passConfirm)
	{
		global $roster, $addon, $accounts;
		
		if ($pass == $passConfirm)
		{
			if (strlen($pass) >= $addon['config']['acc_pass_length'])
			{
				return true;
			}
			else
			{
			    $this->message = sprintf($roster->locale->act['acc_user']['msg32'], $addon['config']['acc_pass_length']);
				return false;
			}
		}
		else
		{
			$this->message = $roster->locale->act['acc_user']['msg38'];
			return false;
		}	
	}

	function checkEMail($mail_address)
	{
		global $roster, $addon, $accounts;
		
		if (preg_match("/^[0-9a-z]+(([\.\-_])[0-9a-z]+)*@[0-9a-z]+(([\.\-])[0-9a-z-]+)*\.[a-z]{2,4}$/i", $mail_address))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function clean($value, $type = '')
	{
		global $roster, $addon, $accounts;
		
		$value = (!get_magic_quotes_gpc()) ? addslashes($value) : $value;
		switch ($type)
		{
			case 'int':
				$value = ($value != '') ? intval($value) : NULL;
				break;
			default:
				$value = ($value != '') ? "'" . $value . "'" : "''";
		}
		return $value;
	}

	function register($first_uname, $first_pass, $confirmPass, $first_fname, $first_lname, $first_group, $first_email)
	{
		global $roster, $addon, $accounts;
		
		if ($this->checkNewPass($first_pass, $confirmPass))
		{
			if (strlen($first_uname) >= $addon['config']['acc_uname_length'])
			{
				if ($this->checkEMail($first_email))
				{
					$this->info['email'] = $first_email;
					$this->info['uname'] = $first_uname;
					if ($this->checkUser('','','new'))
					{
						$this->message = $roster->locale->act['acc_user']['msg12'];
					}
					else
					{
						$group = $this->checkNewGroup($first_group);
						$sql = sprintf("INSERT INTO %s (`uid`, `uname`, `pass`, `fname`, `lname`, `date_joined`, `email`, `group_id`, `active`) VALUES (NULL, %s, %s, %s, %s, %s, %s, %d, '0')", 
						$accounts->db['usertable'],
						$this->clean($first_uname),
						$this->clean(md5($first_pass)),
						$this->clean($first_fname),
						$this->clean($first_lname),
						$this->clean(date('Y-m-d')),
						$this->clean($this->info['email']),
						$group);
						$ins_res = $roster->db->query($sql);
						if ($ins_res)
						{
							$this->id = $roster->db->insert_id();
							$this->info['pass'] = $first_pass;
							if ($this->sendMail($this->info['email']))
							{
								$this->message = $roster->locale->act['acc_user']['msg13'];
							}
							else
							{
								$roster->db->query(sprintf("DELETE FROM %s WHERE `uid` = %d", $accounts->db['usertable'], $this->id));
								$this->message = $roster->locale->act['acc_user']['msg14'];
							}
						}
						else
						{
							$this->message = $roster->locale->act['acc_user']['msg15'];
						}
					}
				}
				else
				{
					$this->message = $roster->locale->act['acc_user']['msg16'];
				}
			}
			else
			{
			    $this->message = sprintf($roster->locale->act['acc_user']['msg17'], $addon['config']['acc_uname_length']);
			}
		}
	}

	function validateEMail($validation_key, $key_id)
	{
		global $roster, $addon, $accounts;
		
		if ($validation_key != '' && strlen($validation_key) == 32 && $key_id > 0)
		{
			$this->id = $key_id;
			if ($this->checkUser('', '', 'validate'))
			{
				$upd_sql = sprintf("UPDATE %s SET `email` = `tmp_mail`, `tmp_mail` = '' WHERE `uid` = %d AND `pass` = '%s'", $accounts->db['usertable'], $key_id, $validation_key);
				if ($roster->db->query($upd_sql))
				{
					$this->message = $roster->locale->act['acc_user']['msg18'];
				}
				else
				{
					$this->message = $roster->locale->act['acc_user']['msg19'];
				}
			}
			else
			{
				$this->message = $roster->locale->act['acc_user']['msg34'];
			}
		}
		else
		{
			$this->message = $roster->locale->act['acc_user']['msg21'];
		}
	}
	
	function activateAccount($activate_key, $key_id)
	{
		global $roster, $addon, $accounts;
		
		if ($activate_key != '' && strlen($activate_key) == 32 && $key_id > 0)
		{
			$this->id = $key_id;
			if ($this->checkUser('','','active'))
			{
				if ($addon['config']['acc_auto_act'] == 1)
				{
					$upd_sql = sprintf("UPDATE %s SET `active` = '1' WHERE `uid` = %d AND `pass` = '%s'", $accounts->db['usertable'], $key_id, $activate_key);
					if ($roster->db->query($upd_sql))
					{
						if ($this->sendConfirmation($key_id))
						{
							$this->message = $roster->locale->act['acc_user']['msg18'];
						}
						else
						{
							$this->message = $roster->locale->act['acc_user']['msg14'];
						}
					}
					else
					{
						$this->message = $roster->locale->act['acc_user']['msg19'];
					}
				}
				else
				{
					if ($this->sendMail($addon['config']['acc_admin_mail'], 0, true))
					{
						$this->message = $roster->locale->act['acc_user']['msg36'];
					}
					else
					{
						$this->message = $roster->locale->act['acc_user']['msg14'];
					}
				}
			}
			else
			{
				$this->message = $roster->locale->act['acc_user']['msg20'];
			}
		}
		else
		{
			$this->message = $roster->locale->act['acc_user']['msg21'];
		}
	}

	function sendConfirmation($uid)
	{
		global $roster, $addon, $accounts;
		
		$sql = sprintf("SELECT `email` FROM %s WHERE `uid` = %d", $accounts->db['usertable'], $uid);
		$userEMail = $roster->db->result($roster->db->query($sql), 0, 'email');
		
		$message = sprintf($roster->locale->act['acc_user']['msg37'], $this->info['uname'], makelink('util-accounts', true), $addon['config']['acc_admin_name']);
		
		if ($this->sendMail($userEMail, $message))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function sendMail($mail_address, $message = '')
	{
		global $roster, $addon, $accounts;
		
		if (!$message)
		{
			$message = sprintf($roster->locale->act['acc_user']['msg29'], $this->info['uname'], makelink('util-accounts-activate', true), $this->id, md5($this->info['pass']));
		}
		
		$header = "From: \"" . $addon['config']['acc_admin_name'] . "\" <" . $addon['config']['acc_admin_mail'] . ">\r\n";
		$header .= "MIME-Version: 1.0\r\n";
		$header .= "Mailer: Olaf's mail script version 1.11\r\n";
		$header .= "Content-Type: text/plain; charset=\"iso-8859-1\"\r\n";
		$header .= "Content-Transfer-Encoding: 7bit\r\n";
		if (!$addon['config']['acc_auto_act'])
		{
			$subject = $roster->locale->act['acc_user']['msg26'];
			$body = sprintf($roster->locale->act['acc_user']['msg39'], date('Y-m-d'), makelink('rostercp-addon-accounts', true));
		}
		else
		{
			$subject = $roster->locale->act['acc_user']['msg28'];
			$body = $message;
		}
		if (mail($mail_address, $subject, $body, $header))
		{
			return true;
		}
		else
		{
			return false;
		} 
	}

function forgotPass($forgot_email)
	{ 
		global $roster, $accounts, $addon;
		
		if ($this->checkEMail($forgot_email))
		{
			$this->info['email'] = $forgot_email;
			if (!$this->checkUser('','','lost'))
			{
				$this->message = $roster->locale->act['acc_user']['msg22'];
			}
			else
			{
				$forgot_sql = sprintf("SELECT `uname`, `uid`, `pass` FROM `%s` WHERE `email` = '%s'", $accounts->db['usertable'], $this->info['email']);
				if ($forgot_result = $roster->db->query($forgot_sql))
				{
					$this->user = $roster->db->result($forgot_result, 0, 'uname');
					$this->uid = $roster->db->result($forgot_result, 0, 'uid');
					$this->userPass = $roster->db->result($forgot_result, 0, 'pass');
					
					$roster->db->free_result($forgot_result);
					
					$actPage = makelink('util-accounts-activate', true);
					
					$message = sprintf($roster->locale->act['acc_user']['msg35'], $this->info['uname'], urldecode($actPage), $this->info['uid'], $this->info['pass']);
					if ($this->sendMail($this->info['email'], $message))
					{
						$this->message = $roster->locale->act['acc_user']['msg23'];
					}
					else
					{
						$this->message = $roster->locale->act['acc_user']['msg14'];
					}
				}
				else
				{
					$this->message = $roster->locale->act['acc_user']['msg15'];
				}
			}
		}
		else
		{
			$this->message = $roster->locale->act['acc_user']['msg16'];
		}
	}

	function checkActivationPass($controle_str, $uid)
	{
		global $roster, $addon, $accounts;
		
		if ($controle_str != '' && strlen($controle_str) == 32 && $uid > 0)
		{
			$password = $controle_str;
			if ($this->checkUser($uid, '', $password, '', 'new_pass'))
			{
				$sql_get_user = sprintf("SELECT `uname` FROM %s WHERE `pass` = '%s' AND `uid` = %d", $accounts->db['usertable'], $this->info['pass'], $uid);
				$get_user = $roster->db->query($sql_get_user);
				$this->info['uname'] = $roster->db->result($get_user, 0, 'uname'); // end fix
				return true;
			}
			else
			{
				$this->message = $roster->locale->act['acc_user']['msg21'];
				return false;
			}
		}
		else
		{
			$this->message = $roster->locale->act['acc_user']['msg21'];
			return false;
		}
	}

}
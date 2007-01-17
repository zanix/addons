<?php
/**
 * Project: SigGen - Signature and Avatar Generator for WoWRoster
 * File: /siggen.php
 *
 * Licensed under the Creative Commons
 * "Attribution-NonCommercial-ShareAlike 2.5" license
 *
 * Short summary:
 *  http://creativecommons.org/licenses/by-nc-sa/2.5/
 *
 * Legal Information:
 *  http://creativecommons.org/licenses/by-nc-sa/2.5/legalcode
 *
 * Full License:
 *  license.txt (Included within this library)
 *
 * You should have recieved a FULL copy of this license in license.txt
 * along with this library, if you did not and you are unable to find
 * and agree to the license you may not use this library.
 *
 * For questions, comments, information and documentation please visit
 * the official website at cpframework.org
 *
 * @link http://www.wowroster.net
 * @license http://creativecommons.org/licenses/by-nc-sa/2.5/
 * @author Joshua Clark
 * @version 0.2.0
 * @copyright 2005-2007 Joshua Clark
 * @package SigGen
 * @filesource
 *
 * $Id:$
 */

// Disable generation of headers from settings.php
$no_roster_headers = true;

require('../../settings.php');			// "settings.php" from WoWRoster

require( ROSTER_BASE.'addons/siggen/conf.php' );				// Require the siggen config

require( SIGGEN_DIR.'localization.php' );		// Translation file


// Set track errors on
if( !ereg('ini_set', ini_get('disable_functions')) )
{
	ini_set('track_errors',1);
}


// Get name from browser request
// url_decode() the name, then utf-8_encode() it
if( isset($_GET['name']) )
{
	$char_name = utf8_encode(urldecode($_GET['name']));
}
elseif( isset($_SERVER['PATH_INFO']) ) // Try pulling from a "path_info" request
{
	$char_name = utf8_encode(urldecode(substr( $_SERVER['PATH_INFO'],1,-4 )));
}


// Get image mode ( signature | avatar | etc )
if( isset($_GET['mode']) )
{
	$config_name = $_GET['mode'];
}
elseif( isset($config_name) )
{
	$config_name = $config_name;
}
else
{
	if( eregi(basename(__FILE__),$_SERVER['PHP_SELF']) )
	{
		debugMode('?',"You cannot access this file directly without a 'mode' option");
	}
}


// Web selectable "save only" mode
if( isset($_GET['saveonly']) )
{
	if( $_GET['saveonly'] == '1' )
	{
		$sig_saveonly = 1;
	}
	elseif( $_GET['saveonly'] == '0' )
	{
		$sig_saveonly = 0;
	}
}


// Web selectable etag mode
if( isset($_GET['etag']) )
{
	if( $_GET['etag'] == 1 )
	{
		$etag_override = 1;
	}
	elseif( $_GET['etag'] == 0 )
	{
		$etag_override = 0;
	}
}


#--[ MYSQL CONNECT AND STORE ]---------------------------------------------

	// Read SigGen Config data from Database
	$config_str = 'SELECT * FROM `'.ROSTER_SIGCONFIGTABLE."` WHERE `config_id` LIKE '$config_name';";
	$config_sql = $wowdb->query($config_str);
	if( $config_sql && $wowdb->num_rows($config_sql) != 0 )
	{
		$configData = $wowdb->fetch_array($config_sql);
	}
	else
	{
		debugMode('DB',"Could not find config_id [$config_name] in table [".ROSTER_SIGCONFIGTABLE."]",(__FILE__),0,'MySQL said: '.$wowdb->error());
	}
	$wowdb->free_result($config_sql);


	if( $sc_db_ver != $configData['db_ver'] )
	{
		debugMode('DB','The database has been changed/upgraded','',0,'You need to run SigGen Config before using the signatures');
	}


	// Read guild data from Database
	// This is so easy now that Roster gets this :D
	$guildData = $guild_info;



	// Read member list from Database
	$members_str = 'SELECT * FROM `'.ROSTER_MEMBERSTABLE."` WHERE `name` LIKE '$char_name' AND `guild_id` = ".$guild_info['guild_id'].";";
	$members_sql = $wowdb->query($members_str);
	if( $members_sql )
	{
		$membersData = $wowdb->fetch_array($members_sql);
		$member_id = $membersData['member_id'];		// Gets the character ID number from the database
	}
	else
	{
		debugMode('DB','Could not get Members Data','',0,"MySQL said: ".$wowdb->error());
	}

	// If the member is not found, write message to name
	if( $wowdb->num_rows($members_sql) == 0 )
	{
		$membersData['name'] = $configData['default_message'];
	}
	$wowdb->free_result($members_sql);


	// Read character data from Database
	$players_str = 'SELECT * FROM `'.ROSTER_PLAYERSTABLE."` WHERE `member_id` = '$member_id';";
	$players_sql = $wowdb->query($players_str);
	if( $players_sql )
	{
		$playersData = $wowdb->fetch_array($players_sql);
	}
	else
	{
		debugMode('DB','Could not get Character Data','',0,"MySQL said: ".$wowdb->error());
	}
	$wowdb->free_result($players_sql);


	// Read skills_table from Database
	if( $member_id )
	{
		$skill_str = 'SELECT * FROM `'.ROSTER_SKILLSTABLE."` WHERE `member_id` = $member_id ORDER BY `skill_order` ASC;";
		$SQL_skill = $wowdb->query($skill_str);

		$skill_rows = $wowdb->num_rows($SQL_skill);

		if( $skill_rows != 0 )
		{
			for( $n=0; $n<$skill_rows; $n++ )
			{
				$tempData = $wowdb->fetch_assoc($SQL_skill);

				list($lvl,$maxlvl) = explode( ':', $tempData['skill_level'] );

				$skillsData[$n] = array('type' => $tempData['skill_type'],
										'name' => $tempData['skill_name'],
										'level' => $lvl,
										'max' => $maxlvl);
			}
		}
		$wowdb->free_result($SQL_skill);
	}

	// Explicitly close the db
	$wowdb->closeDb();


#--[ FIX SOME STUFF ]------------------------------------------------------

	// Replace slashes in directories with system slashes
		$configData['image_dir'] = str_replace( '/',DIR_SEP,SIGGEN_DIR.$configData['image_dir'] );
		$configData['backg_dir'] = str_replace( '/',DIR_SEP,$configData['backg_dir'] );
		$configData['user_dir'] = str_replace( '/',DIR_SEP,$configData['user_dir'] );
		$configData['char_dir'] = str_replace( '/',DIR_SEP,$configData['char_dir'] );
		$configData['class_dir'] = str_replace( '/',DIR_SEP,$configData['class_dir'] );
		$configData['pvplogo_dir'] = str_replace( '/',DIR_SEP,$configData['pvplogo_dir'] );
		$configData['font_dir'] = str_replace( '/',DIR_SEP,ROSTER_BASE.$configData['font_dir'] );
		$configData['save_images_dir'] = str_replace( '/',DIR_SEP,SIGGEN_DIR.$configData['save_images_dir'] );


	// Variable references to DB for quick changing
		$sig_name  = $membersData['name'];
		$sig_exp   = $playersData['exp'];

		$sig_updated  = $playersData['dateupdatedutc'];


	// Get character specific lang if set, or get the roster_lang
		$sig_char_locale = ( empty($playersData['clientLocale']) ? $roster_conf['roster_lang'] : $playersData['clientLocale'] );

	// Get character class from players table first to avoid translation problems
		$sig_class = ( empty($playersData['class']) ? $membersData['class'] : $playersData['class'] );


		$sig_guild_title = $membersData['guild_title'];
		$sig_guild_name  = $guildData['guild_name'];
		$sig_server_name = $guildData['server'];

		$sig_race   = str_replace( ' ','',strtolower( getEnglishValue($playersData['race'],$sig_char_locale) ) );
		$sig_gender = strtolower( getEnglishValue($playersData['sex'],$sig_char_locale) );

		// Remove crap from pvprankicon
		$remove_arr = array('Interface','\\','PvPRankBadges');
		if( file_exists($configData['image_dir'].$configData['pvplogo_dir'].'ext.inc') && is_readable($configData['image_dir'].$configData['pvplogo_dir'].'ext.inc') )
		{
			include( $configData['image_dir'].$configData['pvplogo_dir'].'ext.inc' );
		}
		else
		{
			$pvp_ext = 'png';
		}
		$sig_pvp_icon = str_replace($remove_arr,'',$playersData['RankIcon']).'.'.$pvp_ext;

	// Translate Class Images
		if( file_exists($configData['image_dir'].$configData['class_dir'].'ext.inc') && is_readable($configData['image_dir'].$configData['class_dir'].'ext.inc') )
		{
			include( $configData['image_dir'].$configData['class_dir'].'ext.inc' );
		}
		else
		{
			$class_ext = 'png';
		}
		$sig_class_img = strtolower(getEnglishValue($sig_class,$sig_char_locale)).'.'.$class_ext;


	// Check to remove 'http://'
		$sig_site_name = ( $configData['text_sitename_remove'] ? str_replace('http://','',$roster_conf['website_address']) : $roster_conf['website_address'] );


	// Get player level
		$sig_level = $membersData['level'];


	// Check for PvP rank
	// Stored as none for some chars in database for no rank, so check for that and set to blank
		$sig_pvp_rank = ( $playersData['RankName'] == $wordings[$sig_char_locale]['PvPRankNone'] ? '' : $playersData['RankName'] );


	// Check for etag mode
		$sig_etag_mode = $configData['etag_cache'];

	// e-tag'ing
	// Web request over-ride
	if( isset($etag_override) )
	{
		$sig_etag_mode = $etag_override;
	}

	if( $sig_etag_mode )
	{
		$DTS = strtotime($sig_updated.$configData['db_ver']);
		$condDTS = ( isset($_SERVER['http_if_modified_since']) ? $_SERVER['http_if_modified_since'] : 0 );

		if( isset($_SERVER['HTTP_IF_NONE_MATCH']) && ereg( md5($DTS) , $_SERVER['HTTP_IF_NONE_MATCH']) )
		{
			header( 'HTTP/1.1 304 Not Modified' );
			exit(0);
		}
		elseif( $condDTS && ($_SERVER['REQUEST_METHOD'] == 'GET') && (strtotime($condDTS) >= $DTS) )
		{
			header( 'HTTP/1.1 304 Not Modified' );
			exit(0);
		}
		else
		{
			header( 'Last-Modified: ' . gmdate('D, d M Y H:i:s T', $DTS) );
			header( 'ETag: "{ '.md5($DTS).' }"' );
		}
	}


#--[ FUNCTIONS ]-----------------------------------------------------------

	// Debug function
	function debugMode( $line,$message,$file=null,$config=null,$message2=null )
	{
		global $im;

		// Destroy the image
		if( isset($im) )
			imageDestroy($im);

		if( is_numeric($line) )
			$line -= 1;

		$error_text = 'Error!';
		$line_text  = 'Line: '.$line;
		$file  = ( !empty($file) ? 'File: '.$file : '' );
		$config = ( $config ? 'Check the config file' : '' );
		$message2 = ( !empty($message2) ? $message2 : '' );

		$lines = array();
		$lines[] = array( 's' => $error_text, 'f' => 5, 'c' => 'red' );
		$lines[] = array( 's' => $line_text,  'f' => 3, 'c' => 'blue' );
		$lines[] = array( 's' => $file,       'f' => 2, 'c' => 'green' );
		$lines[] = array( 's' => $message,    'f' => 2, 'c' => 'black' );
		$lines[] = array( 's' => $config,     'f' => 2, 'c' => 'black' );
		$lines[] = array( 's' => $message2,   'f' => 2, 'c' => 'black' );

		$height = $width = 0;
		foreach( $lines as $line )
		{
			if( strlen($line['s']) > 0 )
			{
				$line_width = ImageFontWidth($line['f']) * strlen($line['s']);
				$width = ( ($width < $line_width) ? $line_width : $width );
				$height += ImageFontHeight($line['f']);
			}
		}

		$im = @imagecreate($width+1,$height);
		if( $im )
		{
			$white = imagecolorallocate( $im, 255, 255, 255 );
			$red = imagecolorallocate( $im, 255, 0, 0 );
			$green = imagecolorallocate( $im, 0, 255, 0 );
			$blue = imagecolorallocate( $im, 0, 0, 255 );
			$black = imagecolorallocate( $im, 0, 0, 0 );

			$linestep = 0;
			foreach( $lines as $line )
			{
				if( strlen($line['s']) > 0 )
				{
					imagestring( $im, $line['f'], 1, $linestep, utf8_to_nce($line['s']), $$line['c'] );
					$linestep += ImageFontHeight($line['f']);
				}
			}

			header( 'Content-type: image/gif' );
			imagegif( $im );
			imageDestroy( $im );
		}
		else
		{
			if( !empty($file) )
			{
				$file = "[<span style=\"color:green\">$file</span>]";
			}
			$string = "<strong><span style=\"color:red\">Error!</span></strong>";
			$string .= "<span style=\"color:blue\">Line $line:</span> $message $file\n<br /><br />\n";
			if( $config )
			{
				$string .= "$config\n<br />\n";
			}
			if( !empty($message2) )
			{
				$string .= "$message2\n";
			}
			print $string;
		}

		exit();
	}

	// Get and format eXp
	function printXP( $expval )
	{
		list($current, $max) = explode( ':', $expval );
		if( $current > 0 )
		{
			$for_curr = number_format($current);
			$for_max = number_format($max);
			return $for_curr.' of '.$for_max;
		}
		else
		{
			return '';
		}
	}

	// Get eXp percentage for expbar
	function retPerc( $expval,$loc,$len )
	{
		list($current, $max) = explode( ':', $expval );
		if ( $current > 0 )
		{
			$perc = round( ( ($current / $max)* $len ) + $loc, 0);
			return $perc;
		}
		else
		{
			return 0;
		}
	}

	// Function to set color of text
	function setColor( $image,$color,$trans=0 )
	{
		$red = 100;
		$green = 100;
		$blue = 100;

		$ret = '';
		if( eregi("[#]?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})",$color,$ret) )
		{
			$red = hexdec($ret[1]);
			$green = hexdec($ret[2]);
			$blue = hexdec($ret[3]);
		}

		// Get a transparent color if trans > 0
		if( $trans > 0 )
		{
			$color_index = @imageColorAllocateAlpha( $image,$red,$green,$blue,$trans )
				or debugMode((__LINE__),$php_errormsg);
		}
		else // Get a regular color
		{
			// Damn, we cannot supress this function...
			$color_index = imageColorAllocate( $image,$red,$green,$blue );
		}

		return $color_index;
	}

	// Align text Function
	function textAlignment( $font,$size,$text,$where,$align = 'left' )
	{
		$txtsize = @imageTTFBBox( $size,0,$font,$text )
			or debugMode((__LINE__),$php_errormsg);		// Gets the points of the image coordinates

		switch ($align)
		{
			case 'right':
				$txt = $txtsize[2];
				break;

			case 'center':
				$txt = $txtsize[2]/2;
				break;

			default:
				$txt = 0;
				break;
		}
		$txtloc = $where-$txt;	// Sets the x coordinate where to print the server name
		return $txtloc;
	}

	// Shadow Text
	function shadowText( $image,$fontsize,$xpos,$ypos,$font,$text,$color )
	{
		$color = setColor( $image,$color );

		$_x = array(-1,-1,-1, 0, 0, 1, 1, 1 );
		$_y = array(-1, 0, 1,-1, 1,-1, 0, 1 );

		for( $n=0; $n<=7; $n++ )
		{
			@imageTTFText( $image,$fontsize,0,$xpos+$_x[$n],$ypos+$_y[$n],$color,$font,$text )
				or debugMode((__LINE__),$php_errormsg);
		}
	}

	// Function to convert strings to a compatable format
	// This function was copied from http://de3.php.net/manual/de/function.imagettftext.php
	// Under post made by limalopex.eisfux.de
	function utf8_to_nce( $utf = '' )
	{
		if($utf == '')
		{
			return($utf);
		}

		$max_count = 5;		// flag-bits in $max_mark ( 1111 1000 == 5 times 1)
		$max_mark = 248;	// marker for a (theoretical ;-)) 5-byte-char and mask for a 4-byte-char;

		$html = '';
		for($str_pos = 0; $str_pos < strlen($utf); $str_pos++)
		{
			$old_val = ord( $utf{$str_pos} );
			$new_val = 0;

			$utf8_marker = 0;

			// skip non-utf-8-chars
			if( $old_val > 127 )
			{
				$mark = $max_mark;
				for($byte_ctr = $max_count; $byte_ctr > 2; $byte_ctr--)
				{
					// actual byte is utf-8-marker?
					if( ( $old_val & $mark  ) == ( ($mark << 1) & 255 ) )
					{
						$utf8_marker = $byte_ctr - 1;
						break;
					}
					$mark = ($mark << 1) & 255;
				}
			}

			// marker found: collect following bytes
			if($utf8_marker > 1 and isset( $utf{$str_pos + 1} ) )
			{
				$str_off = 0;
				$new_val = $old_val & (127 >> $utf8_marker);
				for($byte_ctr = $utf8_marker; $byte_ctr > 1; $byte_ctr--)
				{
					// check if following chars are UTF8 additional data blocks
					// UTF8 and ord() > 127
					if( (ord($utf{$str_pos + 1}) & 192) == 128 )
					{
						$new_val = $new_val << 6;
						$str_off++;
						// no need for Addition, bitwise OR is sufficient
						// 63: more UTF8-bytes; 0011 1111
						$new_val = $new_val | ( ord( $utf{$str_pos + $str_off} ) & 63 );
					}
					// no UTF8, but ord() > 127
					// nevertheless convert first char to NCE
					else
					{
						$new_val = $old_val;
					}
				}
				// build NCE-Code
				$html .= '&#'.$new_val.';';
				// Skip additional UTF-8-Bytes
				$str_pos = $str_pos + $str_off;
			}
			else
			{
				$html .= chr($old_val);
				$new_val = $old_val;
			}
		}
		return($html);
	}

	// Write Text
	function writeText( $image,$fontsize,$xpos,$ypos,$color,$font,$text,$align,$shadow_color )
	{
		// Get the font
		$font = getFont( $font );
		// Get the color
		$color = setColor( $image,$color );
		// Convert text for display
		$text = utf8_to_nce($text);

		// Correct alignment
		if( $align != 'left' )
		{
			$xpos = textAlignment( $font,$fontsize,$text,$xpos,$align );
		}

		// Create the pseudo-shadow
		if( !empty($shadow_color) )
		{
			shadowText( $image,$fontsize,$xpos,$ypos,$font,$text,$shadow_color );
		}

		// Write the text
		@imageTTFText( $image,$fontsize,0,$xpos,$ypos,$color,$font,$text )
			or debugMode((__LINE__),$php_errormsg);
	}

	// Get font and font path
	function getFont( $font )
	{
		global $configData;

		$font_file = $configData['font_dir'].$font;

		// Check to see if SigGen can see the font
		if( file_exists($font_file) )
		{
			return $font_file;
		}
		else
		{
			debugMode( (__LINE__),'Cannot find font',$font_file,0 );
		}
	}

	// GIF image creator
	function makeImageGif( $image,$w,$h,$dither,$save_image = '' )
	{
		// Check dither mode
		if( $dither )
		{
			$dither = TRUE;
		}
		else
		{
			$dither = FALSE;
		}

		// Create a new true color image because we don't want to ruin the original
		$im_gif = @imagecreatetruecolor( $w,$h )
			or debugMode((__LINE__),$php_errormsg);

		// Copy the original image into the new one
		@imagecopy( $im_gif,$image,0,0,0,0,$w,$h )
			or debugMode((__LINE__),$php_errormsg);

		// Convert the new image to palette mode
		@imagetruecolortopalette( $im_gif,$dither,256 )
			or debugMode((__LINE__),$php_errormsg);

		// Check if this needs to be saved
		if( empty($save_image) )
		{
			@imageGif( $im_gif )
				or debugMode((__LINE__),$php_errormsg);
		}
		else
		{
			@imageGif( $im_gif,$save_image )
				or debugMode((__LINE__),$php_errormsg);
		}

		// Destroy palette image
		@imageDestroy( $im_gif )
			or debugMode((__LINE__),$php_errormsg);
	}

	// Funtion to merge images with the main image
	function combineImage( $image,$filename,$line,$x_loc,$y_loc )
	{
		$info = getimagesize($filename);

		switch( $info['mime'] )
		{
			case 'image/jpeg' :
				$im_temp = @imagecreatefromjpeg($filename)
					or debugMode( $line,$php_errormsg );
				break;

			case 'image/png' :
				$im_temp = @imagecreatefrompng($filename)
					or debugMode( $line,$php_errormsg );
				break;

			case 'image/gif' :
				$im_temp = @imagecreatefromgif($filename)
					or debugMode( $line,$php_errormsg );
				break;

			default:
				debugMode( $line,'Unhandled image type: '.$info['mime'] );
		}

		// Get the image dimentions
		$im_temp_width = imageSX( $im_temp );
		$im_temp_height = imageSY( $im_temp );

		// Copy created image into main image
		@imagecopy( $image,$im_temp,$x_loc,$y_loc,0,0,$im_temp_width,$im_temp_height )
			or debugMode( $line,$php_errormsg );

		// Destroy the temp image
		if( isset($im_temp) )
		{
			@imageDestroy( $im_temp )
				or debugMode( $line,$php_errormsg );
		}
	}

	function getEnglishValue( $keyword , $locale=null )
	{
		global $siggen_translate, $roster_conf;

		if( !is_null($locale) )
		{
			$locale = $roster_conf['roster_lang'];
		}

		if( array_key_exists($keyword,$siggen_translate[$locale]) )
		{
			return $siggen_translate[$locale][$keyword];
		}
		else
		{
			foreach( $roster_conf['multilanguages'] as $lang )
			{
				if( array_key_exists($keyword,$siggen_translate[$lang]) )
				{
					return $siggen_translate[$lang][$keyword];
				}
			}
		}
	}



#--[ IMAGE CREATION ]------------------------------------------------------

	// Choose the character image
	if( $configData['charlogo_disp'] )
	{
		// Check for custom/uploaded image
		$custom_user_img = $configData['image_dir'].$configData['user_dir'].$sig_name;

		// Set custom character image, based on name in DB
		if( file_exists($custom_user_img.'.png') )
		{
			$im_user_file = $custom_user_img.'.png';
		}
		elseif( file_exists($custom_user_img.'.gif') )
		{
			$im_user_file = $custom_user_img.'.gif';
		}
		elseif( file_exists($custom_user_img.'.jpg') )
		{
			$im_user_file = $custom_user_img.'.jpg';
		}
		elseif( file_exists($custom_user_img.'.jpeg') )
		{
			$im_user_file = $custom_user_img.'.jpeg';
		}
		// If custom image is not found, check for image pack settings
		elseif( file_exists($configData['image_dir'].$configData['char_dir'].'char.inc') )
		{
			include( $configData['image_dir'].$configData['char_dir'].'char.inc' );
		}
		else // Old legacy code if the char image pack doesnt have a char.inc file
		{
			$char_ext = '.png';
			if( !empty($sig_race) )
			{
				// Set race-gender based image
				if( !empty($sig_gender) )
				{
					// Set race-gender based image
					$im_user_file = $configData['image_dir'].$configData['char_dir'].$sig_race.'-'.$sig_gender.$char_ext;
				}
				// Set race only image
				else
				{
					$im_user_file = $configData['image_dir'].$configData['char_dir'].$sig_race.$char_ext;
				}

			}	// Set default character image
			else
			{
				$im_user_file = $configData['image_dir'].$configData['char_dir'].$configData['charlogo_default_image'];
			}
		}
	}


	// Choose the background
	// ====Background Filenames====
	switch ( $configData['backg_data_table'] )
	{
		case 'members':
			$backg['getdatafrom'] = $membersData[$configData['backg_data']];
			break;

		case 'players':
			$backg['getdatafrom'] = $playersData[$configData['backg_data']];
			break;

		default:
			$backg['getdatafrom'] = $playersData['race'];
			break;
	}

	// Populate the background selection
	for( $i=1; $i<=12; $i++ )
	{
		if( $configData['backg_translate'] )
		{
			$key = array_search($configData['backg_search_'.$i],$siggen_translate[$sig_char_locale]);
			$backg[$key] = $configData['backg_file_'.$i];
		}
		else
		{
			$backg[$configData['backg_search_'.$i]] = $configData['backg_file_'.$i];
		}
	}

	if( $configData['backg_disp'] )
	{
		// Set the default background image first
		$im_back_file = $configData['image_dir'].$configData['backg_dir'].$configData['backg_default_image'];

		// Check if the default background is forced
		if( !$configData['backg_force_default'] )
		{
			// Check for custom/uploaded image
			$custom_back_img = $configData['image_dir'].$configData['user_dir'].'bk-'.$sig_name;
			if( file_exists($custom_back_img.'.png') )
			{
				$im_back_file = $custom_back_img.'.png';
			}
			elseif( file_exists($custom_back_img.'.gif') )
			{
				$im_back_file = $custom_back_img.'.gif';
			}
			elseif( file_exists($custom_back_img.'.jpg') )
			{
				$im_back_file = $custom_back_img.'.jpg';
			}
			elseif( file_exists($custom_back_img.'.jpeg') )
			{
				$im_back_file = $custom_back_img.'.jpeg';
			}
			// Try setting background from config
			elseif( !empty($backg['getdatafrom']) && !empty($backg[$backg['getdatafrom']]) )
			{
				$selected_back_img = $configData['image_dir'].$configData['backg_dir'].$backg[$backg['getdatafrom']];
				if( file_exists($selected_back_img) )
				{
					$im_back_file = $selected_back_img;
				}
			}
		}
	}

	// Create a new, truecolor image
	$im = @imagecreatetruecolor( $configData['main_image_size_w'], $configData['main_image_size_h'] )
		or debugMode( (__LINE__),'Cannot Initialize new GD image stream','',0,'Make sure you have the latest version of GD2 installed' );

	// Color fill the background?
	if( $configData['backg_fill'] )
	{
		@imagefill($im,0,0,setColor( $im,$configData['backg_fill_color'] ) )
			or debugMode( (__LINE__),$php_errormsg );
	}

	// Merge the background into the main image, with this hande, dandy merging script

	// Generate background first
	if( $configData['backg_disp'] && file_exists($im_back_file) )
	{
		// Create a new temp image from file
		$info = getimagesize($im_back_file);

		switch( $info['mime'] )
		{
			case 'image/jpeg' :
			case 'image/jpg' :
				$src = @imagecreatefromjpeg($im_back_file)
					or debugMode( (__LINE__),$php_errormsg );
				break;

			case 'image/png' :
				$src = @imagecreatefrompng($im_back_file)
					or debugMode( (__LINE__),$php_errormsg );
				break;

			default:
				debugMode( (__LINE__),'Unhandled image type: '.$info['mime'] );
		}

		// Get the image dimentions
		$src_width = imageSX( $src );
		$src_height = imageSY( $src );

		imagealphablending($im, false);

		for ($i = 0; $i < $src_height; $i++) //this loop traverses each row in the image
		{
			for ($j = 0; $j < $src_width; $j++) //this loop traverses each pixel of each row
			{
				// get the color & alpha info of the current pixel
				$retrieved_color = imagecolorat($src, $j, $i);
					imagesetpixel($im, $j, $i, $retrieved_color);

				imagesetpixel($im, $j, $i, $retrieved_color);

			}
		}
		imagesavealpha($im, true);
		imagealphablending($im, true);
	}



	// Get the image layer order
	$layer_order = explode(':',$configData['image_order']);

	// Place images based on layer order
	foreach( $layer_order as $o )
	{
		// Place the character image
		if( $o == 'char' && $configData['charlogo_disp'] && file_exists($im_user_file) )
		{
			combineImage( $im,$im_user_file,(__LINE__),$configData['charlogo_loc_x'],$configData['charlogo_loc_y'] );
		}

		// Place the colored frames
		if( $o == 'frames' && !empty($configData['frames_image']) )
		{
			$im_frame_file = $configData['image_dir'].$configData['frame_dir'].$configData['frames_image'];
			if( file_exists($im_frame_file) )
			{
				combineImage( $im,$im_frame_file,(__LINE__),0,0 );
			}
		}

		// Place the outside border
		if( $o == 'border' && !empty($configData['outside_border_image']) )
		{
			$im_bdr_file = $configData['image_dir'].$configData['border_dir'].$configData['outside_border_image'];
			if( file_exists($im_bdr_file) )
			{
				combineImage( $im,$im_bdr_file,(__LINE__),0,0 );
			}
		}

		// Place HonorRank logo
		if( $o == 'pvp' && $configData['pvplogo_disp'] && !empty($sig_pvp_icon) )
		{
			$im_pvp_file = $configData['image_dir'].$configData['pvplogo_dir'].$sig_pvp_icon;
			if( file_exists($im_pvp_file) )
			{
				combineImage( $im,$im_pvp_file,(__LINE__),$configData['pvplogo_loc_x'],$configData['pvplogo_loc_y'] );
			}
		}

		// Place Class image
		if( $o == 'class' && $configData['class_img_disp'] && !empty($sig_class_img) )
		{
			$im_class_file = $configData['image_dir'].$configData['class_dir'].$sig_class_img;
			if( file_exists($im_class_file) )
			{
				combineImage( $im,$im_class_file,(__LINE__),$configData['class_img_loc_x'],$configData['class_img_loc_y'] );
			}
		}

		// Place the level bubble
		if( $o == 'lvl' && $configData['lvl_disp'] )
		{
			$im_lvl_file = $configData['image_dir'].$configData['level_dir'].$configData['lvl_image'];
			if( !empty($configData['lvl_image']) && file_exists($im_lvl_file) )
			{
				combineImage( $im,$im_lvl_file,(__LINE__),$configData['lvl_loc_x'],$configData['lvl_loc_y'] );
			}

			// Get the text locations based on the image location
			$lvl_text_loc_x = $configData['lvl_text_loc_x']+$configData['lvl_loc_x'];
			$lvl_text_loc_y = $configData['lvl_text_loc_y']+$configData['lvl_loc_y'];

			// Print the level as text
			writeText( $im,$configData['lvl_font_size'],$lvl_text_loc_x,$lvl_text_loc_y,$configData['lvl_font_color'],$configData['lvl_font_name'],$sig_level,'center',$configData['lvl_text_shadow'] );
		}
	}


#--[ EXP BAR PLACEMENT ]---------------------------------------------------

	if( $configData['expbar_disp'] && !empty($sig_exp) )
	{
		// Get the end locations for the eXp bar
		$x_end = $configData['expbar_loc_x']+$configData['expbar_length'];
		$y_end = $configData['expbar_loc_y']+$configData['expbar_height'];

		// Draw a full eXP bar
		if( $sig_level == $configData['expbar_max_level'] && $configData['expbar_max_disp'] && !$configData['expbar_max_hidden'] )
		{
			// Fix eXp bar text alignment
			if( $configData['expbar_align_max'] == 'center' )
			{
				$exp_text_loc = $x_end-($configData['expbar_length']/2);
			}
			elseif( $configData['expbar_align_max'] == 'right' )
			{
				$exp_text_loc = $x_end-3;
			}
			else
			{
				$exp_text_loc = $configData['expbar_loc_x']+3;
			}

			// The eXP bar (outside border)
			if( $configData['expbar_disp_bdr'] )
			{
				@imageRectangle( $im,$configData['expbar_loc_x']-1,$configData['expbar_loc_y']-1,$x_end+1,$y_end+1,setColor( $im,$configData['expbar_color_border'],$configData['expbar_trans_border'] ) )
					or debugMode( (__LINE__),$php_errormsg );
			}

			// The eXP bar (inside box)
			if( $configData['expbar_disp_inside'] )
			{
				@imageFilledRectangle( $im,$configData['expbar_loc_x'],$configData['expbar_loc_y'],$x_end,$y_end,setColor( $im,$configData['expbar_color_inside'],$configData['expbar_trans_inside'] ) )
					or debugMode( (__LINE__),$php_errormsg );
			}

			// The progress bar
			@imageFilledRectangle( $im,$configData['expbar_loc_x'],$configData['expbar_loc_y'],$x_end,$y_end,setColor( $im,$configData['expbar_color_maxbar'],$configData['expbar_trans_maxbar'] ) )
				or debugMode( (__LINE__),$php_errormsg );

			// eXpbar text
			if($configData['expbar_disp_text'])
			{
				writeText( $im,$configData['expbar_font_size'],$exp_text_loc,$y_end-1,$configData['expbar_font_color'],$configData['expbar_font_name'],$configData['expbar_max_string'],$configData['expbar_align_max'],$configData['expbar_text_shadow'] );
			}

		}// Draw the standard eXP bar
		elseif( $sig_level < $configData['expbar_max_level'] || !$configData['expbar_max_hidden'] )
		{
			// Variables to get and hold eXP bar data
			$outexp = printXP($sig_exp);
			$outperc = retPerc($sig_exp,$configData['expbar_loc_x'],$configData['expbar_length']);

			// Fix eXp bar text alignment
			if( $configData['expbar_align'] == 'center' )
			{
				$exp_text_loc = $x_end-($configData['expbar_length']/2);
			}
			elseif( $configData['expbar_align'] == 'right' )
			{
				$exp_text_loc = $x_end-3;
			}
			else
			{
				$exp_text_loc = $configData['expbar_loc_x']+3;
			}

			// The eXP bar (outside border)
			if( $configData['expbar_disp_bdr'] )
			{
				@imageRectangle( $im,$configData['expbar_loc_x']-1,$configData['expbar_loc_y']-1,$x_end+1,$y_end+1,setColor( $im,$configData['expbar_color_border'],$configData['expbar_trans_border'] ) )
					or debugMode( (__LINE__),$php_errormsg );
			}

			// The eXP bar (inside box)
			if( $configData['expbar_disp_inside'] )
			{
				@imageFilledRectangle( $im,$configData['expbar_loc_x'],$configData['expbar_loc_y'],$x_end,$y_end,setColor( $im,$configData['expbar_color_inside'],$configData['expbar_trans_inside'] ) )
					or debugMode( (__LINE__),$php_errormsg );
			}

			// The progress bar
			@imageFilledRectangle( $im,$configData['expbar_loc_x'],$configData['expbar_loc_y'],$outperc,$y_end,setColor( $im,$configData['expbar_color_bar'],$configData['expbar_trans_bar'] ) )
				or debugMode( (__LINE__),$php_errormsg );

			// eXpbar text
			if( $configData['expbar_disp_text'] )
			{
				writeText( $im,$configData['expbar_font_size'],$exp_text_loc,$y_end-1,$configData['expbar_font_color'],$configData['expbar_font_name'],$configData['expbar_string_before'].$outexp.$configData['expbar_string_after'],$configData['expbar_align'],$configData['expbar_text_shadow'] );
			}
		}
	}


#--[ PLACE DYNAMIC TEXT IN THE IMAGE ]-------------------------------------

	// Place the Character's Name
	if( $configData['text_name_disp'] && !empty($sig_name) )
	{
		writeText( $im,$configData['text_name_font_size'],$configData['text_name_loc_x'],$configData['text_name_loc_y'],$configData['text_name_font_color'],$configData['text_name_font_name'],$sig_name,$configData['text_name_align'],$configData['text_name_shadow'] );
	}

	// Place the Character's Honor Rank
	if( $configData['text_honor_disp'] && !empty($sig_pvp_rank) )
	{
		writeText( $im,$configData['text_honor_font_size'],$configData['text_honor_loc_x'],$configData['text_honor_loc_y'],$configData['text_honor_font_color'],$configData['text_honor_font_name'],$sig_pvp_rank,$configData['text_honor_align'],$configData['text_honor_shadow'] );
	}

	// Place the Character's Class
	if( $configData['text_class_disp'] && !empty($sig_class) )
	{
		writeText( $im,$configData['text_class_font_size'],$configData['text_class_loc_x'],$configData['text_class_loc_y'],$configData['text_class_font_color'],$configData['text_class_font_name'],$sig_class,$configData['text_class_align'],$configData['text_class_shadow'] );
	}

	// Place the Character's Guild Title Name
	if( $configData['text_guildtitle_disp'] && !empty($sig_guild_title) )
	{
		writeText( $im,$configData['text_guildtitle_font_size'],$configData['text_guildtitle_loc_x'],$configData['text_guildtitle_loc_y'],$configData['text_guildtitle_font_color'],$configData['text_guildtitle_font_name'],$sig_guild_title,$configData['text_guildtitle_align'],$configData['text_guildtitle_shadow'] );
	}

	// Place the Guild Name
	if( $configData['text_guildname_disp'] && !empty($sig_guild_name) )
	{
		writeText( $im,$configData['text_guildname_font_size'],$configData['text_guildname_loc_x'],$configData['text_guildname_loc_y'],$configData['text_guildname_font_color'],$configData['text_guildname_font_name'],$sig_guild_name,$configData['text_guildname_align'],$configData['text_guildname_shadow'] );
	}

	// Place the Server Name
	if( $configData['text_servername_disp'] && !empty($sig_server_name) )
	{
		writeText( $im,$configData['text_servername_font_size'],$configData['text_servername_loc_x'],$configData['text_servername_loc_y'],$configData['text_servername_font_color'],$configData['text_servername_font_name'],$sig_server_name,$configData['text_servername_align'],$configData['text_servername_shadow'] );
	}

	// Place the Site Name
	if( $configData['text_sitename_disp'] && !empty($sig_site_name) )
	{
		writeText( $im,$configData['text_sitename_font_size'],$configData['text_sitename_loc_x'],$configData['text_sitename_loc_y'],$configData['text_sitename_font_color'],$configData['text_sitename_font_name'],$sig_site_name,$configData['text_sitename_align'],$configData['text_sitename_shadow'] );
	}

	// Place Custom Text
	if( $configData['text_custom_disp'] && !empty($configData['text_custom_text']) )
	{
		writeText( $im,$configData['text_custom_font_size'],$configData['text_custom_loc_x'],$configData['text_custom_loc_y'],$configData['text_custom_font_color'],$configData['text_custom_font_name'],$configData['text_custom_text'],$configData['text_custom_align'],$configData['text_custom_shadow'] );
	}


#--[ PROFESSIONS AND SECONDARY SKILLS ]------------------------------------

	if( $skill_rows != 0 )
	{
		$pos['desc']  = $configData['skills_desc_loc_y'];
		$pos['level'] = $configData['skills_level_loc_y'];

		// Display Primary skills
		if( $configData['skills_disp_primary'] )
		{
			foreach( $skillsData as $skill )
			{
				// Print only professions where the max level does not equal 1
				if( $skill['type'] == $wordings[$sig_char_locale]['professions'] && $skill['max'] != '1' )
				{
					// Print Skill description
					if( $configData['skills_disp_desc'] )
					{
						$desc = $skill['name'];
						// Shorten long strings based on max length in config
						if( strlen($desc) > $configData['skills_desc_length'] )
						{
							$desc = trim( substr($desc,0,$configData['skills_desc_length']) ).'.';
						}
						writeText( $im,$configData['skills_font_size'],$configData['skills_desc_loc_x'],$pos['desc'],$configData['skills_font_color'],$configData['skills_font_name'],$desc,$configData['skills_align_desc'],$configData['skills_shadow'] );
					}

					// Print Skill level
					if( $configData['skills_disp_level'] )
					{
						// Print max level if turned on in config
						if( $configData['skills_disp_levelmax'] )
						{
							$level = $skill['level'].':'.$skill['max'];
						}
						else
						{
							$level = $skill['level'];
						}
						writeText( $im,$configData['skills_font_size'],$configData['skills_level_loc_x'],$pos['level'],$configData['skills_font_color'],$configData['skills_font_name'],$level,$configData['skills_align_level'],$configData['skills_shadow'] );
					}

					// Move the line position
					$pos['desc']  += $configData['skills_desc_linespace'];
					$pos['level'] += $configData['skills_level_linespace'];
				}
			}

			// Place a gap based on config
			$pos['desc']  += $configData['skills_gap'];
			$pos['level'] += $configData['skills_gap'];
		}

		// Display Secondary skills
		if( $configData['skills_disp_secondary'] )
		{
			foreach( $skillsData as $skill )
			{
				// Print only secondary skills where the max level does not equal 1
				if( $skill['type'] == $wordings[$sig_char_locale]['secondary'] && $skill['max'] != '1' && $skill['name'] != $wordings[$sig_char_locale]['riding'] )
				{
					// Print Skill description
					if( $configData['skills_disp_desc'] )
					{
						$desc = $skill['name'];
						// Shorten long strings based on max length in config
						if( strlen($desc) > $configData['skills_desc_length'] )
						{
							$desc = trim( substr($desc,0,$configData['skills_desc_length']) ).'.';
						}
						writeText( $im,$configData['skills_font_size'],$configData['skills_desc_loc_x'],$pos['desc'],$configData['skills_font_color'],$configData['skills_font_name'],$desc,$configData['skills_align_desc'],$configData['skills_shadow'] );
					}

					// Print Skill level
					if( $configData['skills_disp_level'] )
					{
						// Print max level if turned on in config
						if( $configData['skills_disp_levelmax'] )
						{
							$level = $skill['level'].':'.$skill['max'];
						}
						else
						{
							$level = $skill['level'];
						}
						writeText( $im,$configData['skills_font_size'],$configData['skills_level_loc_x'],$pos['level'],$configData['skills_font_color'],$configData['skills_font_name'],$level,$configData['skills_align_level'],$configData['skills_shadow'] );
					}

					// Move the line position
					$pos['desc']  += $configData['skills_desc_linespace'];
					$pos['level'] += $configData['skills_level_linespace'];
				}
			}

			// Place a gap based on config
			$pos['desc']  += $configData['skills_gap'];
			$pos['level'] += $configData['skills_gap'];
		}

		// Display Mount Info
		if( $configData['skills_disp_mount'] )
		{
			foreach( $skillsData as $skill )
			{
				// Print only secondary skills where the name equals riding
				if( $skill['type'] == $wordings[$sig_char_locale]['secondary'] && $skill['name'] == $wordings[$sig_char_locale]['riding'] )
				{
					$desc = $skill['name'];
					// Shorten long strings based on max length in config
					if( strlen($desc) > $configData['skills_desc_length_mount'] )
					{
						$desc = trim( substr($desc,0,$configData['skills_desc_length_mount']) ).'.';
					}
					writeText( $im,$configData['skills_font_size'],$configData['skills_desc_loc_x'],$pos['desc'],$configData['skills_font_color'],$configData['skills_font_name'],$desc,$configData['skills_align_desc'],$configData['skills_shadow'] );

					// Print Skill level
					if( $configData['skills_disp_level'] )
					{
						// Just Print level
						writeText( $im,$configData['skills_font_size'],$configData['skills_level_loc_x'],$pos['level'],$configData['skills_font_color'],$configData['skills_font_name'],$skill['level'],$configData['skills_align_level'],$configData['skills_shadow'] );
					}

					// Move the line position
					$pos['desc']  += $configData['skills_desc_linespace'];
					$pos['level'] += $configData['skills_level_linespace'];
				}
			}
		}
	}


#--[ FINALIZE AND CLOSE ]--------------------------------------------------

	// Set to output image by default
	$make_image = 1;


	// Save mode Web request over-ride
	if( isset($sig_saveonly) )
	{
		$configData['save_only_mode'] = $sig_saveonly;
		$configData['save_images'] = $sig_saveonly;
	}


	// Save the image to the server?
	if( $configData['save_images'] && $configData['default_message'] != $sig_name )
	{
		$save_dir = $configData['save_images_dir'];
		$saved_image = $save_dir.$configData['save_prefix'].$sig_name.$configData['save_suffix'].'.'.$configData['save_images_format'];

		if( file_exists($save_dir) )
		{
			if( is_writable($save_dir) )
			{
				switch ( $configData['save_images_format'] )
				{
					case 'gif':
						makeImageGif( $im,$configData['main_image_size_w'],$configData['main_image_size_h'],$configData['gif_dither'],$saved_image );
						break;

					case 'jpg':
						@imageJpeg( $im,$saved_image,$configData['image_quality'] )
							or debugMode( (__LINE__),$php_errormsg );
						break;

					case 'png':
						@imagePng( $im,$saved_image )
							or debugMode( (__LINE__),$php_errormsg );
						break;
				}

				if( $configData['save_only_mode'] )
				{
					echo "<!-- Image Saved: [$saved_image] -->\n";
					$make_image = 0;	// Don't output an image
				}
			}
			else
			{
				debugMode( (__LINE__),'Cannot save image to the server. "Saved Images Folder" was not writable',$save_dir,0,'Check SigGen Config settings. Also try manually setting write access' );
			}
		}
		else
		{
			debugMode( (__LINE__),'Saved Images Folder was not found',$save_dir,0,'Check SigGen Config settings' );
		}
	}


#--[ OUTPUT IMAGE ]--------------------------------------------------------

	if( $make_image )
	{
		// Set the header
		header( 'Content-type: image/'.$configData['image_type'] );


		switch ( $configData['image_type'] )
		{
			case 'gif':
				makeImageGif( $im,$configData['main_image_size_w'],$configData['main_image_size_h'],$configData['gif_dither'] );
				break;

			case 'jpg':
				@imageJpeg( $im,'',$configData['image_quality'] )
					or debugMode( (__LINE__),$php_errormsg );
				break;

			case 'png':
				@imagePng( $im )
					or debugMode( (__LINE__),$php_errormsg );
				break;
		}
	}


#--[ FREE MEMORY ]---------------------------------------------------------

	if( isset($im) ) @imageDestroy( $im );
?>
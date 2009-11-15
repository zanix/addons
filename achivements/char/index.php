<?php
/**
 * WoWRoster.net WoWRoster
 *
 * Displays Raid Progresion info
 *
 * LICENSE: Licensed under the Creative Commons
 *          "Attribution-NonCommercial-ShareAlike 2.5" license
 *

 * @license    http://creativecommons.org/licenses/by-nc-sa/2.5   Creative Commons "Attribution-NonCommercial-ShareAlike 2.5"
 * @version    SVN: $Id$
 * @link       http://ulminia.zenutech.com
 * @package    Raid Progresion
*/

      include( $addon['dir'] . 'inc/functions.php' );
      $achv = new achv;
      
      $data = $achv->getConfigDatamod($roster->data['member_id']);
      $data2 = $achv->getConfigDatamod2($roster->data['member_id']);

//echo '<pre>';
//print_r($data2);


            $roster->output['html_head'] = '
            <link href="'.$addon['url_path'] . 'css/achievements.css" rel="stylesheet" type="text/css" />';
      
            $roster->output['body_onload'] .= 'initARC(\'rp_menu\',\'rp_menu2\',\'radioOn\',\'radioOff\',\'checkboxOn\',\'checkboxOff\');';

            $first_tab = ' class="selected"';
                 // $menu .= '<li class="selected">';
            $imgext = $roster->config['img_suffix'];
            $e = '0';
            $xxx = '';
            $sx = '0';
            
            if (isset($_GET['cat']))
            {
                  $catee = $_GET['cat'];
            }
            else
            {
                  $catee = '00';
            }  
		$roster->tpl->assign_block_vars('menue',array(
                                    'ID' => '00',
                                    'LINK' => makelink('&amp;cat=00'),
                                    'NAME' => $roster->locale->act['Summary'],
                                    'SELECTED' => (isset($sx) && $sx == 1 ? true : false)
                                    )
                              );
                              
                              
            if ($catee == '00')
                  {
                        $roster->tpl->assign_block_vars('body2',array(
                                          'ID' => '00',
                                          'NAME' => $roster->locale->act['Summary'],
                                          'TOTAL' => $data2['Summary']['total'],
                                          'MEN' => '00',
                                          'IMG_PATH' => $addon['url_path'],
                                          'RECENT' => $roster->locale->act['Recent'],
                                          )
                                    );
                                    
                        //list($c,$t ) = explode(" / ", "18 / 49", 2);
                        //$width = ($c / $t)*100;
                        //general
                        $roster->tpl->assign_block_vars('body2.general',array(
                                          'NAME' => $roster->locale->act['General'],
                                          'WIDTH' => $achv->bar_width($data2['Summary']['general']),
                                          'STANDING' => $data2['Summary']['general'],
                                          )
                                    );
                        //quests
                        $roster->tpl->assign_block_vars('body2.quest',array(
                                          'NAME' => $roster->locale->act['Quests'],
                                          'WIDTH' => $achv->bar_width($data2['Summary']['quests']),
                                          'STANDING' => $data2['Summary']['quests'],
                                          )
                                    );
                        //Exploration
                        $roster->tpl->assign_block_vars('body2.exploration',array(
                                          'NAME' => $roster->locale->act['Exploration'],
                                          'WIDTH' => $achv->bar_width($data2['Summary']['exploration']),
                                          'STANDING' => $data2['Summary']['exploration'],
                                          )
                                    );
                        //Player vs. Player
                        $roster->tpl->assign_block_vars('body2.pvp',array(
                                          'NAME' => $roster->locale->act['Player vs. Player'],
                                          'WIDTH' => $achv->bar_width($data2['Summary']['pvp']),
                                          'STANDING' => $data2['Summary']['pvp'],
                                          )
                                    );
                        //Dungeons & Raids
                        $roster->tpl->assign_block_vars('body2.dn_raids',array(
                                          'NAME' => $roster->locale->act['Dungeons & Raids'],
                                          'WIDTH' => $achv->bar_width($data2['Summary']['dn_raids']),
                                          'STANDING' => $data2['Summary']['dn_raids'],
                                          )
                                    );
                        //Professions
                        $roster->tpl->assign_block_vars('body2.prof',array(
                                          'NAME' => $roster->locale->act['Professions'],
                                          'WIDTH' => $achv->bar_width($data2['Summary']['prof']),
                                          'STANDING' => $data2['Summary']['prof'],
                                          )
                                    );
                        //Reputation
                        $roster->tpl->assign_block_vars('body2.rep',array(
                                          'NAME' => $roster->locale->act['Reputation'],
                                          'WIDTH' => $achv->bar_width($data2['Summary']['rep']),
                                          'STANDING' => $data2['Summary']['rep'],
                                          )
                                    );
                        //World Events
                        $roster->tpl->assign_block_vars('body2.world_events',array(
                                          'NAME' => $roster->locale->act['World Events'],
                                          'WIDTH' => $achv->bar_width($data2['Summary']['world_events']),
                                          'STANDING' => $data2['Summary']['world_events'],
                                          )
                                    );
                        //Feats of Strength
                        $roster->tpl->assign_block_vars('body2.feats',array(
                                          'NAME' => $roster->locale->act['Feats of Strength'],
                                          'WIDTH' => $achv->bar_width($data2['Summary']['feats']),
                                          'STANDING' => $data2['Summary']['feats'],
                                          )
                                    );
                                    
                        // last 5 achivements compleated
                        for( $t=1; $t <= 5; $t++)
                        {
                        $roster->tpl->assign_block_vars('body2.lst5',array(
                                          'NAME' => $data2['Summary']['title_'.$t.''],
                                          'DESC' => $data2['Summary']['disc_'.$t.''],
                                          'POINTS' => $data2['Summary']['points_'.$t.''],
                                          'DATE' => $data2['Summary']['date_'.$t.''],
                                          )
                                    );
                         }
                  }
                     
            foreach($data as $catagory => $cid)
            {

                  foreach($cid as $achv => $achv_info)
                  {
                  
                        $sx++;
                        $roster->tpl->assign_block_vars('menue',array(
                                    'ID' => $catagory,
                                    'LINK' => makelink('&amp;cat='.$catagory),
                                    'NAME' => htmlspecialchars($roster->locale->act[$achv]),
                                    'SELECTED' => (isset($sx) && $sx == 0 ? true : false)
                                    )
                              );
                  $e++;
                  if ($catee == $catagory)
                  {
            
                        $roster->tpl->assign_block_vars('body',array(
                                          'ID' => $catagory,
                                          'NAME' => $achv,
                                          'MEN' => $e,
                                          )
                                    );
                        $sxx = '0';
                        
                        foreach ($achv_info as $achva => $dat)
                        {

                        $sxx++;
                        
                              $roster->tpl->assign_block_vars('body.menue2',array(
                                    'ID' => 's'.$dat['menue'],
                                    'NAME' => $achva,
                                    'SELECTED' => (isset($sxx) && $sxx == 1 ? true : false)
                                    )
                              );

                             $roster->tpl->assign_block_vars('body.info',array(
                                          'ID' => 's'.$dat['menue'],
                                          'NAME' => $achva
                                          )
                                    );
                                    
                              
                              
                              foreach ($dat['info'] as $a =>$b)
                              {
                                    $xxx++;
                                    
                                    if ($b['achv_complete'] == '1')
                                    {
                                          $bg = $addon['url'].'images/achievement_bg.jpg';
                                    }
                                    if ($b['achv_complete'] == '')
                                    {
                                          $bg = $addon['url'].'images/achievement_bg_locked.jpg';
                                    }

                                    $d = explode("<br>", $b['achv_criteria']);
                                    $ct = count($d);

                                   $u = '0';
                                   $achvg = '<td>';
                                   foreach($d as $g)
                                   {
                                          if (preg_match("/( Completed )/i", $g)) {
                                                //echo "A match was found.";
                                                $color = '#7eff00';
                                          }
                                          else 
                                          {
                                                //echo "A match was not found.";
                                                $color = '#4169E1';
                                          }
                                          $achvg .= '<span style="color:'.$color.';">'.$g.'</span><br>';
                                          $u++;
                                          if ($u == round(($ct/2)))
                                          {
                                                $achvg .= '</td><td>';
                                          }
                                          
                                   }
                                   $achvg .= '</td>';
                                    if ($b['achv_date'] != '000-00-00')
                                    {
                                          $dat = $b['achv_date'];
                                    }
                                    else
                                    {
                                          $dat = '';
                                    }
                                    //echo $b['achv_title'].'<br>';
                                    if (isset($b['achv_progress']))
                                    {
                                          $bar = true;
                                    }
                                    else
                                    {
                                          $bar = false;
                                    }
                                    
                                    $roster->tpl->assign_block_vars('body.info.achv',array(
                                          'ID' => 's'.$dat['menue'],
                                          'IDS' => $xxx,
                                          'BACKGROUND' => $bg,
                                          'NAME' => stripslashes($roster->locale->act[$b['achv_title']]),
                                          'DESC' => stripslashes($roster->locale->act[$b['achv_disc']]),
                                          'DATE' => $dat,
                                          'POINTS' => $b['achv_points'],
                                          'CRITERIA' => $achvg,
							'SHIELD' => $addon['url'].'images/',
							'BAR' => $bar,
                                          'ICON' => $roster->config['interface_url'].'Interface/Icons/'.strtolower($b['achv_icon']).'.'.$imgext,
                                          )
                                    );
                                    
                                    if (isset($b['achv_progress']))
                                    {
                                    //echo '<pre>';
                                    //print_r($b);
                                          
                                          $wdth = '';
                                          
                                          $roster->tpl->assign_block_vars('body.info.achv.bar',array(
                                                'WIDTHX' => $b['achv_progress_width'],
                                                'STANDINGX' => $b['achv_progress'],
                                                )
                                          );
                                    }                                    
                              }
                        }
                  }
                  }
            }

      
$roster->tpl->set_handle('body', $addon['basename'] . '/index.html');
$roster->tpl->display('body');

<?php
/*
Plugin Name: DoDo
Plugin URI: http://www.getdodo.com
Version: 1.7.0
Author: <a href="http://www.wiggler.gr">Stelios Petrakis</a>, <a href="http://www.newsfilter.gr">Christos Zigkolis</a>
Description: A personalized blog inteface with recommendation system. Please read readme.txt for installation in your theme.
*/

if(!class_exists("Dodo"))	{
	class Dodo	{
		//Admin Options
		var $adminOptionsName = "DodoAdminOptions";
		//User Options 
	 	var $adminUsersName = "DodoAdminUsersOptions";  
		
		//Constructor
		function Dodo()	{
			add_action('dodo/dodo.php',  $this->init());
			
			$action_array = array ('template_redirect');
			
			$this->Listen($action_array);
			
			if(!wp_next_scheduled('gen_matrix_hook_hour')){
				wp_schedule_event(0, 'hourly', 'gen_matrix_hook_hour' );
			}
			
			add_action('edit_form_advanced',array(&$this,'hotTags'));
			add_action('admin_menu', 'Dodo_ap');			
			add_action('wp_head', array(&$this, 'addHeaderCode'), 1);
		}
		
		//add custom css for widgets
		function addHeaderCode()	{
			echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') .'/wp-content/plugins/dodo/widget.css" />' . "\n";
			echo '<script src="' . get_bloginfo('wpurl') .'/wp-content/plugins/dodo/widget_js.js" type="text/javascript" language="javascript"></script>'."\n";
		}
		
		//Show Most interesting tags to blogger
		function hotTags()	{
			global $wpdb;
			
			$result = $wpdb->get_row("SELECT *,SUM(score) as amount FROM {$wpdb->prefix}dodo_log WHERE `category` != 'NULL' GROUP BY category ORDER BY amount DESC LIMIT 1");
			$favcat = $result->category;
			
			$result = $wpdb->get_results("SELECT *,SUM(score) as amount FROM {$wpdb->prefix}dodo_log WHERE `tag` != 'NULL' GROUP BY tag ORDER BY amount DESC LIMIT 5");
			$topfivetags = "";
			foreach($result as $tag)
				$topfivetags .= $tag->tag.", ";
				
			$result = $wpdb->get_row("SELECT date_time FROM {$wpdb->prefix}dodo_log ORDER BY date_time DESC LIMIT 1");
			$time = $result->date_time;
			
			$fivetags = substr($topfivetags,0,-2);
			
			if($fivetags == "")
				$fivetags = "None!";
			
			if($favcat == "")	
				$favcat = "(None yet)";
				
			echo "<div class=\"postbox opened\" id=\"hottags\">";
			echo "<h3><a class=\"togbox\">+</a> Story Suggestion</h3>";
			echo "<div class=\"inside\" style=\"font-size:110%\">";
			echo "<p>Hey!<br/>Your 5 hottest tags are : <strong>".$fivetags."</strong></p>";
			echo "<p>You may be interested in writing something for <strong>".$favcat."</strong> category</p>";
			echo "<p><small>Tracking since: ".$time." / <a href=\"http://www.getdodo.com\" title=\"Dodo\">Dodo Plugin</a></small></p>";
			echo "</div>";
			echo "</div>";
		}
		
		//Returns an array of admin options 
        function getAdminOptions()	{ 
			 $dodoAdminOptions = 	array(
									'post_track' => 'true',
									'search_track' => 'true',
									'personalized_page' => 'true',
									'module_order' => 'm_2,m_3,m_4,m_5,m_6'
									);

             $pOptions = get_option($this->adminOptionsName); 
				
             if (!empty($pOptions)) { 
                 foreach ($pOptions as $key => $option) 
                     $dodoAdminOptions[$key] = $option; 
             }             
             update_option($this->adminOptionsName, $dodoAdminOptions); 
             return $dodoAdminOptions; 
         }  

		//Returns an array of user options 
		function getUserOptions($user_email)	{ 
			
			if (empty($user_email))
				return '';

			$pOptions = get_option($this->adminUsersName); 
			
			if (!isset($pOptions)) 
				$pOptions = array(); 

			if (empty($pOptions[$user_email])) { 
			 $pOptions[$user_email] = 	array(
									'post_track' => 'true',
									'search_track' => 'true',
									'personalized_page' => 'true',
									'module_order' => 'm_2,m_3,m_4,m_5,m_6'
									);
			 update_option($this->adminUsersName, $pOptions); 
			} 
			
			return $pOptions; 
		}


		//Returns 1 if there is not enough data in order to render Personalized view
		function notEnoughData()	{
			global $user_ID,$wpdb;
			$result = $wpdb->get_results();
			
			$tags_num = sizeof($wpdb->get_results("SELECT * FROM {$wpdb->prefix}dodo_log WHERE tag != \"NULL\" AND user_id = '$user_ID'"));
			$cats_num = sizeof($wpdb->get_results("SELECT * FROM {$wpdb->prefix}dodo_log WHERE category != \"NULL\" AND user_id = '$user_ID'"));
			
			if($tags_num < 5 || $cats_num < 2)
				return 1;
			else
				return 0;
		}
		
		//Renders personalized tag cloud
		function getTagCloud($uOptions)	{
			global $user_ID,$wpdb,$post;
			
			if($uOptions['post_track'] == "false")
				return 0;
				
			$utags = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dodo_log WHERE tag != \"NULL\" AND user_id = '$user_ID' ORDER BY score DESC LIMIT 30");
			$taglist = "";
			$taglist = array();
			
			$i=0;
			
			foreach($utags as $utag)
			{
				$taglist[$i++] =$utag->tag;
				$namelist[$utag->tag] = $utag->score;
			}
			
 			asort($taglist);
			
			$max = $namelist[$taglist[0]];
			
			echo "<div class=\"widget_container\">";
			
			echo "<div class=\"widget_header\">Your TagCloud</div>";
			
			echo "<div class=\"widget_body\">";
			
			foreach($taglist as $a)
			{
				$tag =get_term_by('name', $a, 'post_tag');
				echo "<a rel=\"tag\" title=\"".$a."\" href=\"".get_tag_link($tag->term_id)."\"style=\"font-size:".(($namelist[$a]/$max)*2)."em\">".$a."</a> ";
			}
	
			echo "</div>";
			echo "</div>";			
		}	
		
		function getCommentPost($uOptions)	{
			global $user_ID,$wpdb;
			
			$get_posts_commented = $wpdb->get_results("SELECT comment_ID,comment_post_ID,MAX(comment_date) as date FROM {$wpdb->prefix}comments WHERE user_id = '$user_ID' GROUP BY comment_post_ID ");
			
			echo "<div class=\"widget_container\">";
			
			echo "<div class=\"widget_header\">Answers to your comments</div>";
			
			echo "<div class=\"widget_body\">";
						
			$i = 0;
			
			foreach($get_posts_commented as $post_comment)
			{
				$get_new_comment = $wpdb->get_row("SELECT comment_content,comment_ID,user_id,comment_author FROM {$wpdb->prefix}comments WHERE comment_post_ID = $post_comment->comment_post_ID ORDER BY comment_date DESC LIMIT 5");
				
				if($get_new_comment->comment_ID != $post_comment->comment_ID && $get_new_comment->user_id != $user_ID)
				{
					echo "<p><a href=\"".get_permalink($post_comment->comment_post_ID)."#comment-".$get_new_comment->comment_ID."\" title=\"".$get_new_comment->comment_author."\">".$get_new_comment->comment_author."</a> in <a href=\"".get_permalink($post_comment->comment_post_ID)."\" title=\"".get_the_title($post_comment->comment_post_ID)."\">".get_the_title($post_comment->comment_post_ID)."</a> : \"".substr($get_new_comment->comment_content,0,50)."...\"</p>";	
					$i++;
				}	
			}
			
			if($i==0)
				echo "<div class=\"widget_info\">No replies have been made to any of your comments yet. Patience...</div>";
			
			echo "</div>";
			echo "</div>";
			
			return 1;
		}
		
		//Renders Favorite category module
		function getFavPost($tag_or_cat,$uOptions)	{
			global $user_ID,$wpdb,$post;
			
			if($uOptions['post_track'] == "false")
				return 0;
			
			$term = "";
			
			if(strcmp($tag_or_cat,'tag') == 0)
				$term = "tag";
			elseif(strcmp($tag_or_cat,'cat') == 0)
				$term = "category";
			else
				return 0;
				
			$fav = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}dodo_log WHERE ".$term." != \"NULL\" AND user_id = '$user_ID' ORDER BY score DESC");
			
			echo "<div class=\"widget_container\">";
			
			echo "<div class=\"widget_header\">Favorite ".$term." <span class=\"widget_fav\">".(($term=="tag")?($fav->tag):($fav->category))."</span></div>";
			
			echo "<div class=\"widget_menu\">";

			if(strcmp($tag_or_cat,'cat') == 0)
				echo "<span class=\"widget_view\">Views</span> <a onClick=\"widget_show(0,0);\" href=\"javascript:void(0);\">Single Post</a> | <a onClick=\"widget_show(1,0);\" href=\"javascript:void(0);\">Post List</a>";
			else
				echo "<span class=\"widget_view\">Views</span> <a onClick=\"widget_show(0,1);\" href=\"javascript:void(0);\">Single Post</a> | <a onClick=\"widget_show(1,1);\" href=\"javascript:void(0);\">Post List</a>";
				
			echo "</div>";
			
			echo "<div class=\"widget_body\">";
			
			if(strcmp($tag_or_cat,'cat') == 0)
			{
				echo "<div id=\"widget_sp\">";
				$posts = get_posts('numberposts=1&category='.get_cat_ID($fav->category));
			}else
			{
				echo "<div id=\"widget_sp_t\">";
 				$tag =get_term_by('name', $fav->tag, 'post_tag');
				$posts = query_posts('tag='.$fav->tag.'');
			}
			
			$post = $posts[0];
				
			setup_postdata($post);?>
				<h4><a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a><br/><small><?php the_time('F jS, Y') ?></small></h4>
			<?php the_content('Read the rest of this entry &raquo;');

			echo "</div>";
	
			if(strcmp($tag_or_cat,'cat') == 0)
			{
				echo "<div id=\"widget_pl\" style=\"display:none;\">";				
				$postsl = get_posts('numberposts=5&category='.get_cat_ID($fav->category));
			}else{
 				echo "<div id=\"widget_pl_t\" style=\"display:none;\">";
				$postsl = query_posts('showposts=5&tag='.$fav->tag.'');
			}
			echo "<ul>";

			foreach($postsl as $post)
			{
				setup_postdata($post);?>
					<li><a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a></li>
				<?php 
			}
			echo "</ul>";		
			echo "</div>";
			
			echo "</div>";
			
			echo "<div class=\"widget_footer\">";
			
			if(strcmp($tag_or_cat,'cat') == 0)
				echo "<a href=\"".get_category_link(get_cat_ID($fav->category))."\">Get more posts from this category...</a>";
			else
				echo "<a href=\"".get_tag_link($tag->term_id)."\">Get more posts from this tag...</a>";
		
			echo "</div>";
			echo "</div>";
						
			return 1;
		}
		
		//Renders Last Posts
		function getLastPosts()	{
			global $post;
			
			echo "<div class=\"widget_container\">";
			
			echo "<div class=\"widget_header\">Latest Posts</div>";
			
			echo "<div class=\"widget_body\">";
			
			$posts = query_posts('showposts=5&tag='.$taglist.'');
			
			echo "<ul>";
			
			foreach($posts as $post)
			{
				setup_postdata($post);?>
					<li><a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>"><?php the_title(); ?></a></li>
				<?php 
			}
			
			echo "</ul>";
			echo "</div>";
			
			echo "<div class=\"widget_footer\">";
			
			echo "<a href=\"".get_option('home')."/?no_pers=1\">More...</a>";
		
			echo "</div>";
			echo "</div>";
		}
		
		//find similar users based on relation matrix
		function select_neighbours()	{
			global $wpdb,$user_ID;
				
			$relationships = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dodo_matrix WHERE ( user_id_1 = '".$user_ID."' OR user_id_2 = '".$user_ID."' ) ORDER BY relation DESC LIMIT 5");
				
			if(sizeof($relationships) == 0)
				return 0;
				
			$i=0;
		
			$neighbours = array();
				
			foreach($relationships as $relation)
			{
				if($relation->user_id_1 == $user_ID)
					$user = $relation->user_id_2;
				else
					$user = $relation->user_id_1;
					
				$neighbours[$i++] = $user;
			}
			
			return $neighbours;
		}
		
		//extract diff tags from a list of users for the logged in user
		function get_diff_tags($users)	{
			global $user_ID,$wpdb;
			
			if(sizeof($users) == 0 || $users == 0)
				return "";
			
			//get user's tag list
			$user_tag_list = $wpdb->get_results("SELECT tag FROM {$wpdb->prefix}dodo_log WHERE tag != \"NULL\" AND user_id = '".$user_ID."'");
			
			//construct a tag array for user's tags
			$u_tl = array();
			$i=0;
			
			foreach($user_tag_list as $user_tl_item)
				$u_tl[$i++] = $user_tl_item->tag;
			
			$users_ids = "";
			
			//construct a tag list for the users list
			foreach($users as $user)
			{
				$users_ids .= "'".$user."',";
			}	
			
			$users_ids = substr($users_ids,0,-1);
			
			//get neighbours' tag list
			$users_tag_list = $wpdb->get_results("SELECT DISTINCT tag FROM {$wpdb->prefix}dodo_log WHERE user_id IN (".$users_ids.") AND tag != \"NULL\" ORDER BY score DESC");

			//construct a tag array for neighbours' tags
			$us_tl = array();
			$i=0;
			
			foreach($users_tag_list as $users_tl_item)
				$us_tl[$i++] = $users_tl_item->tag;
			
			//find and return the different tags
			$diff_tags = array();
			$diff_tags = array_diff($us_tl,$u_tl);
			
			//make it a comma separated string with 5 first tags
			$diff_tl = "";
			$tag_counter = 0;
			
			foreach($diff_tags as $diff_tag)
			{
				$diff_tl .= $diff_tag.",";
				$tag_counter++;
			
				if($tag_counter >=5)
					break;
			}
			
			$diff_tl = substr($diff_tl,0,-1);
				
			return $diff_tl;
		} 
		
		//Suggestion Function
		function getSuggestions($uOptions)	{
			global $post;
				
			echo "<div class=\"widget_container\">";
			
			echo "<div class=\"widget_header\">Posts you may find interesting</div>";
			
			echo "<div class=\"widget_body\">";
			
			$taglist = $this->get_diff_tags($this->select_neighbours());

			if($taglist == "")
				echo "<div class=\"widget_info\">No recommendations found, displaying the latest posts.</div>";
				
			$posts = query_posts('showposts=5&tag='.$taglist.'');
			
			echo "<ul>";
			
			foreach($posts as $post)
			{
				setup_postdata($post);?>
					<li><a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a></li>
				<?php 
			}
			
			echo "</ul>";
			
			echo "</div>";
			echo "</div>";
		}
		
		//General Interface function, renders all the above
		function getInterface()	{
			
			global $user_email,$user_ID;

			if(!is_home())
				return 1;
				
			if(!$user_ID)
				return 1;
				
			$uOptions = Array();
			
			if(current_user_can('activate_plugins'))	
				$uOptions = $this->getAdminOptions();
			else	
			{
				$uOptions = $this->getUserOptions($user_email);
				$uOptions = $uOptions[$user_email];
			}
			
			if($uOptions['personalized_page'] == "false")
				return 1;
			
			if($_GET['no_pers'] == "1")
			{
				echo "<div class=\"widget_info\"><strong>Personalized View is <a href=\"".get_option('home')."\">OFF</a></strong></div>";
				return 1;
			}
			
			echo "<div class=\"widget_info\"><strong>Personalized View is <a href=\"".get_option('home')."/?no_pers=1\">ON</a></strong></div>";
		
			if($this->notEnoughData())
			{
				echo "<div class=\"widget_info\">Not enough data for personalization, visit this blog more often to get a personalized view!</div>";
				return 1;
			}
			
			$actmods = explode (",",$uOptions['module_order']);
		
			foreach($actmods as $actmod)
			{
				if($actmod != "" && $actmod != "undefined")
				{
					if(strcmp($actmod,"m_1") == 0)
						$this->getFavPost('tag',$uOptions);								
					if(strcmp($actmod,"m_2") == 0)
						$this->getFavPost('cat',$uOptions);
					if(strcmp($actmod,"m_3") == 0)
						$this->getTagCloud($uOptions);
					if(strcmp($actmod,"m_4") == 0)
						$this->getCommentPost($uOptions);
					if(strcmp($actmod,"m_5") == 0)
						$this->getSuggestions($uOptions);
					if(strcmp($actmod,"m_6") == 0)
						$this->getLastPosts();
				}
			}
	
			return 0;
		}
		
		//Prints out the admin page 
      	function printAdminPage() { 
			?>
			<div class="wrap"> 
			<h2>Dodo</h2>
			<?php
			$this->renderAdminMenu();

			if(!isset($_GET['sub']))
			{
				$this->printAdminUsersPage();
			}
			
			if($_GET['sub'] == "stats")
			{
				$this->getPop("tag");
				$this->getPop("category");
				$this->getPop("search_term");
			}
			
			if($_GET['sub'] == "live")
			{
				$this->getLiveStats($_GET['all']);
			}
			
			if($_GET['sub'] == "design")
			{
				$this->widgetsdesign();
			}
			?>
			</div> 
		<?php
		}
		
		//render widgets design section
		function widgetsdesign()	{
			echo "<div>";
			
			echo "<p>";
			
			echo "You can change the style of widgets by editing the css file <a href=\"".get_bloginfo('wpurl') ."/wp-content/plugins/dodo/widget.css\">here</a>.";
			
			echo "</p>";
			
			echo "</div>";
		}
		
		//render live statistics for blogger
		function getLiveStats($all)	{
			global $wpdb;
			
			$limit = "LIMIT 20";
			if($all == 1)
				$limit = "";
				
			$result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dodo_log ORDER BY date_time DESC ".$limit);
			
			echo "<div>";
			
			echo "<h3>Live Monitoring of user actions</h3>";
			
			$hex = 0;
			
			foreach($result as $action)
			{
				$result = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}users WHERE ID = ".$action->user_id);
				
				echo "<p style=\"color:rgb(".$hex.",".$hex.",".$hex.")\"><span style=\"font-weight:bold;font-style:italic;\">".$result->display_name."</span> ";

				if($action->tag != "")
					echo "visited tag: <strong>".$action->tag."</strong>";
				if($action->category != "")
					echo "visited category: <strong>".$action->category."</strong>";
				if($action->search_term != "")
					echo "searched for : <strong>".$action->search_term."</strong>";
					
				$time_suf = "th";
				if($action->freq == 1)
					$time_suf = "rst";
				elseif($action->freq == 2)
					$time_suf = "nd";
				elseif($action->freq == 3)
					$time_suf = "rd";
					
				echo " for ".$action->freq."".$time_suf." time ";
				
				echo "at ".$action->date_time."</p>";
				
				if($all == 1)
					$hex +=1;
				else
					$hex+=10;
			}
		
			echo "<a href=\"".$_SERVER["REQUEST_URI"]."&all=1\">Show all</a>";
		
			echo "</div>";
		}
		
		//renders popular categories / tags / searches for blogger
		function getPop($element)	{
			global $wpdb;
				
			$result = $wpdb->get_results("SELECT *,SUM(score) as amount FROM {$wpdb->prefix}dodo_log WHERE `".$element."` != 'NULL' GROUP BY ".$element." ORDER BY amount DESC LIMIT 5");
			
			echo "<div>";
			echo "<h3>Top ";
			
			if($element == "tag")
				echo "Tags";
			elseif($element == "category")
				echo "Categories";
			else
				echo "Searches";
				
			echo "</h3>";
			
			echo "<table style=\"width:50%;text-align:center;\" cellpadding=\"5\">";
			echo "<thead>";
			echo "<tr><th width=\"100\">";
			
			if($element == "tag")
				echo "Tag";
			if($element == "category")
				echo "Category";
			if($element == "search_term")
				echo "Search Term";
			
			echo "</th> <th width=\"100\">Score</th> </tr>";
			echo "</thead>";
			echo "<tbody>";

			foreach($result as $tag)
			{
				echo "<tr><td>";
				
				if($element == "tag")
					echo $tag->tag;
				if($element == "category")
					echo $tag->category;
				if($element == "search_term")
					echo $tag->search_term;
				
				echo "</td><td style=\"text-align:center;\">".$tag->amount."</td></tr>";
			}
			
			echo "</tbody>";
			echo "</table>";
			
			echo "</div>";
		}
		
		//Prints out the admin page 
		function printAdminUsersPage() { 
			
			global $user_email; 
    
        	if (empty($user_email))
                get_currentuserinfo(); 
 			
            //Save the updated options to the database 
            if (isset($_POST['update_dodoSettings']) && isset($_POST['tracktagscats']) &&  isset($_POST['tracksearches']) && isset($_POST['showpersonblog']) && isset($_POST['moduleorder']))
			{ 

				if (isset($user_email)) { 
					if(current_user_can('activate_plugins'))
						$pOptions = array(
						'post_track' => $_POST['tracktagscats'],
						'search_track' => $_POST['tracksearches'],
						'personalized_page' => $_POST['showpersonblog'],
						'module_order' =>  $_POST['moduleorder']
						);
					else			
						$pOptions[$user_email] = array(
						'post_track' => $_POST['tracktagscats'],
						'search_track' => $_POST['tracksearches'],
						'personalized_page' => $_POST['showpersonblog'],
						'module_order' =>  $_POST['moduleorder']
						);

					echo "<div class=\"updated\"><p><strong>Settings successfully updated.</strong></p></div>"; 

					if(current_user_can('activate_plugins'))
						update_option($this->adminOptionsName, $pOptions);
					else
						update_option($this->adminUsersName, $pOptions); 
				} 
            } 

 			if(current_user_can('activate_plugins'))
				$pOptions = $this->getAdminOptions();
			else
            	$pOptions = $this->getUserOptions($user_email);

           	//Get the author options 
			if(!current_user_can('activate_plugins'))
				$pOptions = $pOptions[$user_email]; 
			
			$tracktagscats = $pOptions['post_track']; 
			$tracksearches = $pOptions['search_track'];
			$showpersonblog = $pOptions['personalized_page'];
			$moduleorder = $pOptions['module_order'];
			
		//	echo $moduleorder;
			?>
			<script type="text/javascript" src="<?php echo bloginfo('url');?>/wp-content/plugins/dodo/jquery.js"></script>
			<script type="text/javascript" src="<?php echo bloginfo('url');?>/wp-content/plugins/dodo/jquery-ui.js"></script>
			<script type="text/javascript" src="<?php echo bloginfo('url');?>/wp-content/plugins/dodo/lists.js"></script>
			<link rel="stylesheet" href="<?php echo bloginfo('url');?>/wp-content/plugins/dodo/lists.css" type="text/css"/>
			<div class=wrap> 
				<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
					<h3>Allow Tag and Category Tracking?</h3> 
					<p>Selecting "No" will disable personalized tag cloud and favorite category modules.</p> 
					<p>
						<label for="tracktagscats_yes"><input type="radio" id="tracktagscats_yes" name="tracktagscats" value="true" <?php if ($tracktagscats == "true") { _e('checked="checked"', "DodoPlugin"); }?> /> Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="tracktagscats_no"><input type="radio" id="tracktagscats_no" name="tracktagscats" value="false" <?php if ($tracktagscats == "false") { _e('checked="checked"', "DodoPlugin"); }?>/> No</label>
					</p>
					<h3>Allow Search Tracking?</h3>
					<p>Selecting "No" will disable further personalization of blog homepage.</p>
					<p>
						<label for="tracksearches_yes"><input type="radio" id="tracksearches_yes" name="tracksearches" value="true" <?php if ($tracksearches == "true") { _e('checked="checked"', "DodoPlugin"); }?> /> Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="tracksearches_no"><input type="radio" id="tracksearches_no" name="tracksearches" value="false" <?php if ($tracksearches == "false") { _e('checked="checked"', "DodoPlugin"); }?>/> No</label>
					</p>
					<h3>Show Personalized Page?</h3>
					<p>Selecting "No" will disable personalized view of blog homepage for your account.</p>
					<p>
						<label for="showpersonblog_yes"><input type="radio" id="showpersonblog_yes" name="showpersonblog" value="true" <?php if ($showpersonblog == "true") { _e('checked="checked"', "DodoPlugin"); }?> /> Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="showpersonblog_no"><input type="radio" id="showpersonblog_no" name="showpersonblog" value="false" <?php if ($showpersonblog == "false") { _e('checked="checked"', "DodoPlugin"); }?>/> No</label>
					</p>
					<h3>Modules Order</h3>
					<p>Drag and drop your modules to select which of them will be placed in your personalized homepage.</p>
					<?php 
						$legacy = 0;
						
						if(strpos($moduleorder,"-") !== FALSE)
							$legacy = 1;
							
						if($legacy == 1){
							$moduleorder = "";
							echo "<div style=\"width:500px;background:#FFFFDF;padding:5px;margin:10px;color:green;border:2px solid green;\">Your module list has to be updated, please drag & drop your modules here</div>";
						}
						
						$actmods = explode(",",$moduleorder);
					?>					
					<ul class="sortable" id="phonetic">
						<li class="lhead">Available Modules</li>
						<?php
							if(strpos($moduleorder,"m_1") === FALSE)
								echo "<li id=\"m_1\" class=\"block\">Best Tag</li>";
							if(strpos($moduleorder,"m_2") === FALSE)
								echo "<li id=\"m_2\" class=\"block\">Best Category</li>";
							if(strpos($moduleorder,"m_3") === FALSE)
								echo "<li id=\"m_3\" class=\"block\">Tag Cloud</li>";
							if(strpos($moduleorder,"m_4") === FALSE)
								echo "<li id=\"m_4\" class=\"block\">Comments</li>";
							if(strpos($moduleorder,"m_5") === FALSE)
								echo "<li id=\"m_5\" class=\"block\">Recommendation</li>";
							if(strpos($moduleorder,"m_6") === FALSE)
								echo "<li id=\"m_6\" class=\"block\">Latest Posts</li>";						
						?>
					</ul>
					<ul class="sortable drop" id="numeric">
						<li class="lhead">Active Modules</li>
						<?php
						
						foreach($actmods as $actmod)
						{
							if($actmod != "" && $actmod != "undefined")
							{
								echo "<li>";
								echo "<span class=\"left\">";
								$acttext = "";
								
								if(strcmp($actmod,"m_1") == 0)
									$acttext = "Best Tag";								
								if(strcmp($actmod,"m_2") == 0)
									$acttext = "Best Category";
								if(strcmp($actmod,"m_3") == 0)
									$acttext = "Tag Cloud";
								if(strcmp($actmod,"m_4") == 0)
									$acttext = "Comments";
								if(strcmp($actmod,"m_5") == 0)
									$acttext = "Recommendation";
								if(strcmp($actmod,"m_6") == 0)
									$acttext = "Latest Posts";
								
								echo $acttext."</span>&nbsp;";
								echo "<a href=\"javascript:void(0);\" onclick=\"removeWidget(this,'".$actmod."','".$acttext."');\" class=\"delete\">&nbsp;</a>";
								echo "</li>";
							}
						}						
						?>
					</ul>
					<input type="hidden" name="moduleorder" id="widget_list" value="<?php echo $moduleorder; ?>"/>
					<p style="clear:both;" class="submit"> 
						<input type="submit" name="update_dodoSettings" class="button-primary" value="<?php _e('Update Settings', ';;;;;;') ?>" />
					</p>
				</form> 
			</div> 
		<?php
		}
		
		//renders admin menu
		function renderAdminMenu()	{
			$sub = isset ($_GET['sub']) ? $_GET['sub'] : '';
		  	$url = explode ('&', $_SERVER['REQUEST_URI']);
		  	$url = $url[0];
			
	 		if (!defined ('ABSPATH')) die (); 
			?>
			<div class="filter">
			<ul class="subsubsub">
			 	<li><a <?php if (!isset($_GET['sub'])) echo 'class="current"'; ?>href="<?php echo $url ?>"><?php _e ('General Configuration','DodoPlugin') ?></a></li>
				<li>|</li>
			  	<li><a <?php if ($_GET['sub'] == 'stats') echo 'class="current"'; ?>href="<?php echo $url ?>&amp;sub=stats"><?php _e ('Statistics','DodoPlugin') ?></a></li>
				<li>|</li>
			  	<li><a <?php if ($_GET['sub'] == 'live') echo 'class="current"'; ?>href="<?php echo $url ?>&amp;sub=live"><?php _e ('Live Update','DodoPlugin') ?></a></li>
				<li>|</li>
			  	<li><a <?php if ($_GET['sub'] == 'design') echo 'class="current"'; ?>href="<?php echo $url ?>&amp;sub=design"><?php _e ('Widgets Design','DodoPlugin') ?></a></li>
			</ul>
			</div>
			<div class="clear"></div>
		<?php	
		}
		
		//initializes admin interface for blogger and users and creates DB table
		function init()	{			
			$this->getAdminOptions();
			$this->getUserOptions('');
			$this->BuildTable();
		}
		
		//creates DB table
		function BuildTable()	{
			global $wpdb;
			
			$wpdb->query ("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}dodo_log` (
			  	`id` int(11) NOT NULL auto_increment,
			  	`user_id` int(11) NOT NULL,
  			  	`date_time` datetime NOT NULL,
			  	`freq` int(11) default NULL,
			  	`tag` varchar(100) default NULL,
			  	`category` varchar(100) default NULL,
		  		`search_term` varchar(200) default NULL,
				`score` int(11) default 0,
				PRIMARY KEY  (`id`)
			)");

			$wpdb->query ("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}dodo_matrix` (
			  	`id` int(11) NOT NULL auto_increment,
			  	`user_id_1` int(11) NOT NULL,
			  	`user_id_2` int(11) NOT NULL,
			  	`relation` double default 0,
				PRIMARY KEY  (`id`)
			)");
		}
		
		//Listener module for user actions
		function Listen($actions)	{
			foreach($actions as $action)
				add_action($action,array(&$this,$action),'10','1');
		}
		
		//calculate score based on frequency and date
		function score_function($old_date,$freq)	{
			$a = 1;
			$b = 2 * $a;
			$score = 0;
			
			$now = time();
				
			$old = strtotime($old_date);
			
			$date_diff = $now - $old;
			
			//difference in full days
			$date_score = floor($date_diff / 86400);
					
			$score = $a * $freq - $b * $date_score;
			
			return $score;
		}
		
		//Tag / Category / Search listener
		function template_redirect ()	{
			global $post, $posts,$wpdb,$user_ID,$user_email;

			if($user_email == '')
				return;
				
			$pOptions = $this->getUserOptions($user_email); 
			
			$pOptions = $pOptions[$user_email];

	        if (sizeof($pOptions)>= 4) { 
	            $tracktagscats = $pOptions['post_track'];
				$tracksearches = $pOptions['search_track'];
			}

			$cOptions = $this->getAdminOptions();
			
			// Don't log 404's
			if (!is_404 () && is_single())
			{
				if (isset ($_GET['preview']) && $_GET['preview'] == 'true')
					return;
				
				if(($tracktagscats == "true" && !current_user_can('activate_plugins')) || (current_user_can('activate_plugins') && $cOptions['post_track'] == "true"))
				{
					//get and save post tags
					$tags = wp_get_post_tags($post->ID);	

					foreach($tags as $tag)
					{
						$tag = "'".wpdb::escape ($tag->name)."'";

						$result = $wpdb->get_row ("SELECT * FROM {$wpdb->prefix}dodo_log WHERE tag = $tag AND user_id = '$user_ID'");

						if($result)
							$wpdb->query ("UPDATE {$wpdb->prefix}dodo_log SET freq = '".($result->freq + 1)."', date_time = NOW(), score = '".$this->score_function($result->date_time,($result->freq+1))."' WHERE tag = $tag AND user_id = '$user_ID'");
						else
							$wpdb->query ("INSERT INTO {$wpdb->prefix}dodo_log (user_id,date_time,tag,freq,score) VALUES ('$user_ID',NOW(),$tag,1,1)");				
					}

					//get and save post categories
					$categories = get_object_term_cache($post->ID, 'category');

					foreach($categories as $category)
					{
						$category = "'".wpdb::escape ($category->name)."'";

						$result = $wpdb->get_row ("SELECT * FROM {$wpdb->prefix}dodo_log WHERE category = $category AND user_id = '$user_ID'");

						if($result)
							$wpdb->query ("UPDATE {$wpdb->prefix}dodo_log SET freq = '".($result->freq + 1)."', date_time = NOW(), score = '".$this->score_function($result->date_time,($result->freq+1))."' WHERE category = $category AND user_id = '$user_ID'");
						else
							$wpdb->query ("INSERT INTO {$wpdb->prefix}dodo_log (user_id,date_time,category,freq,score) VALUES ('$user_ID',NOW(),$category,1,1)");								
					}
				}	
				return;
			}
			
			if((is_search() && $tracksearches == "true" && !current_user_can('activate_plugins')) || (current_user_can('activate_plugins') && is_search() && $cOptions['search_track'] == "true"))
			{
				$s = "'".attribute_escape(get_search_query())."'";
				
				$result = $wpdb->get_row ("SELECT * FROM {$wpdb->prefix}dodo_log WHERE search_term = $s");

				if($result)
					$wpdb->query ("UPDATE {$wpdb->prefix}dodo_log SET freq = '".($result->freq + 1)."', date_time = NOW() WHERE search_term = $s AND user_id = '$user_ID'");
				else
					$wpdb->query ("INSERT INTO {$wpdb->prefix}dodo_log (user_id,date_time,search_term,freq) VALUES ('$user_ID',NOW(),$s,1)");
				}
		}
	}
}//End Class Dodo

//build matrix
add_action('gen_matrix_hook_hour','dodo_gen_matrix');
function dodo_gen_matrix()
{
	global $wpdb;
	
	$users = $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}users");
	
	for($i=0;$i<sizeof($users);$i++)
		for($j=$i+1;$j<sizeof($users);$j++)
		{
			$user_1_id = $users[$i]->ID;
			$user_2_id = $users[$j]->ID;
			
			//tag part
			$tags_1 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dodo_log WHERE user_id = '".$user_1_id."' AND tag != \"NULL\" ORDER BY score DESC LIMIT 5");
			$tags_2 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dodo_log WHERE user_id = '".$user_2_id."' AND tag != \"NULL\" ORDER BY score DESC LIMIT 5");
			
			$comm_tags = 0;
			$tag_score = 0;
			
			for($a=0;$a<sizeof($tags_1);$a++)
				for($e=0;$e<sizeof($tags_2);$e++)
					if(strcmp($tags_1[$a]->tag,$tags_2[$e]->tag)==0)
					{
						$tag_diff = abs($tags_1[$a]->score - $tags_2[$e]->score);
						
						if($tag_diff == 0)
							$tag_diff = 1;
							
						$tag_score += 1/$tag_diff;
						$comm_tags++;
					}
			
			if($comm_tags == 0)
				$tags_score = 0;
			else
				$tags_score = $tag_score/$comm_tags;
			
			//cat part
			$cats_1 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dodo_log WHERE user_id = '".$user_1_id."' AND category != \"NULL\" ORDER BY score DESC LIMIT 5");
			$cats_2 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dodo_log WHERE user_id = '".$user_2_id."' AND category != \"NULL\" ORDER BY score DESC LIMIT 5");

			$comm_cats = 0;
			$cat_score = 0;

			for($a=0;$a<sizeof($cats_1);$a++)
				for($e=0;$e<sizeof($cats_2);$e++)
					if(strcmp($cats_1[$a]->category,$cats_2[$e]->category)==0)
					{
						$cat_diff = abs($cats_1[$a]->score - $cats_2[$e]->score);

						if($cat_diff == 0)
							$cat_diff = 1;

						$cat_score += 1/$cat_diff;
						$comm_cats++;
					}

			if($comm_cats == 0)
				$cats_score = 0;
			else
				$cats_score = $cat_score/$comm_cats;

			//search part
			$sear_1 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dodo_log WHERE user_id = '".$user_1_id."' AND search_term != \"NULL\" ORDER BY score DESC LIMIT 5");
			$sear_2 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dodo_log WHERE user_id = '".$user_2_id."' AND search_term != \"NULL\" ORDER BY score DESC LIMIT 5");

			$comm_sears = 0;
			$sear_score = 0;

			for($a=0;$a<sizeof($sear_1);$a++)
				for($e=0;$e<sizeof($sear_2);$e++)
					if(strcmp($sear_1[$a]->search_term,$sear_2[$e]->search_term)==0)
					{
						$sear_diff = abs($sear_1[$a]->score - $sear_2[$e]->score);

						if($sear_diff == 0)
							$sear_diff = 1;

						$sear_score += 1/$sear_diff;
						$comm_sears++;
					}

			if($comm_sears == 0)
				$sears_score = 0;
			else
				$sears_score = $sear_score/$comm_sears;
			
			$score = 0;
			
			if($sears_score == 0)
				$score = (0.5*$tags_score + 0.5*$cats_score);
			else
				$score = (0.5*$sears_score + 0.25*$tags_score + 0.25*$cats_score);
				
			$result = $wpdb->get_row ("SELECT * FROM {$wpdb->prefix}dodo_matrix WHERE user_id_1 = '$user_1_id' AND user_id_2 = '$user_2_id'");

			if($result)
				$wpdb->query ("UPDATE {$wpdb->prefix}dodo_matrix SET relation = '".$score."' WHERE user_id_1 = '$user_1_id' AND user_id_2 = '$user_2_id'");
			else
				$wpdb->query ("INSERT INTO {$wpdb->prefix}dodo_matrix (user_id_1,user_id_2,relation) VALUES ('$user_1_id','$user_2_id',$score)");
		}
}

if(class_exists("Dodo"))	{
	$dodo = new Dodo();
}

//Initialize the admin panel 
if (!function_exists("Dodo_ap")) { 
    function Dodo_ap() { 
		global $dodo; 

		if (!isset($dodo))
        	return; 
		
		if (function_exists('add_options_page')){
			$admin_p_page = add_options_page('Dodo', 'Dodo',9, basename(__FILE__), array(&$dodo, 'printAdminPage')); 
		}
		
		if (function_exists('add_submenu_page') && !current_user_can('activate_plugins')){
			$user_p_page=add_submenu_page('users.php', "Dodo","Dodo", 'read', basename(__FILE__), array(&$dodo, 'printAdminUsersPage'));
		}
	}    
}
?>
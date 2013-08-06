<?php
class PivotalTrackerPlugin extends MantisPlugin {
    function register() {
        $this->name = 'PivotalTracker';    # Proper name of plugin
        $this->description = 'Pivotal Tracker integration';    # Short description of the plugin
        $this->page = 'config_page';           # Default plugin page

        $this->version = '0.1';     # Plugin version string
        $this->requires = array(    # Plugin dependencies, array of basename => version pairs
            'MantisCore' => '1.2.0',  #   Should always depend on an appropriate version of MantisBT
            );

        $this->author = '';         # Author/team name
        $this->contact = '';        # Author/team e-mail address
        $this->url = '';            # Support webpage
    }

#    function events() {
#    }

    function hooks() {
        return array(
            'EVENT_REPORT_BUG' => 'new_bug',
			'EVENT_UPDATE_BUG' => 'update_bug'
        );
    }

    function config() {
        return array(
        	'pt_token' => '0',
		'userid' => '',
		'projects_and_integration' => '',
        );
    }

    function new_bug( $pEvent, $p_bug_data, $p_bug_id ) {
		error_log ("In new bug");
        echo "In method new_bug(). {$p_bug_data->summary} ({$p_bug_data->id})";
	
		# Check if the bug is in one of the projects we are integrating with PT. If it is get the data to interact with PT
		# And send to PT

		$config_proj_integration_data = explode (",", plugin_config_get('projects_and_integration'));
		$n_exploded = count ($config_proj_integration_data);
		if ($n_exploded % 3 != 0){
			echo "Error, the configuration string for PT integration should have a length multiple of 3";
			return;
		}	
	
		for ($i = 0; $i < $n_exploded / 3; $i++){
			# find integration id (if project is set up)
			$config_proj_id_mantis = project_get_id_by_name($config_proj_integration_data[$i*3+0]);
			$config_proj_id_pt = $config_proj_integration_data[$i*3+1];
			$config_proj_integration_id_pt = $config_proj_integration_data[$i*3+2];

			# Check if project is linked
			if ($config_proj_id_mantis == $p_bug_data->project_id){
				$p_userid = plugin_config_get('userid');
				$p_pt_token = plugin_config_get('pt_token');

				# Add story to PT
				$ch = curl_init("https://www.pivotaltracker.com/services/v3/projects/$config_proj_id_pt/stories");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, "<story><story_type>bug</story_type><name>[bug #$p_bug_id] " .
					"{$p_bug_data->summary}</name><description>{$p_bug_data->description}</description>" . 
					(strlen ($p_userid) == 0 ? "" : "<requested_by>$p_userid</requested_by>") . 
					"<external_id>$p_bug_id</external_id><integration_id>$config_proj_integration_id_pt</integration_id></story>");
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/xml", "X-TrackerToken: " . $p_pt_token)); 
				curl_setopt($ch, CURLOPT_HEADER, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

				$res = curl_exec($ch);
				curl_close($ch);
			}
		}
		#if it was not found here, we just ignored it, which is what we should do, so everything is good
	}

	function update_bug ($pEvent, $p_bug_data, $p_bug_id) {
		error_log ("In update bug");
        echo "In method update_bug(). {$p_bug_data->summary} ({$p_bug_data->id})";
		# Check if the bug is in one of the projects we are integrating with PT. If it is get the data to interact with PT
		# Find the story with the bug on PT, and update its status

		$config_proj_integration_data = explode (",", plugin_config_get('projects_and_integration'));
		$n_exploded = count ($config_proj_integration_data);
		if ($n_exploded % 3 != 0){
			echo "Error, the configuration string for PT integration should have a length multiple of 3";
			return $p_bug_data;
		}	
	
		for ($i = 0; $i < $n_exploded / 3; $i++){
			# find integration id (if project is set up)
			$config_proj_id_mantis = project_get_id_by_name($config_proj_integration_data[$i*3+0]);
			$config_proj_id_pt = $config_proj_integration_data[$i*3+1];
			$config_proj_integration_id_pt = $config_proj_integration_data[$i*3+2];
			
			# Check if project is linked
			if ($config_proj_id_mantis == $p_bug_data->project_id){
				$p_userid = plugin_config_get('userid');
				$p_pt_token = plugin_config_get('pt_token');
				
				# Find story id to update on PT (search for external_id that matches p_bug_id)
				$ch = curl_init("https://www.pivotaltracker.com/services/v3/projects/$config_proj_id_pt/stories?filter=external_id:$p_bug_id");
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/xml", "X-TrackerToken: " . $p_pt_token)); 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

				$res = curl_exec($ch);
				curl_close($ch);
				
				$p = xml_parser_create();
				$rc = xml_parse_into_struct ($p, $res, $vals, $index);
				if ($rc == 0){
					error_log ("[PT plugin]: Error parsing xml from PT server");
					return $p_bug_data;
				}
				xml_parser_free($p);
			
				$PT_story_id = 0;
				foreach ($vals as $elem){
					# "ID complete 3" is how the xml entry for the story ID looks like when xml_parse_into_struct works
					if ("IDcomplete3" == $elem["tag"] . $elem["type"] . $elem["level"]){ 
						$PT_story_id = $elem["value"];
						error_log ("Story id found: " . $PT_story_id);
					}
				}


				# update bug if it was found in the system
				# possible new states on PT are [finished, delivered and accepted, and maybe rejected? (not sure if we will use this one)]
				# possible states in Mantis are (According to constant_inc.php): 
				# define( 'NEW_', 10 );
				# define( 'FEEDBACK', 20 );
				# define( 'ACKNOWLEDGED', 30 );
				# define( 'CONFIRMED', 40 );
				# define( 'ASSIGNED', 50 );
				# define( 'RESOLVED', 80 );
				# define( 'CLOSED', 90 );

				if ($PT_story_id != 0){
					# decide the new status
					error_log ("BUG STATUS:  {$p_bug_data->status}");
					switch ($p_bug_data->status){
						case 90: $PT_new_status = "accepted"; break;
						case 80: $PT_new_status = "delivered"; break;
						case 10: $PT_new_status = "unstarted"; break;
						default: $PT_new_status = ""; break;
					}
					$ch = curl_init("https://www.pivotaltracker.com/services/v3/projects/$config_proj_id_pt/stories/$PT_story_id");
					curl_setopt($ch, CURLOPT_POST, 0);
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); 
					curl_setopt($ch, CURLOPT_POSTFIELDS, "<story><name>[bug #$p_bug_id] " .
						"{$p_bug_data->summary}</name><description>{$p_bug_data->description}-updated</description>" . 
						(strlen ($p_userid) == 0 ? "" : "<requested_by>$p_userid</requested_by>") . 
						($PT_new_status !== '' ? "<current_state>$PT_new_status</current_state>" : "") . 
						 "</story>");
					curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/xml", "X-TrackerToken: " . $p_pt_token)); 
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

					$res = curl_exec($ch);
					curl_close($ch);
				}
			}
		}
		#if it was not found here, we just ignored it, which is what we should do, so everything is good
	}
}

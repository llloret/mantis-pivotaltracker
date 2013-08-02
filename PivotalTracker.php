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
            'EVENT_REPORT_BUG' => 'new_bug'
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
		$config_proj_id_mantis = project_get_id_by_name($config_proj_integration_data[$i*3+0]);
		$config_proj_id_pt = $config_proj_integration_data[$i*3+1];
		$config_proj_integration_id_pt = $config_proj_integration_data[$i*3+2];
		if ($config_proj_id_mantis == $p_bug_data->project_id){
			$ch = curl_init("http://www.pivotaltracker.com/services/v3/projects/$config_proj_id_pt/stories");

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "<story><story_type>bug</story_type><name>[bug #$p_bug_id] " .
				"{$p_bug_data->summary}</name><description>{$p_bug_data->description}</description><requested_by>" . 
				plugin_config_get('userid') . 
				"</requested_by><external_id>$p_bug_id</external_id><integration_id>$config_proj_integration_id_pt</integration_id></story>");
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/xml", "X-TrackerToken: " . plugin_config_get('pt_token'))); 
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$res = curl_exec($ch);
			#	echo "RES: $res";
			curl_close($ch);
		}	
	}

	#if it was not found then, we just ignored it, which is what we should do
    }
}

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
		'pt_user_id' => '0',
		'pt_projects_and_integration_ids' => '0',
        );
    }

    function new_bug( $pEvent, $p_bug_data, $p_bug_id ) {
        echo "In method new_bug(). {$p_bug_data->summary} ({$p_bug_data->id})";
# curl -H "X-TrackerToken: 9474655f2f75d39f689d875a12a11b44" -X POST -H "Content-type: application/xml" \
#    -d "<story><story_type>feature</story_type><name>Fire torpedoes</name><requested_by>James Kirk</requested_by></story>" \
#    http://www.pivotaltracker.com/services/v3/projects/881952/stories
	$ch = curl_init("http://www.pivotaltracker.com/services/v3/projects/881952/stories");

	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "<story><story_type>bug</story_type><name>[bug #$p_bug_id] {$p_bug_data->summary}</name><description>{$p_bug_data->description}</description><external_id>$p_bug_id</external_id><integration_id>22638</integration_id></story>");
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/xml", "X-TrackerToken: ".plugin_config_get( 'pt_token' ))); 
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$res = curl_exec($ch);
#	echo "RES: $res";
	curl_close($ch);
    }

}

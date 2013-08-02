<?php
form_security_validate( 'plugin_PT_config_update' );

$f_pt_token = gpc_get_string( 'pt_token' );
$f_userid = gpc_get_string( 'userid' );
$f_projects_and_integration = gpc_get_string( 'projects_and_integration' );

plugin_config_set( 'pt_token', $f_pt_token );
plugin_config_set( 'userid', $f_userid );
plugin_config_set( 'projects_and_integration', $f_projects_and_integration );


form_security_purge( 'plugin_PT_config_update' );
print_successful_redirect( plugin_page( 'config_page', true ) );

<?php
form_security_validate( 'plugin_PT_config_update' );

$f_pt_token = gpc_get_string( 'pt_token' );

plugin_config_set( 'pt_token', $f_pt_token );


form_security_purge( 'plugin_PT_config_update' );
print_successful_redirect( plugin_page( 'config_page', true ) );

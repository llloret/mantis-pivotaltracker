<?php

html_page_top( plugin_lang_get( 'config' ) );
$t_pt_token = plugin_config_get( 'pt_token' );
$t_userid = plugin_config_get( 'userid' );
$t_projects_and_integration = plugin_config_get( 'projects_and_integration' );

?>

<br/>

<form action="<?php echo plugin_page( 'config_update' ) ?>" method="post">
<?php echo form_security_field( 'plugin_PT_config_update' ) ?>
<table class="width60" align="center">

<tr <?php echo helper_alternate_class() ?>>
    <td class="category"><?php echo plugin_lang_get( 'pt_token' ) ?></td>
    <td><input name="pt_token" size=40 value="<?php echo string_attribute( $t_pt_token ) ?>"/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
    <td class="category"><?php echo plugin_lang_get( 'userid' ) ?></td>
    <td><input name="userid" size=40 value="<?php echo string_attribute( $t_userid ) ?>"/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
    <td class="category"><?php echo plugin_lang_get( 'projects_and_integration' ) ?></td>
    <td><input name="projects_and_integration" size=40 value="<?php echo string_attribute( $t_projects_and_integration ) ?>"/></td>
</tr>


<tr>
    <td class="center" rowspan="2"><input type="submit"/></td>
</tr>

</table>
</form>

<?php

html_page_bottom();

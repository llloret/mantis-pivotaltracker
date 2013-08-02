<?php

html_page_top( plugin_lang_get( 'config' ) );
$t_pt_token = plugin_config_get( 'pt_token' );

?>

<br/>

<form action="<?php echo plugin_page( 'config_update' ) ?>" method="post">
<?php echo form_security_field( 'plugin_PT_config_update' ) ?>
<table class="width60" align="center">

<tr>
    <td class="form-title" rowspan="2"><?php echo plugin_lang_get( 'pt_token' ) ?></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
    <td class="category"><php echo plugin_lang_get( 'pt_token' ) ?></td>
    <td><input name="pt_token" value="<?php echo string_attribute( $t_pt_token ) ?>"/></td>
</tr>

<tr>
    <td class="center" rowspan="2"><input type="submit"/></td>
</tr>

</table>
</form>

<?php

html_page_bottom();

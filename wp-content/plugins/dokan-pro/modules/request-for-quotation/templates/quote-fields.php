<?php
if ( ! is_user_logged_in() ) :
	?>
<table class='form-table announcement-meta-options'>
    <tbody>
    <tr>
        <td>
            <?php esc_html_e( 'Full Name', 'dokan' ); ?>
        </td>
        <td>
            <input type='text' size='50' placeholder='<?php esc_html_e( 'Full Name', 'dokan' ); ?>' name='name_field' required='required' style='border-left: 2px solid rgb(202, 16, 16);'>
        </td>
    </tr>
    <tr>
        <td>
            <?php esc_html_e( 'Email', 'dokan' ); ?>
        </td>
        <td>
            <input type='email' size='50' placeholder='<?php esc_html_e( 'Email', 'dokan' ); ?>' name='email_field' required='required' style='border-left: 2px solid rgb(202, 16, 16);'>
        </td>
    </tr>
    <tr>
        <td>
            <?php esc_html_e( 'Company Name', 'dokan' ); ?>
        </td>
        <td>
            <input type='text' size='50' placeholder='<?php esc_html_e( 'Company Name', 'dokan' ); ?>' name='company_field'>
        </td>
    </tr>
    <tr>
        <td>
            <?php esc_html_e( 'Phone Number', 'dokan' ); ?>
        </td>
        <td>
            <input type='text' size='50' placeholder='<?php esc_html_e( 'Phone Number', 'dokan' ); ?>' name='phone_field'>
        </td>
    </tr>
    </tbody>
</table>
	<?php
endif;
?>

<?php
if(!class_exists('Group_Sence_Mods'))
{
    if ( bp_is_active( 'groups' ) ) :
        class Group_Sence_Mods extends BP_Group_Extension {
            /**
             * Your __construct() method will contain configuration options for 
             * your extension, and will pass them to parent::init()
             */
            function __construct() {
                $args = array(
                    'slug' => 'group-sence',
                    'name' =>  __('Sence Settings','wplms-sence'),
                    'access' => apply_filters('wplms_sence_auhority','mod'),
                );
                parent::init( $args );
            }
         
            /**
             * display() contains the markup that will be displayed on the main 
             * plugin tab
             */
            function display( $group_id = NULL ) {
                $group_id = bp_get_group_id();

                //save and send mail :
                if (isset($_POST) && isset( $_POST['wplms_sence'] ) &&  wp_verify_nonce( $_POST['wplms_sence'], 'wplms_sence'.$group_id ) && isset($_POST['wplms_sence_post']) && $_POST['wplms_sence_post']=='wplms_sence_post'){
                    $setting = isset( $_POST['sence_id'] ) ? $_POST['sence_id'] : '';
                    $setting2 = isset( $_POST['sence_password'] ) ? $_POST['sence_password'] : '';
                    groups_update_groupmeta( $group_id, 'sence_id', $setting );
                    groups_update_groupmeta( $group_id, 'sence_password', $setting2 );

                }

                //mail form : 
                $setting = groups_get_groupmeta( $group_id, 'sence_id' );
                $setting2 = groups_get_groupmeta( $group_id, 'sence_password' );
         
                ?>
                <?php echo _x('Set Snece id and password here','','wplms-sence');?>
                <br>
                <form method="post" > 
                  <br>
                <label for="sence_id"><?php echo __('Sence ID','wplms-sence');?></label>
                <input type="text" id="sence_id" name="sence_id" value="<?php echo stripslashes($setting ); ?>">
                  &nbsp;<br>

                <label for="sence_password"><?php echo __('Sence Password','wplms-sence');?></label>
                <input type="text" id="sence_password" name="sence_password" value="<?php echo stripslashes($setting2 ); ?>">


                
                <?php wp_nonce_field( 'wplms_sence'.$group_id, 'wplms_sence' ); ?>
                <button type="submit" value="wplms_sence_post" id="save" name="wplms_sence_post"><?php echo __('Send','wplms-sence')?></button>
                </form>
                <?php
            }
         
           
        }
     
        bp_register_group_extension( 'Group_Sence_Mods' );
    endif; 
}
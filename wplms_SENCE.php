<?php
/**
 * SENCE Integration
 *
 * @author      VibeThemes
 * @category    Admin
 * @package     SENCE
 * @version     3.9
 */

 if ( ! defined( 'ABSPATH' ) ) exit;

define('BP_SENCE_USER_TYPE','SENCE');
class WPLMS_SENCE{

    public static $instance;
    public static function init(){
    if ( is_null( self::$instance ) )
        self::$instance = new WPLMS_SENCE();

        return self::$instance;
    }

    private function __construct(){

    	add_action('wp_login',array($this,'report_SENCE_portal'),10,2);
    	add_action('wp_login',array($this,'record_start_time'),10,2);

        add_action('template_redirect',array($this,'check_SENCE_user_session'));
    	add_action('wp_footer',array($this,'track_session_time'));
    }


    function record_start_time($user_login,$user){

    	//detect if current user is a SENCE user

    	//Set a user meta with the timestmap.


    }

    function check_SENCE_user_session(){
        if(is_user_logged_in()){
            $user_id = get_current_user_id();
            if(bp_get_member_type($user_id) == BP_SENCE_USER_TYPE){
                $login_time = get_user_meta($user_id,'last_login_time',true);
                if( (time() - $login_time ) > 2*3600 + 50*60){
                    wp_logout();
                }
            }
        }

    }
    function track_session_time(){

    	if(is_user_logged_in()){
    		$user_id = get_current_user_id();
    		$login_time = get_user_meta($user_id,'last_login_time',true);
    		if(!empty($login_time)){
    			$time = $login_time - time();
    		?>
    			<script>
    				setTimeout(function(){
    					alert('<?php echo 'Session timed out'; ?>');
    					location.reload();
    				},<?php echo $time; ?>*1000);
    			</script>
    		<?php
    		}
    	}
    }

    function report_SENCE_portal($user_login,$user){

    }
}

WPLMS_SENCE::init();
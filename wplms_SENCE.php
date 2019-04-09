<?php
/*
Plugin Name: Wplms sence integration
Plugin URI: http://www.vibethemes.com
Description: integrates wplms to sence
Version: 1.0
Author: VibeThemes
Author URI: http://www.VibeThemes.com/
License : GPLv2 or Later
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
        $this->settings = get_option('wplms_sence_settings');
    	//add_action('wp_login',array($this,'report_SENCE_portal'),10,2);
    	//add_action('wp_login',array($this,'record_start_time'),10,2);
        add_action('wplms_before_start_course',array($this,'wplms_before_start_course_status'));
        add_action('template_redirect',array($this,'check_SENCE_user_session'));
    	add_action('wp_footer',array($this,'track_session_time'));
        add_action('clear_auth_cookie',array($this, 'users_last_login'), 10);
        

        //codigoSence for course id
        add_filter('wplms_course_metabox',array($this,'codigoSence_fileds'));
        add_filter('wplms_course_creation_tabs',array($this,'codigoSence_front_end'));
    }

    function users_last_login() {
        $user_id = get_current_user_id();
        delete_user_meta($user_id,'login_expiry');
        //maybe:yes report to sence portal
        if(!empty($_COOKIE['course'])){
            $course_id = $_COOKIE['course'];
            
            $return  = $this->report_SENCE_portal($user_id,2,$course_id);
            add_action('wp_head',function () use ($return) {
                echo '<div class="message success" style="width:100%;"><p>'.$return.'</p></div>';
            });
        }
    }


    function wplms_before_start_course_status(){
        $course_id = 0;
        $course_id = $_POST['course_id'];
        if(empty($course_id) && !empty($_COOKIE['course'])){
            $course_id = $_COOKIE['course'];
        }
        if(!empty($course_id) &&  is_user_logged_in()){
            $user_id = get_current_user_id();
            $check_last_expiry = get_user_meta($user_id,'login_expiry',true);
            if(!empty($check_last_expiry)){
                if($check_last_expiry > time()){
                    //do nothing
                }else{
                    //record the expiry start session on sence .
                    $this->record_start_time($user_id);
                    $return = $this->report_SENCE_portal($user_id,1,$course_id);
                    add_action('wp_head',function () use ($return) {
                        echo '<div class="message success" style="width:100%;"><p>'.$return.'</p></div>';
                    });
                }

            }else{
                //record the expiry start session on sence .
                $this->record_start_time($user_id);
                $return =  $this->report_SENCE_portal($user_id,1,$course_id);
                add_action('wp_head',function () use ($return) {
                    echo '<div class="message success" style="width:100%;"><p>'.$return.'</p></div>';
                });
            }
        }
    }

    function codigoSence_front_end($settings){
            
                
        $fields = $settings['course_settings']['fields'];
        $arr=array(array(
                    'label' => __('CodigoSence ID','wplms-front-end'), // <label>
                    'desc'  => __('CodigoSence ID','wplms-front-end'), 
                    'text'=>__('Set CodigoSence ID','wplms-front-end' ),// description
                    'id'  => 'codigoSence', // field id and name
                    'type'  => 'text', // type of field
        ));
     
        array_splice($fields, (count($fields)-1), 0,$arr );
        $settings['course_settings']['fields'] = $fields;  
     
      return $settings;
    }

    function codigoSence_fileds($field1){
       
       $field1[]=array( // Text Input
       'label' => __('CodigoSence ID','vibe-customtypes'), // <label>
       'desc'  => __('CodigoSence ID','vibe-customtypes'), // description
       'id'    => 'codigoSence', // field id and name
       'type'  => 'text' // type of field
       );
       return $field1;
       
    }


    function record_start_time($user_id){

    	//detect if current user is a SENCE user

    	//Set a user meta with the timestmap.
        $sence_id = bp_get_profile_field_data(array('field'=>$this->settings['sence_id'],'user_id'=>$user_id));
     
        if(!empty($sence_id )){
            $expiry = time()+2*3600 + 30*60;
            update_user_meta($user_id,'login_expiry',$expiry);
        }
    }

    function check_SENCE_user_session(){
        if(is_user_logged_in()){
            $user_id = get_current_user_id();
            //check profile field 

            $sence_id = bp_get_profile_field_data(array('field'=>$this->settings['sence_id'],'user_id'=>$user_id));
            if(!empty($sence_id )){
                $login_expiry = get_user_meta($user_id,'login_expiry',true);
                if(!empty($login_expiry)){
                    if( time() >= $login_expiry ){
                        delete_user_meta($user_id,'login_expiry');
                        //maybe:yes report to sence portal
                        if(!empty($_COOKIE['course'])){
                            $course_id = $_COOKIE['course'];
                            $return = $this->report_SENCE_portal($user_id,2,$course_id);
                            add_action('wp_head',function () use ($return) {
                                echo '<div class="message success" style="width:100%;"><p>'.$return.'</p></div>';
                            });
                        }
                        
                        wp_logout();
                        
                    }
                }
                
            } 
        }
    }

    function track_session_time(){

    	if(is_user_logged_in()){
    		$user_id = get_current_user_id();
    		$login_expiry = get_user_meta($user_id,'login_expiry',true);
    		if(!empty($login_expiry)){
                if($login_expiry > time()){
                    $time = $login_expiry - time();
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
    }

    function report_SENCE_portal($user_id,$in_out,$course_id){
        
            //means login
        $codigoSence = get_post_meta($course_id,'codigoSence',true);
        //print_r($codigoSence);
        $sence_id = bp_get_profile_field_data(array('field'=>$this->settings['sence_id'],'user_id'=>$user_id));
        $sence_password = bp_get_profile_field_data(array('field'=>$this->settings['sence_password'],'user_id'=>$user_id));
        //print_r($sence_id .'-----'.$sence_password);
        if(!empty($codigoSence)){

            if($in_out==1){
                //means login
                $return = '';
                $soapURL = "http://elearningtest.sence.cl/Webservice/SenceElearning.svc?wsdl";
                $soapFunction = "RegistrarActividad";

                $soapFunctionParameters = array(
                    'codigoSence' => $codigoSence,//required  -- course id
                    'rutAlumno' => $sence_id,//required sence id --  profile field of user
                    'claveAlumno' =>$sence_password, //required sence password -- profile field of user
                    'rutOtec' => $this->settings['rutOtec'],//required sence company id --  option table setting
                    'claveOtec' =>$this->settings['claveOtec'],//required sence company password --  option table setting
                    'estadoActividad' =>$in_out
                );
                $soapClient = new SoapClient($soapURL);

                $soapResult = $soapClient->__soapCall($soapFunction, array($soapFunctionParameters));

                //print_r(  $soapResult);
                $soapResult = $this->obj2array($soapResult); 
                //print_r(  $soapResult);die();
                //response: 123795074514011034-5-----12345678stdClass Object ( [RegistrarActividadResult] => 32 ) Array ( [RegistrarActividadResult] => 32 )

                $return .= "Resultado "; $return .= $soapResult['RegistrarActividadResult']; 
                if(is_array($soapResult) && isset($soapResult['RegistrarActividadResult'])) {
                    // Process result.
                    $return .= "<br>Resultado Exitoso";
                } else {
                    // Unexpected result if(function_exists("debug_message"))
                
                    $return .= debug_message("Unexpected soapResult for {$soapFunction}: ".print_r($soapResult,TRUE)) ;  
                }
            }

            if($in_out==2){

                //means logout
                $return = '';
                $soapURL = "http://elearningtest.sence.cl/Webservice/SenceElearning.svc?wsdl";
                $soapFunction = "RegistrarActividad";

                $soapFunctionParameters = array(
                    'codigoSence' => $codigoSence,//required  -- course id
                    'rutAlumno' => $sence_id,//required sence id --  profile field of user
                    'claveAlumno' =>$sence_password, //required sence password -- profile field of user
                    'rutOtec' => $this->settings['rutOtec'],//required sence company id --  option table setting
                    'claveOtec' =>$this->settings['claveOtec'],//required sence company password --  option table setting
                    'estadoActividad' =>$in_out
                );
                $soapClient = new SoapClient($soapURL);

                $soapResult = $soapClient->__soapCall($soapFunction, array($soapFunctionParameters));
                
                $soapResult = $this->obj2array($soapResult); 
                
                $return .= "Resultado "; $return .= $soapResult['RegistrarActividadResult']; 
                if(is_array($soapResult) && isset($soapResult['RegistrarActividadResult'])) {
                    // Process result.
                    $return .= "<br>Resultado Exitoso";
                } else {
                    // Unexpected result if(function_exists("debug_message"))
                
                    $return .= debug_message("Unexpected soapResult for {$soapFunction}: ".print_r($soapResult,TRUE)) ;  
                }
            }

        }
        return $return;
    }

    function obj2array($obj) {
        $out = array(); foreach ($obj
        as $key => $val) { switch(true)
        { case is_object($val):
        $out[$key] = $this->obj2array($val); break;
        case is_array($val):
        $out[$key] = $this->obj2array($val); break;
        default:
        $out[$key] = $val;
        } }
        return $out;
    }


}



WPLMS_SENCE::init();


include_once('class.settings.php');

/*add_action('bp_init',function(){
    include_once('class.batch.php');
});*/



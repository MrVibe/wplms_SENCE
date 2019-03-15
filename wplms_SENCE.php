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
    	add_action('wp_login',array($this,'report_SENCE_portal'),10,2);
    	add_action('wp_login',array($this,'record_start_time'),10,2);

        add_action('template_redirect',array($this,'check_SENCE_user_session'));

    	add_action('wp_footer',array($this,'track_session_time'));
        //codigoSence for course id
        add_filter('wplms_course_metabox',array($this,'codigoSence_fileds'));
        add_filter('wplms_course_creation_tabs',array($this,'codigoSence_front_end'));
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


    function record_start_time($user_login,$user){

    	//detect if current user is a SENCE user

    	//Set a user meta with the timestmap.
        $sence_id = bp_get_profile_field_data(array('field'=>$this->settings['sence_id'],'user_id'=>$user->ID));
     
        if(!empty($sence_id )){
            $expiry = time()+2*3600 + 50*60;
            update_user_meta($user->ID,'login_expiry',$expiry);
        }
    }

    function check_SENCE_user_session(){
        if(is_user_logged_in()){
            $user_id = get_current_user_id();
            //check profile field 

            $sence_id = bp_get_profile_field_data(array('field'=>$this->settings['sence_id'],'user_id'=>$user_id));
            if(!empty($sence_id )){
                $login_expiry = get_user_meta($user_id,'login_expiry',true);
                if( time() >= $login_expiry ){
                    wp_logout();
                    //maybe report to sence portal
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
                    //maybe report to sence portal
                }
    			
    		}
    	}
    }

    function report_SENCE_portal($user_login,$user){
        $return = '';
        $soapURL = "http://elearningtest.sence.cl/Webservice/SenceElearning.svc?wsdl";
        $soapFunction = "RegistrarActividad";

        $soapFunctionParameters = array(
            'codigoSence' => 'xxxxxxxxxx',//required  -- course id
            'rutAlumno' => 'xxxxxxxxx',//required sence id --  profile field of user
            'claveAlumno' => 'xxxxxx', //required sence password -- profile field of user
            'rutOtec' => 'xxxxxxx',//required sence company id --  batch meta
            'claveOtec' => 'xxxxxxx',//required sence company password --  batch meta
            'estadoActividad' =>'1'
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



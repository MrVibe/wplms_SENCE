<?php


 if ( ! defined( 'ABSPATH' ) ) exit;

class wplms_sence_settings{

	var $settings;
	var $option = 'wplms_sence_settings';
	public static $instance;
    public static function init(){
    if ( is_null( self::$instance ) )
        self::$instance = new wplms_sence_settings();

        return self::$instance;
    }

    private function __construct(){
    	add_options_page(__('Wplms Sence settings','wplms-sence'),__('Wplms Sence','wplms-sence'),'manage_options','wplms-sence',array($this,'settings'));
		add_action('admin_enqueue_scripts',array($this,'enqueue_admin_scripts'));
		$this->settings=$this->get();
		
    }

	function enqueue_admin_scripts($hook){
		if ( 'settings_page_wplms-sence' != $hook ) {
        	return;
    	}
	}

	function get(){
		return get_option($this->option);
	}

	function settings(){
		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
		$this->settings_tabs($tab);
		$this->$tab();
	}

	function settings_tabs( $current = 'general' ) {
	    $tabs = array( 
	    		'general' => __('General','wplms-sence'), 
	    		
	    		);
	    echo '<div id="icon-themes" class="icon32"><br></div>';
	    echo '<h2 class="nav-tab-wrapper">';
	    foreach( $tabs as $tab => $name ){
	        $class = ( $tab == $current ) ? ' nav-tab-active' : '';
	        echo "<a class='nav-tab$class' href='?page=wplms-sence&tab=$tab'>$name</a>";

	    }
	    echo '</h2>';
	    if(isset($_POST['save'])){
	    	$this->save();
	    }
	}

	function general(){
		echo '<h3>'.__('Wplms Sence Settings','wplms-sence').'</h3>';
		$groups = bp_xprofile_get_groups( array(
			'fetch_fields' => true
		) );
 		$options_array = array(''=>_x('Select field','','wplms-sence'));
 		if(!empty($groups)){
 			foreach($groups as $group){
			
				if ( !empty( $group->fields ) ) {
					//CHECK IF FIELDS ENABLED
					foreach ( $group->fields as $field ) {
						$field = xprofile_get_field( $field->id );
						$options_array[$field->id] = $field->name.' ( '.$field->type.''.(empty($field->can_delete)?', '._x('Necessary','necessary fields for buddypress registration','wplms-phone-auth'):'').
						')';
						
					} // end for
					
				}
				
			}
 		}
		$settings=array(
				array(
					'label' => __('Sence ID buddypress profile field','wplms-sence'),
					'name' =>'sence_id',
					'type' => 'select',
					'options'=> $options_array,
					'desc' => __('Set Sence ID buddypress profile field settings','wplms-sence')
				),
				array(
					'label' => __('Sence password buddypress profile field','wplms-sence'),
					'name' =>'sence_password',
					'type' => 'select',
					'options'=> $options_array,
					'desc' => __('Set Sence password buddypress profile field settings','wplms-sence')
				),
				array(
					'label' => __('rutOtec','vibe-customtypes'),
					'name' =>'rutOtec',
					'type' => 'text',
					'desc' => __('Set a rutOtec ','wplms-sence')
				),
				array(
					'label' => __('claveOtec','vibe-customtypes'),
					'name' =>'claveOtec',
					'type' => 'text',
					'desc' => __('Set a claveOtec ','wplms-sence')
				),
		);

		$this->generate_form('general',$settings);
	}

	function generate_form($tab,$settings=array()){
		echo '<form method="post">
				<table class="form-table">';
		wp_nonce_field('save_settings','_wpnonce');   
		echo '<ul class="save-settings">';

		foreach($settings as $setting ){
			echo '<tr valign="top">';
			global $wpdb,$bp;
			switch($setting['type']){
				case 'textarea': 
					echo '<th scope="row" class="titledesc">'.$setting['label'].'</th>';
					echo '<td class="forminp"><textarea name="'.$setting['name'].'">'.(isset($this->settings[$setting['name']])?$this->settings[$setting['name']]:(isset($setting['std'])?$setting['std']:'')).'</textarea>';
					echo '<span>'.$setting['desc'].'</span></td>';
				break;
				case 'select':
					echo '<th scope="row" class="titledesc">'.$setting['label'].'</th>';
					echo '<td class="forminp"><select name="'.$setting['name'].'" class="chzn-select">';
					foreach($setting['options'] as $key=>$option){
						echo '<option value="'.$key.'" '.(isset($this->settings[$setting['name']])?selected($key,$this->settings[$setting['name']]):'').'>'.$option.'</option>';
					}
					echo '</select>';
					echo '<span>'.$setting['desc'].'</span></td>';
				break;
				case 'checkbox':
					echo '<th scope="row" class="titledesc">'.$setting['label'].'</th>';
					echo '<td class="forminp"><input type="checkbox" name="'.$setting['name'].'" '.(isset($this->settings[$setting['name']])?'CHECKED':'').' />';
					echo '<span>'.$setting['desc'].'</span></td>';
				break;
				case 'number':
					echo '<th scope="row" class="titledesc">'.$setting['label'].'</th>';
					echo '<td class="forminp"><input type="number" name="'.$setting['name'].'" value="'.(isset($this->settings[$setting['name']])?$this->settings[$setting['name']]:'').'" />';
					echo '<span>'.$setting['desc'].'</span></td>';
				break;
				case 'hidden':
					echo '<input type="hidden" name="'.$setting['name'].'" value="1"/>';
				break;
				case 'bp_fields':
					echo '<th scope="row" class="titledesc">'.$setting['label'].'</th>';
					echo '<td class="forminp"><a class="add_new_map button">'.__('Add BuddyPress profile field map','wplms-sence').'</a>';

					global $bp,$wpdb;;
					$table =  $bp->profile->table_name_fields;
					$bp_fields = $wpdb->get_results("SELECT DISTINCT name FROM {$table}");

					echo '<ul class="bp_fields">';
					if(is_array($this->settings[$setting['name']]['field']) && count($this->settings[$setting['name']]['field'])){
						foreach($this->settings[$setting['name']]['field'] as $key => $field){
							echo '<li><label><select name="'.$setting['name'].'[field][]">';
							foreach($setting['fields'] as $k=>$v){
								echo '<option value="'.$k.'" '.(($field == $k)?'selected=selected':'').'>'.$k.'</option>';
							}
							echo '</select></label><select name="'.$setting['name'].'[bpfield][]">';
							foreach($bp_fields as $f){
								echo '<option value="'.$f->name.'" '.(($this->settings[$setting['name']]['bpfield'][$key] == $f->name)?'selected=selected':'').'>'.$f->name.'</option>';
							}
							echo '</select><span class="dashicons dashicons-no remove_field_map"></span></li>';
						}
					}
					echo '<li class="hide">';
					echo '<label><select rel-name="'.$setting['name'].'[field][]">';
					foreach($setting['fields'] as $k=>$v){
						echo '<option value="'.$k.'">'.$k.'</option>';
					}
					echo '</select></label>';
					echo '<select rel-name="'.$setting['name'].'[bpfield][]">';
					
					foreach($bp_fields as $f){
						echo '<option value="'.$f->name.'">'.$f->name.'</option>';
					}
					echo '</select>';
					echo '<span class="dashicons dashicons-no remove_field_map"></span></li>';
					echo '</ul></td>';
				break;
				default:
					echo '<th scope="row" class="titledesc">'.$setting['label'].'</th>';
					echo '<td class="forminp"><input type="text" name="'.$setting['name'].'" value="'.(isset($this->settings[$setting['name']])?$this->settings[$setting['name']]:(isset($setting['std'])?$setting['std']:'')).'" />';
					echo '<span>'.$setting['desc'].'</span></td>';
				break;
			}
			
			echo '</tr>';
		}
		echo '</tbody>
		</table>';
		echo '<input type="submit" name="save" value="'.__('Save Settings','wplms-sence').'" class="button button-primary" /></form>';
	}


	function save(){
		$none = $_POST['save_settings'];
		if ( !isset($_POST['save']) || !isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'],'save_settings') ){
		    _e('Security check Failed. Contact Administrator.','wplms-sence');
		    die();
		}
		unset($_POST['_wpnonce']);
		unset($_POST['_wp_http_referer']);
		unset($_POST['save']);

		foreach($_POST as $key => $value){
			$this->settings[$key]=$value;
		}

		$this->put($this->settings);
	}

	function put($value){
		update_option($this->option,$value);
	}
}

add_action('admin_menu','init_wplms_sence_settings_settings',100);
function init_wplms_sence_settings_settings(){
	$obj = wplms_sence_settings::init();	
}

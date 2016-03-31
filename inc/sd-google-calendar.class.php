<?php
include "simple_html_dom.php";

class GoogleCalendars {
	public $default_fields = array(
		
	);
	
	function __construct() {
		add_action( 'init', array( $this, 'sdgc_init') );
	}
	
	function sdgc_init() {
		$this->sdgc_register_post_type();
		add_action( 'add_meta_boxes', array($this, 'sdgc_meta_box_main_data'));
		add_action( 'save_post', array($this, 'sdgc_save_meta_box_data'));
		add_action( 'admin_head', array($this, 'sdgc_AdminHeadSectionHook'));
		add_action( 'wp_head', array($this, 'sdgc_frontendHeadHook'));
		//$this->sdgc_filters();
		/*
		add_action( 'rest_api_init', function () {
		    register_rest_route( 'rest-routes/v2', '/(?P<id>[a-zA-Z-_0-9]+)', array(
			    'methods' => WP_REST_Server::READABLE,
			    'callback' => array( $this, 'custom_get_route' )
			) );
		} );
		*/
		add_action( 'admin_menu', array( $this, 'sdgc_admin_menu' ) );
		add_action( 'rest_api_init', array('SDGoogleCalendar_Custom_Route', 'init'));
		add_shortcode( 'sd_show_calendar', array( $this, 'showcalendartag_func' ));
		add_action( 'sdgc_save_fields', array($this, 'calendar_save_output'), 10, 2 );
	}
	function sdgc_frontendHeadHook(){
	  $output = "<link rel='stylesheet' href='".SDGC_URL."/css/sd-google-calendar.css'></link>";
	  echo $output;
	}
	function sdgc_AdminHeadSectionHook() {
	  $output = "<script src='".SDGC_URL."/js/jscolor.min.js' type='text/javascript'></script>";
	  echo $output;
	}
	function sdgc_admin_menu() {
		add_menu_page('Google Calendars', 'Google Calendars', 'manage_options', 'edit.php?post_type=sd-google-calendars', '',  'dashicons-share');
		add_submenu_page( 'edit.php?post_type=sd-google-calendars', 'Calendars', 'My Google Calendars', 'manage_options', 'edit.php?post_type=sd-google-calendars');
		//add_submenu_page( 'edit.php?post_type=rest-routes', 'Settings', 'Settings', 'manage_options', 'wprr-settings', 'wprr_admin_menu_settings' );
		//add_submenu_page( 'edit.php?post_type=rest-routes', 'Help', 'Help', 'manage_options', 'wprr-help', 'wprr_admin_menu_help' );
	}

	function sdgc_admin_menu_routes(){}
	
	function sdgc_register_post_type() {
		$labels = array(
			'name'               => _x( 'Google Calendars', 'post type general name', TEXTDOMAIN ),
			'singular_name'      => _x( 'Google Calendar', 'post type singular name', TEXTDOMAIN ),
			'menu_name'          => _x( 'Google Calendars', 'admin menu', TEXTDOMAIN ),
			'name_admin_bar'     => _x( 'Calendar', 'add new on admin bar', TEXTDOMAIN ),
			'add_new'            => _x( 'Add New', 'calendar', TEXTDOMAIN ),
			'add_new_item'       => __( 'Add New Calendar',  TEXTDOMAIN ),
			'new_item'           => __( 'New Calendar', TEXTDOMAIN ),
			'edit_item'          => __( 'Edit Calendar', TEXTDOMAIN ),
			'view_item'          => __( 'View Calendar', TEXTDOMAIN ),
			'all_items'          => __( 'All Calendars', TEXTDOMAIN ),
			'search_items'       => __( 'Search Calendars', TEXTDOMAIN ),
			'parent_item_colon'  => __( 'Parent Calendars:', TEXTDOMAIN ),
			'not_found'          => __( 'No calendars found.', TEXTDOMAIN ),
			'not_found_in_trash' => __( 'No calendars found in Trash.', TEXTDOMAIN )
		);

		$args = array(
			'labels'             => $labels,
	        'description'        => __( 'Description.', 'Combine Google Calendars for display on your website.' ),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'query_var'          => false,
			'rewrite'            => array( 'slug' => 'sd-google-calendars' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title' )
		);

		register_post_type( 'sd-google-calendars', $args );
	}
	
	function sdgc_meta_box_main_data() {
		$screens = array( 'sd-google-calendars' );
		foreach ( $screens as $screen ) {
			add_meta_box(
				'sdgc_calendar_builder',
				__( 'Combined Google Calendars', TEXTDOMAIN ),
				array( $this, 'sdgc_calendar_builder_callback' ),
				$screen
			);
			add_meta_box(
				'sdgc_calendar_output',
				__( 'Calendar Output', TEXTDOMAIN ),
				array( $this, 'sdgc_calendar_details_callback' ),
				$screen
			);
		}
	}
	
	function sdgc_calendar_builder_callback( $post ) {
		global $wpdb;
		// Add a nonce field so we can check for it later.
		wp_nonce_field( 'sdgc_save_meta_box_data', 'sdgc_meta_box_nonce' );
		$output = '';
		echo '
		<style>
			.sdgc-container .url-column{
				float: left;
				width: 50%;
				clear: both;
			}
			.sdgc-container .content-column{
				float: left;
				width: 40%;
				padding: 10px;
			}
			.sdgc-container .content-column ul{
			    margin: 0px;
			}
			.sdgc-container .content-column ul li {
			    background: #F1F1F1;
			    display: block;
			    padding: 10px;
			    margin-bottom: 0px;
			    border: 1px solid #E5E5E5;
			    border-left: none;
			    border-top: none;
			}
			.sdgc-container .content-column ul li a{
				text-decoration: none;
			}
			.sdgc-container .content-column ul li:hover{
				background: #E5E5E5;
				cursor: pointer;
			}
			.sdgc-container .content-column ul .item-active{
				background: #E5E5E5;
				cursor: pointer;
			}
			#sdgc_calendar_builder .inside{
				margin: 0px;
				padding: 0px;
			}
			#sdgc_calendar_builder .clean{
				clear: both;
			}
			#sdgc_calendar_builder .form-table th{
				padding: 0px;
			}
			#sdgc_calendar_builder .form-table td{
				padding: 0px;
			}
			.sdgc-calendar-fields td{
				padding: 0px;
			}
		}
		</style>

		<script>
			jQuery("document").ready(function(){
			});
		</script>';

		$value = get_post_meta( $post->ID, '_my_meta_value_key', true );
		$post_fields_arr = $this->default_fields;
		$custom_fields_query = $wpdb->get_results(
			'SELECT distinct( meta_key )
			 FROM '.$wpdb->postmeta.'
			 WHERE meta_key NOT LIKE "\_%"',
			 ARRAY_N
		);
		$custom_fields_arr = array();
		foreach ($custom_fields_query as $key => $value) {
			foreach ($value as $key2 => $value2) {
				$custom_fields_arr[] = $value2;
			}
		}
		$post_fields_urls = unserialize( get_post_meta($post->ID, '_sdgc_calendar_urls', true) );
		//$post_fields_colors = unserialize( get_post_meta($post->ID, '_sdgc_calendar_colors', true) );
		$post_fields_timezone = unserialize( get_post_meta($post->ID, '_sdgc_calendar_timezone', true) );
		$post_fields_numdays = unserialize( get_post_meta($post->ID, '_sdgc_calendar_numdays', true) );
		$post_fields_arr = array_merge($post_fields_arr, $custom_fields_arr);
		$max_fields = 1;
		echo '<div class="sd-calendar-combined-div" style="margin:10px;">';
		echo '	<p>Define Google Calendars you want to combine.</p>
				<table class="form-table sdgc-calendar-fields">
				    <tbody>
				        <tr>
				        	<th scope="row">Timezone</th>
				        	<td id="sdgc_calendar_timezone">';
				        		$utc = new DateTimeZone('UTC');
								$dt = new DateTime('now', $utc);
								echo '<select name="sdgc_calendar_timezone">';
								foreach(DateTimeZone::listIdentifiers() as $tz) {
									$current_tz = new DateTimeZone($tz);
									$offset =  $current_tz->getOffset($dt);
									$transition =  $current_tz->getTransitions($dt->getTimestamp(), $dt->getTimestamp());
									$abbr = $transition[0]['abbr'];
									echo '<option value="' .$tz. '" '.(strcmp($tz,$post_fields_timezone) == 0 ? 'selected' : ''). '>' .$tz. ' [' .$abbr. ' '. $this->formatOffset($offset). ']</option>';
								}
								echo '</select><br/><br/>';
echo '			        	</td>
				        </tr>
				        <tr><th scope="row">Days</th>
				        	<td>Days in advance from current day to display (in Agenda mode):<br/>
				        		<input type="number" name="sdgc_calendar_numdays" id="sdgc_calendar_numdays" value="'.(!empty($post_fields_numdays) ? $post_fields_numdays : 30).'" placeholder="# of Days"/>
				        		<br/><br/>
				        	</td>
				        </tr>
				        <tr>
						    <th scope="row">Calendars</th>
							<td id="sdgc_calendar_fields">';
						    $i = 0;
						    if($post_fields_urls){
							    foreach ($post_fields_urls as $key => $value) {
							    	$i++;
echo '								<fieldset id="sdgc_calendar_fieldset_'.$i.'">
										<table>
											<tr><th>Google Calendar ID</th><th></th></tr>
											<tr><td><input type="email" id="sdgc_calendar_url_'.$i.'" name="sdgc_calendar_urls[]" value="'.$value.'" placeholder="(ex: test@group.calendar.google.com)" size="50"/></td>
												<td><a class="sdgc-remove-item button button-small" data-sdgc-field="'.$i.'">Delete</a></td>
											</tr>
										</table>
									</fieldset>
									<br/>';
									//<th>Text Color</th>
									//<td><input type="text" class="jscolor" id="sdgc_calendar_color_'.$i.'" name="sdgc_calendar_colors[]" value="'.$post_fields_colors[$key].'" placeholder="000000"/></td>
								}
							}else{
								$i = 1;
echo '							<fieldset id="sdgc_calendar_fieldset_'.$i.'">
									<table>
										<tr><th>Google Calendar ID</th><th></th></tr>
										<tr><td><input type="email" id="sdgc_calendar_url_'.$i.'" name="sdgc_calendar_urls[]" placeholder="(ex: test@group.calendar.google.com)" size="50"/></td>
											<td><a class="sdgc-remove-item button button-small" data-sdgc-field="'.$i.'">Delete</a></td>
					  					</tr>
					  				</table>
								</fieldset>
								<br/>';
								//<th>Text Color</th>
								//<td><input type="text" class="jscolor" id="sdgc_calendar_color_'.$i.'" name="sdgc_calendar_colors[]" placeholder="000000"/></td>
							}
echo '			        	</td>
						</tr>
						<tr>
							<th></th>
							<td class="sdgc-add-field">
								<a class="sdgc-add-item button button-primary button-large">Add Field</a>
							</td>
						</tr>
				    </tbody>
				</table>';
echo '        </div>';
echo '			<script>
				  jQuery( document ).ready(function() {
					var counter = 0;
				  	jQuery( "a.sdgc-add-item" ).click(function(event) {
					   	jQuery("#sdgc_calendar_fields").append("<fieldset><table><tr><th>Google Calendar ID</th></tr><tr><td><input type=\"email\" id=\"sdgc_calendar_url_new_"+counter+"\" name=\"sdgc_calendar_urls[]\" placeholder=\"(ex: test@group.calendar.google.com)\" size=\"50\"/></td></tr></table></fieldset>");
					   	counter = counter + 1;
					});
					jQuery(".sdgc-remove-item").click(function(event){
						jQuery("#sdgc_calendar_fieldset_"+jQuery(this).data("sdgc-field")).remove();
					})
				});
			  </script>
			  ';
			  //<th>Text Color</th>
			  //<td><input type=\"text\" class=\"jscolor\" id=\"sdgc_calendar_color_new_"+counter+"\" name=\"sdgc_calendar_colors[]\" placeholder=\"000000\" value=\"000000\"/></td>
		echo $output;
	}
	
	function formatOffset($offset) {
        $hours = $offset / 3600;
        $remainder = $offset % 3600;
        $sign = $hours > 0 ? '+' : '-';
        $hour = (int) abs($hours);
        $minutes = (int) abs($remainder / 60);
        if ($hour == 0 AND $minutes == 0) {
            $sign = ' ';
        }
        return $sign . str_pad($hour, 2, '0', STR_PAD_LEFT) .':'. str_pad($minutes,2, '0');
	}
	
	function sdgc_calendar_details_callback( $post ) {
		echo '<p>Use the following shortcode: </p>';
		echo '<strong>[sd_show_calendar id="'.$post->ID.'"]</strong>';
		echo '<br/><p>Other attributes:</p>
			  <ul>
				<li><strong>type</strong> = "agenda" displays the calendar as an agenda list for the number of days defined. (default)</li>
			  </ul>';
		echo '<p>JSON Using REST v2 API: <a target="_blank" href="'.get_site_url().'/wp-json/sd-google-calendar/v1/sdcalendar/'.$post->ID.'">'.get_site_url().'/wp-json/sd-google-calendar/v1/sdcalendar/'.$post->ID.'</a></p>';
//			  <a target="_blank" href="'.site_url().'/wp-json/rest-routes/v2/'.$post->post_name.'">'.site_url().'/wp-json/rest-routes/v2/'.$post->post_name.'</a></p>';
	}
	
	function sdgc_save_meta_box_data( $post_id ) {
		// Check if our nonce is set.
		if ( ! isset( $_POST['sdgc_meta_box_nonce'] ) ) {
			return;
		}
		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['sdgc_meta_box_nonce'], 'sdgc_save_meta_box_data' ) ) {
			return;
		}
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'sd-google-calendars' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}
		//add_action( 'sdgc_save_fields', array($this, 'calendar_save_output'), 10, 2 );
			
			echo $post_id;
		do_action( 'sdgc_save_fields', $_POST, $post_id );
	}
	
	function calendar_save_output( $fields, $post_id ){
		$timezone = serialize( $fields['sdgc_calendar_timezone'] );
		update_post_meta( $post_id, '_sdgc_calendar_timezone', $timezone );
		
		$numdays = serialize( $fields['sdgc_calendar_numdays'] );
		if(empty($numdays)) $numdays = 30;
		update_post_meta( $post_id, '_sdgc_calendar_numdays', $numdays );
		
		$urls = serialize( $fields['sdgc_calendar_urls'] );
		update_post_meta( $post_id, '_sdgc_calendar_urls', $urls );
		
// 		$colors = serialize( $fields['sdgc_calendar_colors'] );
// 		update_post_meta( $post_id, '_sdgc_calendar_colors', $colors );
	}	
	
	function showcalendartag_func( $atts ) {
		$atts = shortcode_atts( array(
			'id' => -1,
			'type' => "agenda"
		), $atts, 'sd_show_calendar' );
		if(isset($atts['id']) && $atts['id']*1>=0){
			if(strcmp($atts['type'], "agenda") == 0){
				return $this->getAgendaFromCalendar($atts['id']);
			}
		}
		return "";
	}
	
	public static function getCustomCalendarUrl($postId){
		$cal_urls = unserialize( get_post_meta($postId, '_sdgc_calendar_urls', true) );
		$cal_colors = unserialize( get_post_meta($postId, '_sdgc_calendar_colors', true) );
		$timezone = unserialize( get_post_meta($postId, '_sdgc_calendar_timezone', true) );
		date_default_timezone_set($timezone);
		$numdays = unserialize( get_post_meta($postId, '_sdgc_calendar_numdays', true) );
		$currentDate = new DateTime(date('Ymd'));
		$futureDate = date_add($currentDate, new DateInterval('P'.$numdays.'D'));
		$dates = date('Ymd')."/".$futureDate->format('Ymd');
		//dates=20160530/20160630
		if($cal_urls){
			$calendarurl = "https://calendar.google.com/calendar/htmlembed?mode=AGENDA&";
			$calendarurl .= "ctz=".urlencode($timezone)."&";
			$calendarurl .= "dates=".$dates."&";
			$i=0;
			foreach ($cal_urls as $key => $value) {
				$calendarurl .= "src=".urlencode($value)."&color=".urlencode("#".$cal_colors[$i])."&";
				$i++;
			}
			return $calendarurl;
		}
		return null;
	}
	
	function getAgendaFromCalendar($postId){
		$calendarurl = $this->getCustomCalendarUrl($postId);
	    //echo($calendarurl);
		if(!empty($calendarurl)){
			$html = file_get_html($calendarurl);
			$output = "<ul class='sd-calendar-list'>";
			foreach($html->find('div.date-section') as $element){
				$output .= "<li>";
				$output .= "<span class='sd-date-text'>".date_format(date_create_from_format('D M d, Y T',$element->find('div.date',0)->innertext . ' EST'), "Y-m-d")."</span><br/>";
				foreach($element->find('tr.event') as $event){
				   $output .= "<ul class='sd-date-events'>";
				   $output .= "<li><span class='sd-date-event-time'>".$event->find('td.event-time',0)->innertext."</span>";
				   $output .= "<a class='sd-date-event-link' href='https://calendar.google.com/calendar/".$event->find('a.event-link',0)->href."'>";
				   $output .= "<span class='sd-date-event-title'>".$event->find('span.event-summary',0)->innertext."</span>";
				   $output .= "</a></li>";
				   $output .= "</ul>";
				}
				$output .= "<br/></li>";
			}
			$output .= "</ul>";
			return $output;
		}
		return "";
	}
	
	/****

	function custom_get_route( WP_REST_Request $request) {

		global $wpdb;

		if( null !== $request->get_param( 'id' ) ){
			$route_info = $request->get_param( 'id' );
			$route = get_post( $route_info );

			if( $route != null ){
				$route_id = $route->ID;
			}else{
				$route_by_name = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE post_name = %s", $route_info ) );
				$route_id = $route_by_name->ID;
			}

		}

	    return $this->wprr_get_posts($args, $route_id, $this->default_fields);
	}

	function wprr_filters(){

		foreach ( array_reverse( glob( WPRR_INC_PATH . '/filters/wprr-filter-*.php' ) ) as $filename ) {
			include $filename;
		}

	}

	function wprr_output( $post_id, $default_fields, $post_ids ){

		global $wpdb;

		$output_columns_db = unserialize( get_post_meta( $post_id, '_wprr_output_fields', true ) );

		$post_fields = array();
		$post_custom_fields = array();

		foreach ($output_columns_db as $key => $value) {
			if( (string) array_search($value, $default_fields) != '' ){
				$post_fields[] = $value;
			}else{
				$post_custom_fields[] = $value;
			}
		}

		$post_meta_key = array();
		$output_columns = '';

		if( isset($post_fields) ){
			$posts_table = $wpdb->posts . ' p';
			$output_columns .= 'p.' . implode( ", p.", $post_fields );

			if ($post_custom_fields){
				$output_columns .= ', ';
			}
		}

		if( isset($post_custom_fields) ){

			foreach ($post_custom_fields as $key => $value) {

				$output_columns .= "COALESCE( ( SELECT 
									meta_value
									FROM
									".$wpdb->postmeta." pm
									WHERE pm.post_id = p.ID
									AND pm.meta_key = '".$value."' ), '' ) as " . $value;

				if( $value != end( $post_custom_fields ) ){
					$output_columns .= ', ';
				}
		
			}
		}

		$output_query = $wpdb->get_results(
			"
			SELECT
			".$output_columns."
			FROM 
			".$posts_table."
			WHERE
			p.ID IN (".$post_ids.")
			"
		);

		if( count( $output_columns_db ) === 1 ){

			$output_one_column = array();

			foreach ( $output_query as $key => $value ) {
				$output_one_column[] = $value->$output_columns_db[0];
			}

			return $output_one_column;
			
		}else{

			return $output_query;

		}
	}

	function wprr_get_posts( $args, $post_id, $default_fields ){

		global $wpdb;

		$args['fields'] = 'ids';
		$args['posts_per_page'] = -1;

		//var_dump(apply_filters( 'wprr_filter_query', $args, $post_id ));

		$post_ids_query = new WP_Query( apply_filters( 'wprr_filter_query', $args, $post_id ) );

		$post_ids = implode(',', $post_ids_query->posts);

		return $this->wprr_output($post_id, $default_fields, $post_ids);
	}
	*****/
	
}

class SDGoogleCalendar_Custom_Route extends WP_REST_Controller {

	private static $instance;
	private static $myController = null;
	
	public static function init(){
		if(self::$instance == null){
			self::$instance = new SDGoogleCalendar_Custom_Route();
		}
		self::$instance->register_routes();
		return self::$instance;
	}
	
    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes() {
        $version = '1';
        $namespace = 'sd-google-calendar/v' . $version;
        $base = 'sdcalendar';
        register_rest_route( $namespace, '/'.$base.'/(?P<id>[\d]+)', array(
            array(
                'methods'         => WP_REST_Server::READABLE,
                'callback'        => array( $this, 'get_item' ),
                'permission_callback' => array( $this, 'get_item_permissions_check' ),
                'args'            => array(
					'context'          => array(
                        'default'      => 'view',
                    ),
                ),
            )
        ) );
    }
    
    public function get_item_permissions_check( $request ) {
        return true; //<--use to make readable by all
        //return current_user_can( 'read_something' );
    }

    /**
     * Get post
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {
        $items = array(); //do a query, call another class, etc
        $data = array();
        foreach( $items as $item ) {
            $itemdata = $this->prepare_item_for_response( $item, $request );
            $data[] = $this->prepare_response_for_collection( $itemdata );
        }

        return new WP_REST_Response( $data, 200 );
    }
    
    function get_item($request) {
	  $params = $request->get_params();
	  $post = get_post($params['id']);
 	  $output .= $this->getCalendarPostAsJson($post);
	  //return $output;
	  return new WP_REST_Response( $output, 200 );
	}

	function getCalendarPostAsJson($post){
	  $calendarurl = GoogleCalendars::getCustomCalendarUrl($post->ID);
	  //echo($calendarurl);
	  if(!empty($calendarurl)){
		$html = file_get_html($calendarurl);
		$days = array();
		$i=0;
		foreach($html->find('div.date-section') as $element){
			$day = new stdClass();
			$day->events = array();
			$day->date = date_create_from_format('D M d, Y T',$element->find('div.date',0)->innertext . ' EST');
			$j=0;
			foreach($element->find('tr.event') as $event){
			   $evt = new stdClass();
			   $evt->time = (isset($event->find('div.tbg',0)->innertext) ? '' : $event->find('td.event-time',0)->innertext);
			   $evt->title = $event->find('span.event-summary',0)->innertext;
			   $evt->link = 'https://calendar.google.com/calendar/'.$event->find('a.event-link',0)->href;
			   $day->events[$j] = $evt;
			   $j++;
			}
			$days[$i] = $day;
			$i++;
		}
		
		$output .= "{[";
		foreach($days as $day){
			$output .= "{'date':'".date_format($day->date, "Y-m-d")."',";
			$output .= "'events':[";
			foreach($day->events as $evt){
				$output .= "{";
				if(!empty($evt->time))
					$output .= "'time':'".$evt->time."',";
				$output .= "'title':'".$evt->title."',";
				$output .= "'link':'".$evt->link."'";
				$output .= "},";
			}
			$output = rtrim($output, ",");
			$output .= "]},";
		}
		$output = rtrim($output, ",");
		$output .= "]}";
	  }
	  return isset($output) ? $output : "";
	}
}

?>
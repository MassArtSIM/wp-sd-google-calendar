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
		add_action( 'wp_head', array($this, 'sdgc_frontendHeadHook'));
		add_action( 'admin_menu', array( $this, 'sdgc_admin_menu' ) );
		add_shortcode( 'sd_show_calendar', array( $this, 'showcalendartag_func' ));
		add_action( 'sdgc_save_fields', array($this, 'calendar_save_output'), 10, 2 );
	}
	function sdgc_frontendHeadHook(){
	  $output = "<link rel='stylesheet' href='".SDGC_URL."/css/sd-google-calendar.css'></link>";
	  echo $output;
	}
	function sdgc_admin_menu() {
		add_menu_page('Google Calendars', 'Google Calendars', 'manage_options', 'edit.php?post_type=sd-google-calendars', '',  'dashicons-share');
		add_submenu_page( 'edit.php?post_type=sd-google-calendars', 'Calendars', 'My Google Calendars', 'manage_options', 'edit.php?post_type=sd-google-calendars');
	}
	
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
		</style>';

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
				  jQuery(function() {
					var counter = 0;
				  	jQuery( "a.sdgc-add-item" ).click(function(event) {
					   	jQuery("#sdgc_calendar_fields").append("<fieldset><table><tr><th>Google Calendar ID</th></tr><tr><td><input type=\"email\" id=\"sdgc_calendar_url_new_"+counter+"\" name=\"sdgc_calendar_urls[]\" placeholder=\"(ex: test@group.calendar.google.com)\" size=\"50\"/></td></tr></table></fieldset>");
					   	counter++;
					});
					jQuery(".sdgc-remove-item").click(function(event){
						jQuery("#sdgc_calendar_fieldset_"+jQuery(this).data("sdgc-field")).remove();
					})
				});
			  </script>
			  ';
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
		echo '<p>Other attributes:</p>';
		echo '<ul><li><strong>show_more="true"</strong> Adds a show more link at the bottom of the list.</li></ul>';
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
	}	
	
	function showcalendartag_func( $atts ) {
		$atts = shortcode_atts( array(
			'id' => -1,
			'show_more' => "false"
		), $atts, 'sd_show_calendar' );
		if(isset($atts['id']) && $atts['id']*1>=0){
			$showMore = strcmp($atts['show_more'], "true") == 0;
			return $this->getAgendaFromCalendar($atts['id'], $showMore);
		}
		return "";
	}
	
	public static function getCustomCalendarUrl($postId){
		$cal_urls = unserialize( get_post_meta($postId, '_sdgc_calendar_urls', true) );
		$timezone = unserialize( get_post_meta($postId, '_sdgc_calendar_timezone', true) );
		date_default_timezone_set($timezone);
		$numdays = unserialize( get_post_meta($postId, '_sdgc_calendar_numdays', true) );
		$currentDate = new DateTime(date('Ymd'));
		$futureDate = date_add($currentDate, new DateInterval('P'.$numdays.'D'));
		$dates = date('Ymd')."/".$futureDate->format('Ymd');
		$cal_colors = array(
			'#A32929',
			'#B1365F',
			'#7A367A',
			'#5229A3',
			'#29527A',
			'#2952A3',
			'#1B887A',
			'#28754E',
			'#0D7813',
			'#528800',
			'#88880E',
			'#AB8B00',
			'#BE6D00',
			'#B1440E',
			'#865A5A',
			'#705770',
			'#4E5D6C',
			'#5A6986',
			'#4A716C',
			'#6E6E41',
			'#8D6F47'
		);
		shuffle($cal_colors);
		if($cal_urls){
			$calendarurl = "https://calendar.google.com/calendar/htmlembed?mode=AGENDA&";
			$calendarurl .= "ctz=".urlencode($timezone)."&";
			$calendarurl .= "dates=".$dates."&";
			$i=0;
			foreach ($cal_urls as $key => $value) {
				$calendarurl .= "src=".urlencode($value)."&color=".urlencode($cal_colors[$i++])."&";
			}
			return $calendarurl;
		}
		return null;
	}
	
	function getAgendaFromCalendar($postId, $showMore){
		$calendarurl = $this->getCustomCalendarUrl($postId);
		if(!empty($calendarurl)){
			$html = file_get_html($calendarurl);
			$output = "<ul class='sd-calendar-list'>";
			foreach($html->find('div.date-section') as $element){
				$output .= "<li>";
				$output .= "<span class='sd-date-text'>".date_format(date_create_from_format('D M d, Y T',$element->find('div.date',0)->innertext . ' EST'), "Y-m-d")."</span><br/>";
				foreach($element->find('tr.event') as $event){
					$output .= "<ul class='sd-date-events'>";
					$output .= "<li>";
					$time = $event->find('td.event-time',0)->innertext;
					if (preg_match('/^[0-9\:apm]+$/', $time)) {
						$output .= "<span class='sd-date-event-time'>".$event->find('td.event-time',0)->innertext."</span>";
					}
					else {
						$output .= "<span class='sd-date-event-time sd-date-event-time-all-day'>all day</span>";
					}
					$output .= "<a class='sd-date-event-link' href='https://calendar.google.com/calendar/".$event->find(' a.event-link',0)->href."'>";
					$output .= "<span class='sd-date-event-title'>".$event->find('span.event-summary',0)->innertext."</ span>";
					$output .= "</a></li>";
					$output .= "</ul>";
				}
				$output .= "<br/></li>";
			}
			$output .= "</ul>";
			if ($showMore) {
				$output .= "<div class='sd-show-more'><a class='sd-show-all-link' target='_blank' href='".str_replace('htmlembed', 'embed', $calendarurl)."showTitle=0&showPrint=0&showTz=0'>Show More...</a></div>";
			}
			return $output;
		}
		return "";
	}	
}

?>
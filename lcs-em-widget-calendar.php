<?php
/*
Plugin Name: LCS Fast Calendar Widget for Events Manager
Plugin URI: http://www.latcomsystems.com/index.cfm?SheetIndex=wp_lcs_em_widget_calendar
Description: If you are using the Events Manager sidebar calendar widget AND you have a lot of events AND you're using either event categories or event tags or both, this will be a much faster sidebar calendar widget than the one that comes with the Events Manager plugin.  Replace the existing slow Events Manger sidebar calendar widget with this one and you will notice a significant boost in page load speed and switching from month to another month in the calendar.
Version: 1.0
Author: LatCom Systems
Author URI: http://www.latcomsystems.com/
License: GPLv2
Licence URI: http://www.gnu.org/licenses/gpl-2.0.html
Copyright 2019 LatCom Systems
*/

if (!defined('ABSPATH')) exit;

class LCS_EM_Widget_Calendar extends WP_Widget {
	
	var $defaults = [];
	
	function __construct() {
		$this->defaults = [
			'title' => __('LCS Events Calendar','lcs_em_widget_calendar'),
			'long_events' => 0,
			'category' => 0,
		];
		$widget_ops = array('description' => __( "Display your events in a calendar widget.", 'lcs_em_widget_calendar') );
		parent::__construct(false, $name = __('LCS Events Calendar', 'lcs_em_widget_calendar'), $widget_ops);	
	}
	
	public static function init() {
		return register_widget("LCS_EM_Widget_Calendar");
	}
	
	private static function add_calendar_filters() {
		add_filter('pre_option_dbem_categories_enabled', 'LCS_EM_Widget_Calendar::set_option');
		add_filter('pre_option_dbem_tags_enabled', 'LCS_EM_Widget_Calendar::set_option');
	}
	
	private static function remove_calendar_filters() {
		remove_filter('pre_option_dbem_categories_enabled', 'LCS_EM_Widget_Calendar::set_option');
		remove_filter('pre_option_dbem_tags_enabled', 'LCS_EM_Widget_Calendar::set_option');
	}
	
	public static function set_option() {
		return 0;
	}
	
	public static function calendar_init() {
		if (empty($_REQUEST['lcs_em_calendar_ajax'])) :
			return;
		endif;
		if (defined('DOING_AJAX') && DOING_AJAX ) :
			$_REQUEST['em_ajax'] = true;
		endif;
		
		if (!empty($_REQUEST['em_ajax']) || !empty($_REQUEST['em_ajax_action'])) :
			if (isset($_REQUEST['ajaxCalendar']) && $_REQUEST['ajaxCalendar']) :
				self::add_calendar_filters();
				if (class_exists('EM_Calendar')) :
					echo EM_Calendar::output($_REQUEST, false);
				else :
					echo '<p>Calendar widget error.  Check to see if Events Manager plugin is installed and active.</p>';
				endif;
				self::remove_calendar_filters();
				die();
			endif;
		endif;

	}

	function widget($args, $instance) {
		$instance = array_merge($this->defaults, $instance);

		echo $args['before_widget'];
		if (!empty($instance['title'])) :
			echo $args['before_title'];
			echo apply_filters('widget_title',$instance['title'], $instance, $this->id_base);
			echo $args['after_title'];
		endif;
		
		$instance['owner'] = false;
		
		if (!empty($_REQUEST['calendar_day'])) :
			$date = explode('-', $_REQUEST['calendar_day']);
			$instance['month'] = $date[1];
			$instance['year'] = $date[0];
		else:
			$instance['month'] = date("m");
			$instance['year'] = date('Y');
		endif;
		
		self::add_calendar_filters();
		ob_start();
		if (class_exists('EM_Calendar')) :
			echo EM_Calendar::output(apply_filters('em_widget_calendar_get_args',$instance));
		else :
			echo '<p>Calendar widget error.  Check to see if Events Manager plugin is installed and active.</p>';
		endif;
		$cal_html = ob_get_clean();
		$cal_html = str_replace('ajaxCalendar=1', 'ajaxCalendar=1&lcs_em_calendar_ajax=1', $cal_html);
		echo $cal_html;
		self::remove_calendar_filters();
		
		echo $args['after_widget'];
	}

	function update($new_instance, $old_instance) {
		foreach ($this->defaults as $key => $value) :
			if (!isset($new_instance[$key]) ) :
				$new_instance[$key] = $value;
			endif;
		endforeach;
		return $new_instance;
	}

	function form($instance) {
		$instance = array_merge($this->defaults, $instance);
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'lcs_em_widget_calendar'); ?>: </label>
			<input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($instance['title']); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('long_events'); ?>"><?php _e('Show Long Events?', 'events-manager'); ?>: </label>
			<input type="checkbox" id="<?php echo $this->get_field_id('long_events'); ?>" name="<?php echo $this->get_field_name('long_events'); ?>" value="1" <?php echo ($instance['long_events'] == '1') ? 'checked="checked"':''; ?>/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('category'); ?>"><?php _e('Category IDs','events-manager'); ?>: </label>
			<input type="text" id="<?php echo $this->get_field_id('category'); ?>" name="<?php echo $this->get_field_name('category'); ?>" size="3" value="<?php echo esc_attr($instance['category']); ?>" /><br />
			<em><?php _e('1,2,3 or 2 (0 = all)','events-manager'); ?> </em>
		</p>
		<?php 
	}
	
}

add_action('init', 'LCS_EM_Widget_Calendar::calendar_init', 9);
add_action('widgets_init', 'LCS_EM_Widget_Calendar::init');

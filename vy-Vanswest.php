<?php
/* Plugin Name: VY Vanswest API
    Description: use the VY API to get data
    Version: 0.1.0
    Author: Huaiqing Han
*/

//Unauthorised person cannot access to the file

defined('ABSPATH') or die('Unauthorised Access');
//action when login -- get the data from API
add_action('admin_menu', 'add_admin_menu_section');
function add_admin_menu_section(){
    add_menu_page(
        'VANSWEST API', 'API CSV', 'manage_options', 'vanswest_vy_api', 'vanswest_api_setting_page'
    );
}






////////////////////////////Cron Job Part\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
//start a cron job 
function cronJobStart(){
    if(!wp_next_scheduled('getJSON')){
        wp_schedule_event(time(),'daily','getJSON');
    }
}
add_action('wp','cronJobStart');
//deactivate the cron job
function cronJobEnd(){
    $timestamp = wp_next_scheduled('getJSON');
    wp_unschedule_event($timestamp,'getJSON');
}
register_deactivation_hook(__FILE__,'cronJobEnd');
//send the email to notice
function repeatFunction(){
    $email = 'korobeiniki@vanswest.com.au';
    $subject = 'This is your Cron job Auto Message';
    $message = 'API sent this message to you to check it works...';
    $success = mail($email,$subject,$message);
    if($success){
        echo 'mail sent';
    }else{
        echo 'error';
    }
}
add_action('getJSON', 'repeatFunction');
//add custom interval
function cron_add_daily($schedules){
    //add once every day
    $schedules['daily'] = array(
        'interval' => 10,
        'display' => __('Once per day')
    );
    return $schedules;
}
add_filter('cron_schedules','cron_add_daily');







///////////////////////////////////The Function Part\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
$fileink;
$json_data;
function getJSON(){
    $url = 'https://dealers.virtualyard.com.au/api/v2/get.php?a=vehicles&key=OvtapBIat1bGjrrY2v1GesK8w4odENFJ5zyFYbX2Uoy5c8pqXXABJjvko7vrT3Y2EGbLXUtWMN37DO7NalSkzzGvI';
    $argument =array(
        'method' => 'GET'
    );
    //get the response from VY
    $response = wp_remote_get($url, $argument); 

    //list the error when get error message
    if(is_wp_error($response)){
        $error_msg = $response -> get_error_message();
        echo"something went wrong: $error_msg";
    }
    global $json_data;
    //test the outcome, decode it into PHP object
    $json_data = json_decode(wp_remote_retrieve_body($response), true); 
    write_to_file();
};
//
function write_to_file(){

    global $file_link, $json_data;
    $file_link = WP_CONTENT_DIR . '/uploads/wpallimport/files/stocklist.csv';

        $fp = fopen($file_link,'w');
        foreach($json_data as $row){
             foreach($row as $car){
                fputcsv($fp, $car);        
            }       
        }
        //write the file       
        fclose($fp);
};

function downloadFile(){
    global $file_link;
    header("Content-type: application/x-file-to-save"); 
    header("Content-Disposition: attachment; filename=".basename($file_link));
    ob_end_clean();
    readfile($file_link);
    exit;
}
 function API_register_setting(){
     register_setting('api_update','api_update');
     add_settings_section('csv_update','CSV Update','title_text', 'vanswest_vy_api');
 }

add_action('admin_init', 'API_register_setting');
function vanswest_api_setting_page(){
    ?>
	<div class="wrap">
		<h2>Generate the Stocklist.csv in Host</h2>
	</div>
    <form action="" method="post">
        <?php
            settings_fields('api_update');
            do_settings_sections('vanswest_vy_api');
        ?>
        <input type="submit" name="submit" class='button button-primary' value='<?php getJSON(); echo'click me to update' ;?>'/>
    </form>
	<?php
}

function title_text(){
    echo '<p>You can update the CSV here</p>';
}

?>

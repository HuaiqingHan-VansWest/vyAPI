
<?php
/* Plugin Name: VY Vanswest API
    Description: Use the VY API to get data
    Version: 0.1.0
    Author: Huaiqing Han
*/

// Unauthorised person cannot access the file
defined('ABSPATH') or die('Unauthorised Access');
add_action('admin_init', 'vanswest_vy_api_start');
function vanswest_vy_api_start(){
    getJSON();
}
// function vanswest_api_add_six_hours_interval($schedules) {
//     $schedules['six_hours'] = array(
//         'interval' => 6 * HOUR_IN_SECONDS,
//         'display' => __('Every 6 Hours')
//     );
//     return $schedules;
// }
// // Register the six hours interval
// add_filter('cron_schedules', 'vanswest_api_add_six_hours_interval');

// //schedule cron job
// function api_activate(){
//     if(!wp_next_scheduled('')){
//         wp_schedule_event(time(),'six_hours', 'custom_everyday_event');
//     }
// }

// register_activation_hook(__FILE__,'api_activate');

// add_action('custom_everyday_event','custom_everyday_cronjob');

// function custom_everyday_cronjob(){
//     error_log(date('Y-m-d H:i:s',time()));
//     add_option('custom_cron_run_at', date('Y-m-d H:i:s',time()));
// };

// function custom_deactivation(){
//     wp_clear_scheduled_hook('custom_everyday_event');
// }
// register_deactivation_hook(__FILE__,'custom_deactivation');

// Action when login -- get the data from API
add_action('admin_menu', 'add_admin_menu_section');
function add_admin_menu_section(){
    add_menu_page(
        'VANSWEST API', 'API CSV', 'manage_options', 'vanswest_vy_api', 'vanswest_api_setting_page'
    );
}

function getJSON(){
    $url = 'https://dealers.virtualyard.com.au/api/v2/get.php?a=vehicles&key=OvtapBIat1bGjrrY2v1GesK8w4odENFJ5zyFYbX2Uoy5c8pqXXABJjvko7vrT3Y2EGbLXUtWMN37DO7NalSkzzGvI';
    $argument = array(
        'method' => 'GET'
    );
    // Get the response from VY
    $response = wp_remote_get($url, $argument); 

    // List the error when getting error message
    if (is_wp_error($response)) {
        $error_msg = $response->get_error_message();
        echo "Something went wrong: $error_msg";
    }

    $json_data = json_decode(wp_remote_retrieve_body($response), true);
    echo 'File is updated at ' . date('Y-m-d H:i:s', time());
    write_to_file($json_data);
}

function write_to_file($json_data) {
    $file_link = WP_CONTENT_DIR . '/uploads/wpallimport/files/stocklist.csv';
    $fp = fopen($file_link, 'w');
    foreach ($json_data as $row) {
        foreach ($row as $car) {
            fputcsv($fp, $car);
        }
    }
    fclose($fp);
}

function downloadFile() {
    $file_link = WP_CONTENT_DIR . '/uploads/wpallimport/files/stocklist.csv';
    header("Content-type: application/x-file-to-save"); 
    header("Content-Disposition: attachment; filename=".basename($file_link));
    ob_end_clean();
    readfile($file_link);
    exit;
}

function API_register_setting() {
    register_setting('api_update', 'api_update');
    add_settings_section('csv_update', 'CSV Update', 'title_text', 'vanswest_vy_api');
}

add_action('admin_init', 'API_register_setting');
function vanswest_api_setting_page() {
    if (isset($_POST['submit'])) {
        getJSON();
        echo 'CSV file updated successfully.';
    }
    ?>
    <div class="wrap">
        <h2>Generate the Stocklist.csv in Host</h2>
    </div>
    <form action="" method="post">
        <?php
        settings_fields('api_update');
        do_settings_sections('vanswest_vy_api');
        ?>
        <input type="submit" name="submit" class='button button-primary' value='Click me to update' />
    </form>
    <?php
}

function title_text() {
    echo '<p>You can update the CSV here</p>';
}


?>
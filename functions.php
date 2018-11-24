<?php 

add_action( 'wp_enqueue_scripts', 'salient_child_enqueue_styles');
function salient_child_enqueue_styles() {
	
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css', array('font-awesome'));

    if ( is_rtl() ) 
   		wp_enqueue_style(  'salient-rtl',  get_template_directory_uri(). '/rtl.css', array(), '1', 'screen' );
}

/**
 * Add endpoint for custom order-details page
 */
function my_custom_endpoints() {
 
    add_rewrite_endpoint( 'order-details', EP_ROOT | EP_PAGES );
 
}
add_action( 'init', 'my_custom_endpoints' );


function my_custom_query_vars( $vars ) {
    $vars[] = 'order-details';

    return $vars;
}
add_filter( 'query_vars', 'my_custom_query_vars', 0 );

function my_custom_flush_rewrite_rules() {
    flush_rewrite_rules();
}
add_action( 'wp_loaded', 'my_custom_flush_rewrite_rules' );

$endpoint = 'order-details';
add_action( 'woocommerce_account_' . $endpoint .  '_endpoint', 'wk_endpoint_content' );
 
function wk_endpoint_content() {
    wc_get_template( 'myaccount/order-details.php' );
}


/**
 * Add endpoint for customer-admin page
 */
function customer_admin_endpoints() {
 
    add_rewrite_endpoint( 'customer-admin', EP_ROOT | EP_PAGES );
 
}
add_action( 'init', 'customer_admin_endpoints' );


function customer_admin_query_vars( $vars ) {
    $vars[] = 'customer-admin';

    return $vars;
}
add_filter( 'query_vars', 'customer_admin_query_vars', 0 );

function customer_admin_flush_rewrite_rules() {
    flush_rewrite_rules();
}
add_action( 'wp_loaded', 'customer_admin_flush_rewrite_rules' );

$endpoint = 'customer-admin';
add_action( 'woocommerce_account_' . $endpoint .  '_endpoint', 'ca_endpoint_content' );
 
function ca_endpoint_content() {
    wc_get_template( 'myaccount/customer-admin.php' );
}

function custom_my_account_menu_items( $items ) {
    $user = wp_get_current_user();
    
    if( current_user_can('administrator') ) { 
        $items = array(
            'dashboard'         => __( 'Dashboard', 'woocommerce' ),
            'orders'            => __( 'Orders', 'woocommerce' ),
            'downloads'       => __( 'Downloads', 'woocommerce' ),
            'edit-address'    => __( 'Addresses', 'woocommerce' ),
            'payment-methods' => __( 'Payment Methods', 'woocommerce' ),
            'edit-account'      => __( 'Edit Account', 'woocommerce' ),
            'customer-admin'      => 'Customer Admin',
            'customer-logout'   => __( 'Logout', 'woocommerce' ),
        );
    }
    else {
        $items = array(
            'dashboard'         => __( 'Dashboard', 'woocommerce' ),
            'orders'            => __( 'Orders', 'woocommerce' ),
            'downloads'       => __( 'Downloads', 'woocommerce' ),
            'edit-address'    => __( 'Addresses', 'woocommerce' ),
            'payment-methods' => __( 'Payment Methods', 'woocommerce' ),
            'edit-account'      => __( 'Edit Account', 'woocommerce' ),
            'customer-logout'   => __( 'Logout', 'woocommerce' ),
        );        
    }
    return $items;
}
add_filter( 'woocommerce_account_menu_items', 'custom_my_account_menu_items' );


// Begin functions for orders db update process
add_action( 'TechnicoteOrdersFileUpdate', 'UpdateOrderDB' );
function UpdateOrderDB() {
            mailStatus("order update process started");

	//$dir = dirname(__FILE__);
	
	// Call the database connection settings
	require( "/nas/content/live/technicotedev/wp-config.php" );

	$input_file = "/nas/content/live/technicotedev/uploads/Technicote_orders.txt";
	
	if (!file_exists($input_file)) {
		mailStatus('No input file found'); 
        error_log("ORDER PROC UPDATE ERROR: No input file found", 0);
		return;
	}

	// Connect to database
	$con = connectToDB(DB_HOST, DB_USER, DB_PASSWORD);

	if (!$con)
		return;

	// Delete old data prior to load of new data
	if (!deleteStagingData($con))
		return;

	// Load input file into tc_order_stage and tc_order_detail_stage tables
	if (!loadNewDataFromCSV($con, $input_file))
		return;

	// Delete old data from prod tables
	if (!deleteProdData($con))
		return;

	// Move data from stage table to prod tables
	if (!stagingToProd($con))
		return;
	
	//Delete old saved file prior to rename of current file to -old
	if (!unlink("/nas/content/live/technicotedev/uploads/Technicote_orders-old.txt"))
			    wp_mail( 'sstromick@gmail.com', 'test', 'delete failed' );

	//rename current file to -old
	rename("/nas/content/live/technicotedev/uploads/Technicote_orders.txt", "/nas/content/live/technicotedev/uploads/Technicote_orders-old.txt");
	
}

// Connect to database
function connectToDB($hostname, $username, $password) 
{
    try {
        $con = new PDO("mysql:host=$hostname;dbname=wp_technicotedev", $username, $password);
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $con->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    } 

    catch (PDOException $e) {
        mailStatus("ERROR: Connection failed: " . $e->getMessage());
        error_log("ORDER PROC UPDATE ERROR: Connection failed: " . $e->getMessage(), 0);
    }
    
    return $con;
}

// Deletes old data from tc_order_stage and tc_order_detail_stage tables
function deleteStagingData($con)
{
    //Start our transaction.
    $con->beginTransaction();

    try {
        $sql = $con->prepare("DELETE FROM tc_order_detail_stage");
        $sql->execute();
        $con->commit();
    }
    catch (PDOException $e) {
        mailStatus("ERROR: Delete from tc_order_detail_stage failed: " . $e->getMessage()); 
        error_log("ORDER PROC UPDATE ERROR: Delete from tc_order_detail_stage failed: " . $e->getMessage(), 0);
        $con->rollBack();
        return false;
    }

    //Start our transaction.
    $con->beginTransaction();

    try {
        $sql = $con->prepare("DELETE FROM tc_order_stage");
        $sql->execute();
        $con->commit();
    }
    catch (PDOException $e) {
        mailStatus("ERROR: Delete from tc_order_stage failed: " . $e->getMessage()); 
        error_log("ORDER PROC UPDATE ERROR: Delete from tc_order_stage failed: " . $e->getMessage(), 0);
        $con->rollBack();
        return false;
    }
    
    return true;
}

// Deletes old prod data from tc_order and tc_order_detail tables
function deleteProdData($con)
{
    //Start our transaction.
    $con->beginTransaction();

    try {
        $sql = $con->prepare("DELETE FROM tc_order_detail");
        $sql->execute();
        $con->commit();
    }
    catch (PDOException $e) {
        mailStatus("ERROR: Delete from tc_order_detail failed: " . $e->getMessage()); 
        error_log("ORDER PROC UPDATE ERROR: Delete from tc_order_stage failed: " . $e->getMessage(), 0);
        $con->rollBack();
        return false;
    }

    //Start our transaction.
    $con->beginTransaction();

    try {
        $sql = $con->prepare("DELETE FROM tc_order");
        $sql->execute();
        $con->commit();
    }
    catch (PDOException $e) {
        mailStatus("ERROR: Delete from tc_order failed: " . $e->getMessage()); 
        error_log("ORDER PROC UPDATE ERROR: Delete from tc_order failed: " . $e->getMessage(), 0);
        $con->rollBack();
        return false;
    }
    
    return true;
}

// Move data from staging to prod tables
function stagingToProd($con)
{
    try {
        $sql = $con->prepare("INSERT INTO tc_order SELECT * FROM tc_order_stage");
        $sql->execute();
    }
    catch (PDOException $e) {
        mailStatus("ERROR: Failed moving data from tc_order_stage to tc_order: " . $e->getMessage()); 
        error_log("ORDER PROC UPDATE ERROR: Failed moving data from tc_order_stage to tc_order: " . $e->getMessage(), 0);
        return false;
    }
    
    try {
        $sql = $con->prepare("INSERT INTO tc_order_detail SELECT * FROM tc_order_detail_stage");
        $sql->execute();
    }
    catch (PDOException $e) {
        mailStatus("ERROR: Failed moving data from tc_order_detail_stage to tc_order_detail: " . $e->getMessage()); 
        error_log("ORDER PROC UPDATE ERROR: Failed moving data from tc_order_detail_stage to tc_order_detail: " . $e->getMessage(), 0);
        return false;
    }
    
    return true;
}

// Populate tc_order_stage and tc_order_detail_stage from input CSV file
function loadNewDataFromCSV($con, $input_file)
{
    global $recCount;
    global $orderCount;
    global $lineCount;
    
    $recCount = 0;
    $orderCount = 0;
    $lineCount = 0;
    
    $all_data = csvToArray($input_file);
    $prev_order_num = '          ';

    foreach ($all_data as $data) {
        
        if ($prev_order_num != $data['co_num']) {
            $prev_order_num = $data['co_num'];
                
            try {
                $sql = $con->prepare("INSERT INTO tc_order_stage (order_number, customer_number, customer_name, customer_city, customer_state, customer_country, purchase_order) 
                VALUES (:order_number, :customer_number, :name, :city, :state, :country, :po)");

                $sql->bindParam(':order_number', $data['co_num']);
                $sql->bindParam(':customer_number', $data['cust_num']);
                $sql->bindParam(':name', $data['name']);
                $sql->bindParam(':city', $data['city']);
                $sql->bindParam(':state', $data['state']);
                $sql->bindParam(':country', $data['country']);
                $sql->bindParam(':po', $data['cust_po']);
                $sql->execute();
                $last_inser_order_id = $con->lastInsertId();
            }
            catch (PDOException $e) {
                mailStatus("ERROR: Insert to tc_order_stage failed for order# " . $data['co_num'] . $e->getMessage());
                error_log("ORDER PROC UPDATE ERROR: Insert to tc_order_stage failed for order# " . $data['co_num'] . $e->getMessage(), 0);
                return false;
            }

        }
        
        try {
            $sql = $con->prepare("INSERT INTO tc_order_detail_stage (order_id, line_number, item_number, quantity, width, unit_of_measure, order_date, request_date, promise_date, ship_date, status) 
            VALUES (:order_id, :line_number, :item_number, :quantity, :width, :unit_of_measure, :order_date, :request_date, :promise_date, :ship_date, :status)");
            
            $sql->bindParam(':order_id', $last_inser_order_id);
            $sql->bindParam(':line_number', $data['co_line']);
            $sql->bindParam(':item_number', $data['item']);
            $sql->bindParam(':quantity', $data['qty']);
            $sql->bindParam(':width', $data['width']);
            $sql->bindParam(':unit_of_measure', $data['u_m']);
            $sql->bindParam(':order_date', date("Y/m/d", strtotime($data['order_date'])));
            $sql->bindParam(':request_date', date("Y/m/d", strtotime($data['request_date'])));
            $sql->bindParam(':promise_date', date("Y/m/d", strtotime($data['promise_date'])));
            $sql->bindParam(':ship_date', date("Y/m/d", strtotime($data['ship_date'])));
            $sql->bindParam(':status', $data['stat']);
            $sql->execute();
        }
        catch (PDOException $e) {
            mailStatus("ERROR: Insert to tc_order_detail_stage failed for order# " . $data['co_num'] . "line number " . $data['co_line'] . $e->getMessage());
            error_log("ORDER PROC UPDATE ERROR: Insert to tc_order_detail_stage failed for order# " . $data['co_num'] . "line number " . $data['co_line'] . $e->getMessage(), 0);
            return false;
        }

    }

    return true;
}

// Create  CSV to Array function
function csvToArray($filename = '', $delimiter = '|')
{
    if (!file_exists($filename) || !is_readable($filename)) {
        return false;
    }

    $header = NULL;
    $result = array();
    if (($handle = fopen($filename, 'r')) !== FALSE) {
        while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
            if (!$header)
                $header = $row;
            else
                $result[] = array_combine($header, $row);
        }
        fclose($handle);
    }

    return $result;
}

// Mail Errors from Orders Update to Admin
function mailStatus($msg) 
{
    wp_mail( 'sstromick@gmail.com', 'Orders DB Update Failed', $msg );
}



// End functions for orders db update process
// Capture ref URL on quote form
add_filter( 'gform_field_value_refurl', 'populate_referral_url');
 
function populate_referral_url( $form ){
    // Grab URL from HTTP Server Var and put it into a variable
    $refurl = $_SERVER['HTTP_REFERER'];
 
    // Return that value to the form
    return esc_url_raw($refurl);
}
// add SKU & extra permalink to cat pages
add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_after_shop_loop_item_sku_in_cart', 20, 1);
function woocommerce_after_shop_loop_item_sku_in_cart( $template )  {
	global $product;
	$sku = $product->get_sku();
	$link = apply_filters( 'woocommerce_loop_product_link', get_the_permalink(), $product );
	echo '<p class="sku_title">ITEM: '.$sku.'</p>';
	echo '<a href="' . esc_url( $link ) . '" class="see_more">See More Details ></a>';
}
// Redirect Registration Page
function registration_page_redirect()
{
 global $pagenow;
 
 if ( ( strtolower($pagenow) == 'wp-login.php') && ( strtolower( $_GET['action']) == 'register' ) ) {
 wp_redirect( home_url('/registration'));
 }
}
 
add_filter( 'init', 'registration_page_redirect' );

?>
<?php
/**
 * Orders
 *
 * Shows orders on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/orders.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$error_msg = null;

$current_user = wp_get_current_user();

$items_per_page = 25;
$page = isset( $_GET['cpage'] ) ? abs( (int) $_GET['cpage'] ) : 1;
$offset = ( $page * $items_per_page ) - $items_per_page;
$prev_page = $page - 1;
$next_page = $page + 1;

$query = $wpdb->prepare("SELECT a.order_id, a.order_number, customer_number, purchase_order, line_number, status, order_date, request_date, promise_date, ship_date FROM tc_order a, tc_order_detail b WHERE a.order_id=b.order_id AND customer_number = %s", $current_user->user_login);

/* keep if they ever want to have sub-accounts
$query = $wpdb->prepare("SELECT a.order_id, a.order_number, customer_number, purchase_order, line_number, status, order_date, request_date, promise_date, ship_date FROM tc_order a, tc_order_detail b WHERE a.order_id=b.order_id AND customer_number = %s OR customer_number in (select can_access_customer from tc_user_groups where customer_number = %s)", $current_user->user_login, $current_user->user_login );
*/

$total_query = "SELECT COUNT(1) FROM (${query}) AS combined_table";
$total = $wpdb->get_var( $total_query );

$max_pages = ceil($total / $items_per_page);

$orders = $wpdb->get_results( $query.' LIMIT '. $items_per_page . ' OFFSET ' . $offset , ARRAY_A );

if ($wpdb->last_error) {
    error_log("ERROR: An error has occured on orders.php" . $wpdb->last_error, 0);

    $error_msg = "ERROR: An error has occured, please contact support.";
}
?>

<h2>My Orders</h2>

<?php 
if ($error_msg) {
?>
    <div class="errMsg">
        <p><?php echo $error_msg ?></p>
    </div>
<?php 
} 
?>

<?php if ( $orders ) { ?>

	<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
		<thead>
			<tr>
			    <th>Customer Id</th>
			    <th>Order Number</th>
			    <th>Purchase Order</th>
			    <th>Line</th>
			    <th>Status</th>
			    <th>Order Date</th>
			    <th>Request Date</th>
			    <th>Promise Date</th>
			    <th>Ship Date</th>
			</tr>
		</thead>
        <tbody>
<?php 
$row_class = "woocommerce-orders-table__row woocommerce-orders-table__row--status- order";
$cell_class = "woocommerce-orders-table__cell woocommerce-orders-table__cell";
$prev_order_id = 0;           
            
foreach ( $orders as $order ) {
switch ($order[status]) {
    case 'C';
        $status_desc = "Complete";
        break;
    case 'O':
        $status_desc = "Ordered";
        break;
    case 'S':
        $status_desc = "Scheduled";
        break;
    case 'I':
        $status_desc = "In-Process";
        break;
    default:
        $status_desc = $order[status];
}
    
if ($order[order_id] == $prev_order_id) {
?>
            <tr class="<?php echo $row_class ?>">
                <td class="<?php echo $cell_class ?> hidecell"></td>
                <td class="<?php echo $cell_class ?> hidecell"></td>
                <td class="<?php echo $cell_class ?> hidecell"></td>
                <td class="<?php echo $cell_class ?>" data-title="Line"><?php echo $order[line_number]; ?></td>
                <td class="<?php echo $cell_class ?>" data-title="Status"><?php echo $status_desc; ?></td>
                <td class="<?php echo $cell_class ?>" data-title="Order Date"><?php echo date("m/d/Y", strtotime($order[order_date])); ?></td>
                <td class="<?php echo $cell_class ?>" data-title="Request Date"><?php echo date("m/d/Y", strtotime($order[request_date])); ?></td>
                <td class="<?php echo $cell_class ?>" data-title="Promise Date"><?php echo date("m/d/Y", strtotime($order[promise_date])); ?></td>
                <td class="<?php echo $cell_class ?>" data-title="Ship Date"><?php echo date("m/d/Y", strtotime($order[ship_date])); ?></td>
            </tr>
            
<?php    
} // end if for order id's are equal
else {
    $prev_order_id = $order[order_id];
?>
            <tr class="<?php echo $row_class ?> new-customer">
                <td class="<?php echo $cell_class ?>" data-title="Customer ID"><?php echo $order[customer_number]; ?></td>
                <td class="<?php echo $cell_class ?>" data-title="Order Number"><?php echo $order[order_number]; ?></td>
                <td class="<?php echo $cell_class ?>" data-title="Purchase Order"><a href="http://www.technicote.com/my-account/order-details?id=<?php echo $order[order_id]; ?>"><?php echo $order[purchase_order]; ?></a></td>
                <td class="<?php echo $cell_class ?> hidecell"></td>
                <td class="<?php echo $cell_class ?> hidecell"></td>
                <td class="<?php echo $cell_class ?> hidecell"></td>
                <td class="<?php echo $cell_class ?> hidecell"></td>
                <td class="<?php echo $cell_class ?> hidecell"></td>
                <td class="<?php echo $cell_class ?> hidecell"></td>
            </tr>
            
            <tr class="<?php echo $row_class ?>">
                <td class="<?php echo $cell_class ?> hidecell"></td>
                <td class="<?php echo $cell_class ?> hidecell"></td>
                <td class="<?php echo $cell_class ?> hidecell"></td>
                <td class="<?php echo $cell_class ?>" data-title="Line"><?php echo $order[line_number]; ?></td>
                <td class="<?php echo $cell_class ?>" data-title="Status"><?php echo $status_desc; ?></td>
                <td class="<?php echo $cell_class ?>" data-title="Order Date"><?php echo date("m/d/Y", strtotime($order[order_date])); ?></td>
                <td class="<?php echo $cell_class ?>" data-title="Request Date"><?php echo date("m/d/Y", strtotime($order[request_date])); ?></td>
                <td class="<?php echo $cell_class ?>" data-title="Promise Date"><?php echo date("m/d/Y", strtotime($order[promise_date])); ?></td>
                <td class="<?php echo $cell_class ?>" data-title="Ship Date"><?php echo date("m/d/Y", strtotime($order[ship_date])); ?></td>
            </tr>
<?php 
} // end if for order_id's not equal
} // end foreach
?>
        </tbody>
	</table>

<?php 
global $wp;
$prevURL = home_url($wp->request) . "?cpage=" . $prev_page;
$nextURL = home_url($wp->request) . "?cpage=" . $next_page;

if ($page > 1 || $page < $max_pages) {
?>

<div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
   <?php if ($page > 1) { ?>
    <a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button" href="<?php echo $prevURL; ?>">Previous</a>
    <?php } 
    if ($page < $max_pages) {
    ?>
    <a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button" href="<?php echo $nextURL; ?>">Next</a>
    <?php } ?>
</div>
<?php 
}// end if for pagination div
?>
				
<?php
}// end if for orders

else {
?>
<p>No Orders Found</p>
<?php 
}
?>


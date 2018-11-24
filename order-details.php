<?php
/**
 * View Order
 *
 * Shows the details of a particular order on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/view-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 3.0.0
 */

$error_msg = null;
$id = null;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (isset( $_GET['id'] )) {
    $id = $_GET['id'];
}

global $wpdb;
$current_user = wp_get_current_user();

$orders = $wpdb->get_results( $wpdb->prepare("SELECT a.order_id, a.order_number, customer_number, purchase_order, customer_name, customer_city, customer_state, customer_country, line_number, item_number, quantity, width, unit_of_measure, status, order_date, request_date, promise_date, ship_date FROM tc_order a, tc_order_detail b WHERE a.order_id=b.order_id AND a.order_id = %d AND customer_number = %s", $id, $current_user->user_login), ARRAY_A );

if ($wpdb->last_error) {
    error_log("ERROR: An error has occured on orders-details.php" . $wpdb->last_error, 0);

    $error_msg = "ERROR: An error has occured, please contact support.";
}

if ($error_msg) {
?>
    <div class="errMsg">
        <p><?php echo $error_msg ?></p>
    </div>
<?php 
} 


if ( $orders ) { 
?>

    <section class="woocommerce-order-details">
        <h2 class="woocommerce-order-details__title">Order Details</h2>
        
<?php 
$loop_counter = 1;
foreach ( $orders as $order ) {
   if ($loop_counter == 1) {
?>

        <table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
            <thead>
                <th colspan="2">General Order</th>
            </thead>
            <tbody>
                <tr>
                    <td>Order Number:</td>
                    <td><?php echo $order[order_number] ?></td>
                </tr>
                <tr>
                    <td>Purchase Order:</td>
                    <td><?php echo $order[purchase_order] ?></td>
                </tr>
                <tr>
                    <td>Customer:</td>
                    <td>
                        <?php echo $order[customer_number] ?><br />
                        <?php echo $order[customer_name] ?><br />
                        <?php echo $order[customer_city] . ', ' . $order[customer_state] ?><br />
                        <?php echo $order[customer_country] ?><br />
                    </td>
                </tr>
            </tbody>
        </table>

        <h2 class="woocommerce-order-details__title">Sub-Orders</h2>
<?php
       $loop_counter++;
} //end if for loop_counter==1
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

?>
        <table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
            <thead>
                <th colspan="2">Order <?php echo $order[order_number] . '-' . $order[line_number] ?> is currently <?php echo $status_desc ?></th>
            </thead>
            <tbody>
                <tr>
                    <td>Item Number:</td>
                    <td><?php echo $order[item_number] ?></td>
                </tr>
                <tr>
                    <td>Quantity:</td>
                    <td><?php echo $order[quantity] ?></td>
                </tr>
                <tr>
                    <td>Width:</td>
                    <td><?php echo $order[width] ?></td>
                </tr>
                <tr>
                    <td>Unit of Measure:</td>
                    <td><?php echo $order[unit_of_measure] ?></td>
                </tr>
                <tr>
                    <td>Order Date:</td>
                    <td><?php echo date("m/d/Y", strtotime($order[order_date])) ?></td>
                </tr>
                <tr>
                    <td>Request Date:</td>
                    <td><?php echo date("m/d/Y", strtotime($order[request_date])) ?></td>
                </tr>
                <tr>
                    <td>Promise Date:</td>
                    <td><?php echo date("m/d/Y", strtotime($order[promise_date])) ?></td>
                </tr>
                <tr>
                    <td>Ship Date:</td>
                    <td><?php echo date("m/d/Y", strtotime($order[ship_date])) ?></td>
                </tr>
            </tbody>
        </table>
<?php 
} //end for loop
?>
    </section>


<?php 
} // end if for have orders
else {
    echo "<h2>No Orders Found</h2>";
}
?>
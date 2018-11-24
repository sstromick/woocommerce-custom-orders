<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$error_msg = null;

//user issued delete action
if (isset($_GET['id1']) || isset($_GET['id2'])) {
    $id1 = null;
    $id2 = null;
    
    if ($_GET['id1'])
        $id1 = $_GET['id1'];
    
    if ($_GET['id2'])
        $id2 = $_GET['id2']; 

    $wpdb->delete('tc_user_groups', array('customer_number' => $id1, 'can_access_customer' => $id2), array('%s', '%s'));

    if ($wpdb->last_error) {
        $error_msg = "ERROR: An error has occured, please contact support.";
    }

    $customers = $wpdb->get_results($wpdb->prepare("SELECT customer_number, can_access_customer FROM tc_user_groups WHERE customer_number = %s", $id1), ARRAY_A);

    if ($wpdb->last_error) {
        $error_msg = "ERROR: An error has occured, please contact support.";
    }
}

else
if (isset($_POST['submit'])) {
    $action = $_POST['submit'];
    $customers = null;
    $master_customer = null;
    $linked_customer = null;

    if ($action === "Submit") { 
        $customer_id = $_POST['customer-id'];

        if ($customer_id == "" || empty($customer_id)) {
            $error_msg = "ERROR: Customer id field is required";
        }
        else {
            $customers = $wpdb->get_results($wpdb->prepare("SELECT customer_number, can_access_customer FROM tc_user_groups WHERE customer_number = %s", $customer_id), ARRAY_A);

            if ($wpdb->last_error) {
                $error_msg = "ERROR: An error has occured, please contact support.";
            }
        }
    }
    else if ($action === "Add") {
        $master_customer = $_POST['master-customer'];
        $linked_customer = $_POST['linked-customer'];
        
        if ($linked_customer == "" || empty($linked_customer)) {
            $error_msg = "ERROR: Linked Customer field is required";
        }
        else {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->users WHERE user_login = %s", $linked_customer));

            if ($count == 0) {
                $error_msg = "ERROR: Linked customer must be a registered user";
            }
            else {
                $wpdb->insert('tc_user_groups', array('customer_number' => $master_customer, 'can_access_customer' => $linked_customer), array('%s', '%s'));

                if ($wpdb->last_error) {
                    $error_msg = "ERROR: An error has occured, please contact support.";
                }
            }
        }
        
        $customers = $wpdb->get_results($wpdb->prepare("SELECT customer_number, can_access_customer FROM tc_user_groups WHERE customer_number = %s", $master_customer), ARRAY_A);
    }
}


?>
<h2>Customer Admin</h2>

<div id="section-user-groups">
<?php if ($error_msg) {
?>
    <div class="errMsg">
        <p><?php echo $error_msg ?></p>
    </div>
<?php }
?>
    <form action="customer-admin.php" method="post">
        <div>
            <label for="customer-id">Customer ID</label>
            <input type="text" name="customer-id" >
        </div>
        <div>
            <input type="submit" name="submit" value="Submit">
        </div>
    </form>			        

	<table >
		<thead>
			<tr>
			    <th>Customer ID</th>
			    <th>Linked Customers</th>
			    <th>Action</th>
			</tr>
		</thead>
        <tbody>
<?php 
if ($customers) {            
foreach ( $customers as $customer ) {

?>
            <tr>
                <td><?php echo $customer['customer_number']; ?></td>
                <td><?php echo $customer['can_access_customer']; ?></td>
                <td><a href="customer-admin.php?id1=<?php echo $customer['customer_number']; ?>&id2=<?php echo $customer['can_access_customer']; ?>">unlink</a></td>
            </tr>
            
<?php 
} // end foreach
} // end if customers
else {
?>
            <tr>
                <td colspan="3"><em>Please enter a customer id above.</em></td>
            </tr>
<?php 
} // end else - no customers
?>
        </tbody>
<?php 
if ($customers) {            
?>
        <tfoot>
            <tr>
                <td><?php echo $customer['customer_number']; ?></td>
                <td colspan="2">
                    <form class="add-customer" action="customer-admin.php" method="post">
                        <div>
                            <input type="hidden" name="master-customer" value="<?php echo $customer['customer_number']; ?>">
                            <input type="text" name="linked-customer" placeholder="Enter a customer id..." >
                        </div>
                        <div>
                            <input type="submit" name="submit" value="Add">
                        </div>
                    </form>
                </td>
            </tr>
        </tfoot>
<?php 
} // end else - have customers for footer row
?>
	</table>
</div>

<?php

/**
 * woocommerce_order_status.php
 *
 * WordPress view file for woocommerce order status admin info box.
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Sdk\Webhook
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.8
 * @since      File available since initial Release.
 * @deprecated -
 */

use BBMSL\Sdk\BBMSL;
use BBMSL\Sdk\Utility;
use BBMSL\Sdk\Setup;
use BBMSL\Sdk\WordPress;

$order_id = intval( $order_id );
?>
<div class="postbox bbmsl-status bbmsl-bg">
	<button type="submit" name="save" value="Update" class="default-failover" id="save"></button>
	<div class="header">
		<img src="<?php echo BBMSL::getLogoURL(); ?>" />
	</div>
	<?php if( !BBMSL::matchOrderingMode( $order_id ) ) { ?>
		<div class="body">
			<p><?php echo __( 'Unable to read the order details if current environment setting is not aligned with this order\'s. Please switch the testing mode if you need to view this order details' ); ?></p>
			<p><a class="bbmsl-btn" href="<?php echo Setup::setupLink(); ?>"><?php echo __( 'Change Mode', 'bbmsl-gateway' ); ?></a></p>
		</div>
	<?php } else if( isset( $order_info ) && is_array( $order_info ) && sizeof( $order_info ) > 0 ) { ?>
		<div class="body">
			<?php if( isset( $order_status ) ) { ?>
				<div class="status <?php echo esc_attr( strtolower( $order_status ) ); ?>"><?php echo esc_attr( strtoupper( $order_status ) ); ?></div>
				<?php if( $order_status == 'OPEN' ) { ?>
					<?php if( isset( $metadata[ 'checkoutUrl' ] ) ) { ?>
						<p><?php echo __( 'Order has not been paid, client may checkout at the following link:', 'bbmsl-gateway' ); ?></p>
						<p><a href="<?php echo esc_url( $metadata[ 'checkoutUrl' ] ); ?>" target="_blank" rel="noreferrer noopener"><?php echo esc_attr( $metadata[ 'checkoutUrl' ] ); ?></a></p>
						
						<?php if( false) { // void function here ?>
						<p>
							<a href="<?php echo esc_url( $metadata[ 'checkoutUrl' ] ); ?>" target="_blank" rel="noreferrer noopener" class="bbmsl-btn"><?php echo __( 'Checkout', 'bbmsl-gateway' ); ?></a>
							<a href="javascript:alert( 'Testing' )" class="bbmsl-btn"><?php echo __( 'Void', 'bbmsl-gateway' ); ?></a>
						</p>
						<?php } ?>

					<?php } else { ?>
						<p><?php echo __( 'Checkout URL is not available.', 'bbmsl-gateway' ); ?></p>
					<?php } ?>
				<?php } ?>
			<?php } ?>
		</div>
		<table class="table" cellspacing="0">
			<?php if( isset( $order_info[ 'id' ] ) ) { ?>
			<tr>
				<th><?php echo __( 'Order ID', 'bbmsl-gateway' ); ?></th>
				<td><?php echo esc_html( $order_info[ 'id' ] ); ?></td>
			</tr>
			<?php } ?>
			<?php if( isset( $order_info[ 'merchantReference' ] ) ) { ?>
			<tr>
				<th><?php echo __( 'Merchant Reference', 'bbmsl-gateway' ); ?></th>
				<td><?php echo esc_html( $order_info[ 'merchantReference' ] ); ?></td>
			</tr>
			<?php } ?>
			<?php if( isset( $order_info[ 'currency' ] ) && isset( $order_info[ 'amount' ] ) ) { ?>
			<tr>
				<th><?php echo __( 'Amount', 'bbmsl-gateway' ); ?></th>
				<td><?php echo esc_html( sprintf( '%s %s-', $order_info[ 'currency' ], number_format( doubleval( $order_info[ 'amount' ] ), 2, '.', ',' ) ) ); ?></td>
			</tr>
			<?php } ?>
			<?php if( isset( $order_info[ 'createTime' ] ) ) { ?>
			<tr>
				<th><?php echo __( 'Created at', 'bbmsl-gateway' ); ?></th>
				<td><?php echo Utility::dateFromTimezone( $order_info[ 'createTime' ], 'UTC', wp_timezone_string() ); ?></td>
			</tr>
			<?php } ?>
			<?php if( isset( $order_info[ 'updateTime' ] ) ) { ?>
			<tr>
				<th><?php echo __( 'Last updated at', 'bbmsl-gateway' ); ?></th>
				<td><?php echo Utility::dateFromTimezone( $order_info[ 'updateTime' ], 'UTC', wp_timezone_string() ); ?></td>
			</tr>
			<?php } ?>
			<?php if( isset( $order_info[ 'recurring' ] ) ) { ?>
			<tr>
				<th><?php echo __( 'Is Recurring?', 'bbmsl-gateway' ); ?></th>
				<td>
					<?php if( boolval( $order_info[ 'recurring' ] ) ) {
						echo __( 'Yes', 'bbmsl-gateway' );
					}else{
						echo __( 'No', 'bbmsl-gateway' );
					} ?>
				</td>
			</tr>
			<?php } ?>
		</table>
		<div class="body">
			<p>
				<a href="<?php echo esc_html( $portal_link ); ?>" target="_blank" rel="noreferrer noopener" class="bbmsl-btn"><?php echo __( 'Portal Login', 'bbmsl-gateway' ); ?></a>
				<?php if( $order_status == 'SUCCESS' && isset( $order_info[ 'merchantReference' ] ) ) { ?>
				<?php wp_nonce_field( 'bbmsl-plugin', '_bbmsl_nonce' ); ?>
				<input type="hidden" name="<?php echo BBMSL::POSTED_KEY; ?>[order_reference]" value="<?php echo esc_attr( $order_info[ 'merchantReference' ] ); ?>" />
				<button type="submit" name="<?php echo BBMSL::POSTED_KEY; ?>[bbmsl_action]" value="void_order" class="bbmsl-btn"><?php echo __( 'Void', 'bbmsl-gateway' ); ?></button>
				<?php } ?>
			</p>
		</div>
	<?php } else { ?>
		<div class="body">
			<p><?php echo __( 'No valid public key.' ); ?></p>
			<?php if( isset( $error ) ) { ?>
			<p><?php echo esc_html( $error ); ?></p>
			<?php } ?>
		</div>
	<?php } ?>
</div>

<style>
#save{width:0;height:0;display:block;border:0;background:transparent;padding:0;}
<?php if( !boolval( Utility::checkBoolean( WordPress::get_option( BBMSL::PARAM_GATEWAY_REFUND, 0) ) ) || ( isset( $order_status ) && in_array( $order_status, array( 'OPEN', 'SUCCESS', 'REFUNDED' ), true ) ) ) { ?>
.refund-actions button.do-api-refund{display:none!important;opacity:0;width:0;height:0;overflow:hidden;pointer-events:none;}
<?php } ?>
<?php if( isset( $order_status ) && in_array( $order_status, array( 'OPEN', 'SUCCESS', 'REFUNDED' ), true) ) { ?>
.refund-actions:after{display:block;background:#fff9c4;border:1px solid #CCC;width:100%;text-align:left;margin-top:14px;padding:8px;box-sizing:border-box;}
<?php if( in_array( $order_status, array( 'OPEN', 'SUCCESS' ), true) ) { ?>
.refund-actions:after{content:'<?php echo __( 'Online refund is available after settlement.', 'bbmsl-gateway' ); ?>';
<?php } ?>
<?php if( 'REFUNDED' == $order_status ) { ?>
.refund-actions:after{content:'<?php echo __( 'This order has already been refunded once.', 'bbmsl-gateway' ); ?>';
<?php } ?>
<?php } ?>
</style>
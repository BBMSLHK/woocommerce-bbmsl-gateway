<?php

/**
 * woocommerce_payments_settings.php
 *
 * WordPress view file for plugin settings page.
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
use BBMSL\Sdk\BBMSL_SDK;
use BBMSL\Sdk\Setup;
use BBMSL\Sdk\Utility;
use BBMSL\Sdk\WordPress;

$reference_endpoint = BBMSL::newApiCallInstance();
$testing_mode = boolval( $reference_endpoint && Utility::checkBoolean( $reference_endpoint->getModeCode() == BBMSL_SDK::MODE_TESTING) );
?>
<div class="woocommmerce-settings bbmsl-settings bbmsl-bg">
	<?php wp_nonce_field( 'bbmsl-plugin', '_bbmsl_nonce' ); ?>
	<input type="hidden" name="ui_last_pane_state" value="merchant-settings" readonly />
	<button type="submit" name="save" value="Save changes" class="default-failover" id="save"></button>
	<div class="header">
		<img src="<?php echo BBMSL::getLogoURL(); ?>" />
		<label for="mobile-menu" class="btn-mobile-menu">
			<span class="dashicons dashicons-menu"></span>
		</label>
	</div>
	<input type="checkbox" id="mobile-menu" /> 
	<div class="body">
		<div class="settings">
			<menu>
				<label class="menu-item active" data-pane="merchant-settings">
					<span class="dashicons dashicons-admin-generic"></span>
					<?php echo __( 'Merchant Settings', 'bbmsl-gateway' ); ?>
				</label>
				<label class="menu-item" data-pane="content-settings">
					<span class="dashicons dashicons-admin-generic"></span>
					<?php echo __( 'Content Settings', 'bbmsl-gateway' ); ?>
				</label>
				<?php if(false){ ?>
				<label class="menu-item" data-pane="other-settings">
					<span class="dashicons dashicons-admin-generic"></span>
					<?php echo __( 'Other Settings', 'bbmsl-gateway' ); ?>
				</label>
				<?php } ?>
			</menu>

			<div class="pane" id="merchant-settings">
				<div class="row mb-3">
					<div class="col-12">
						<div class="display-box">
							<?php echo __( 'Remember to save change for update new setting.', 'bbmsl-gateway' ); ?>
						</div>
						<h1 class="heading"><?php echo __( 'Merchant Setting', 'bbmsl-gateway' ); ?></h2>
						<div class="input-group">
							<h2 class="heading"><?php echo __( 'Testing Mode', 'bbmsl-gateway' ); ?></h2>
							<div class="input-box">
								<label class="switch">
									<input type="hidden" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_GATEWAY_MODE; ?>]" value="<?php echo BBMSL_SDK::MODE_PRODUCTION; ?>" />
									<input type="checkbox" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_GATEWAY_MODE; ?>]" value="<?php echo BBMSL_SDK::MODE_TESTING; ?>" <?php echo( $testing_mode?'checked':'' ); ?> id="toggle_site_checkbox" />
									<span class="slider round"></span>
								</label>
								<br />
								<p><?php echo __( 'This mode allows paper ordering without making real transactions, used during integration and development.', 'bbmsl-gateway' ); ?></p>
								<table cellpadding="0" cellspacing="0">
									<tr>
										<td class="pe-2"><?php echo __( 'Current mode: ', 'bbmsl-gateway' ); ?></td>
										<td><span style="color:#00F;"><?php echo esc_html( $reference_endpoint->getModeName() ); ?></span></td>
									</tr>
									<tr>
										<td class="pe-2"><?php echo __( 'Current endpoint: ', 'bbmsl-gateway' ); ?></td>
										<td><span style="color:#00F;"><?php echo esc_html( $reference_endpoint->getEndpoint() ); ?></span></td>
									</tr>
								</table>
							</div>
						</div>
					</div>
				</div>
				
				<div class="row mb-3">
					<div class="col-12">
						<h3 class="heading"><?php echo __( 'Signature Verification', 'bbmsl-gateway' ); ?></h3>
						<p>
							<?php echo __( 'To verify request coming from your online store, you\'ll need to upload your public key to BBMSL Portal or transactions will not be authenticated.', 'bbmsl-gateway' ); ?>
						</p>
						<div class="steps row g-2">
							<div class="col-12 col-md-5 col-lg-6">
								<div class="step">
									<p style="font-size:24px;font-weight:500;">1. <?php echo __( 'On WordPress', 'bbmsl-gateway' ); ?></p>
									<?php echo __( 'Please click the \'Generate\' button below to receive a new key, then you can click \'Copy\' button to copy the new public key and click \'Portal Login\' button go to BBMSL portal for the next step.', 'bbmsl-gateway' ); ?>
								</div>
							</div>
							<div class="col-12 col-md-7 col-lg-6">
								<div class="step">
									<p style="font-size:24px;font-weight:500;">2. <?php echo __( 'On BBMSL Portal', 'bbmsl-gateway' ); ?></p>
									<?php echo __( 'Please enter the \'Account Center\' by clicking your account name at top right corner. Then select \'Public Key\' from the menu and click \'Add Public Key\' button to insert the newly copied public key from wordpress. Finally, click \'Active\' to make it effective.', 'bbmsl-gateway' ); ?>
									<img class="gen_key_img" src="<?php echo plugin_dir_url( BBMSL_PLUGIN_FILE ) . 'public/images/instruction/generate_key.png?v=' . BBMSL::$version; ?>" />
								</div>
							</div>
						</div>
					</div>
					
					<div class="col-12">
						<h3 class="heading"><?php echo __( 'Public Key', 'bbmsl-gateway' ); ?></h3>
						<div class="row">
							<div class="col-12 col-md-6 toggle_site <?php echo( $testing_mode?'disabled':'' ); ?>" id="toggle_site_live">
								<div class="input-group mb-2">
									<div class="label prime">
										<?php echo __( 'Live Site', 'bbmsl-gateway' ); ?>
									</div>
									<div class="label">
										<?php echo __( 'Merchant ID', 'bbmsl-gateway' ); ?>
									</div>
									<div class="input-box">
										<input type="number" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_PRODUCTION_MERCHANT_ID; ?>]" maxlength="4" min="0" step="1" placeholder="0000" value="<?php echo WordPress::get_option( BBMSL::PARAM_PRODUCTION_MERCHANT_ID ); ?>"/>
										<span class="line"></span>
									</div>
								</div>
								
								<div class="input-group mb-2">
									<div class="input-box">
										<textarea class="monospace" spellcheck="false" id="field_production_public_key" readonly><?php echo BBMSL_SDK::pem2str( WordPress::get_option( BBMSL::PARAM_PRODUCTION_PUBLIC_KEY, '' ) ); ?></textarea>
										<span class="line"></span>
									</div>
									<p>
										<?php
										$last_update_timestamp = __( 'Never', 'bbmsl-gateway' );
										$last_update = WordPress::get_option( BBMSL::PARAM_PRODUCTION_KEY_LAST_UPDATE );
										if( isset( $last_update ) && is_string( $last_update ) ) {
											$last_update = trim( $last_update );
											if( strlen( $last_update ) > 0 ) {
												$last_update_timestamp = $last_update;
											}
										}
										echo sprintf( __( 'Last key generation: %s', 'bbmsl-gateway' ), esc_attr( $last_update_timestamp ) );
										?>
									</p>
									<p>
										<button type="submit" class="bbmsl-btn" name="<?php echo BBMSL::POSTED_KEY; ?>[action]" value="<?php echo BBMSL::ACTION_REGEN_PRODUCTION_KEYS; ?>"><?php echo __( 'Generate', 'bbmsl-gateway' ); ?></button>
										<button type="button" class="bbmsl-btn" data-copy-source="field_production_public_key"><?php echo __( 'Copy', 'bbmsl-gateway' ); ?></button>
										<a class="bbmsl-btn" href="<?php echo BBMSL::PRODUCTION_PORTAL_LINK; ?>" target="_blank" rel="noreferrer noopener"><?php echo __( 'Portal Login', 'bbmsl-gateway' ); ?></a>
									</p>
								</div>
							</div>
							<div class="col-12 col-md-6 toggle_site <?php echo( $testing_mode?'':'disabled' ); ?>" id="toggle_site_testing">
								<div class="input-group mb-2">
									<div class="label prime">
										<?php echo __( 'Testing Site', 'bbmsl-gateway' ); ?>
									</div>
									<div class="label">
										<?php echo __( 'Merchant ID', 'bbmsl-gateway' ); ?>
									</div>
									<div class="input-box">
										<input type="number" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_TESTING_MERCHANT_ID; ?>]" maxlength="4" min="0" step="1" placeholder="0000" value="<?php echo WordPress::get_option( BBMSL::PARAM_TESTING_MERCHANT_ID ); ?>"/>
										<span class="line"></span>
									</div>
								</div>
								
								<div class="input-group mb-2">
									<div class="input-box">
										<textarea class="monospace" spellcheck="false" id="field_testing_public_key" readonly><?php echo BBMSL_SDK::pem2str( WordPress::get_option( BBMSL::PARAM_TESTING_PUBLIC_KEY, '' ) ); ?></textarea>
										<span class="line"></span>
									</div>
									<p>
										<?php
										$last_update_timestamp = __( 'Never', 'bbmsl-gateway' );
										$last_update = WordPress::get_option( BBMSL::PARAM_TESTING_KEY_LAST_UPDATE );
										if( isset( $last_update ) && is_string( $last_update ) ) {
											$last_update = trim( $last_update );
											if( strlen( $last_update ) > 0 ) {
												$last_update_timestamp = $last_update;
											}
										}
										echo sprintf( __( 'Last key generation: %s', 'bbmsl-gateway' ), esc_attr( $last_update_timestamp ) );
										?>
									</p>
									<p>
										<button type="submit" class="bbmsl-btn" name="<?php echo BBMSL::POSTED_KEY; ?>[action]" value="<?php echo BBMSL::ACTION_REGEN_TESTING_KEYS; ?>"><?php echo __( 'Generate', 'bbmsl-gateway' ); ?></button>
										<button type="button" class="bbmsl-btn" data-copy-source="field_testing_public_key"><?php echo __( 'Copy', 'bbmsl-gateway' ); ?></button>
										<a class="bbmsl-btn" href="<?php echo BBMSL::TESTING_PORTAL_LINK; ?>" target="_blank" rel="noreferrer noopener"><?php echo __( 'Portal Login', 'bbmsl-gateway' ); ?></a>
									</p>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<div class="row mb-3">
					<div class="col-12">
						<hr />
					</div>
				</div>

				<div class="row mb-3">
					<div class="col-12">
						<h3 class="heading"><?php echo __( 'Refund Settings', 'bbmsl-gateway' ); ?></h3>
						<span class="inline"><?php echo __( 'Enable refund via this gateway.', 'bbmsl-gateway' ); ?>
							<div class="hint-box">
								<span class="dashicons dashicons-info"></span>
								<div class="popover">
									<img class="img-fluid w-100" src="<?php echo plugin_dir_url( BBMSL_PLUGIN_FILE ) . 'public/images/instruction/refund_function.png?v=' . BBMSL::$version; ?>" />
								</div>
							</div></span>
						<div class="input-group">
							<div class="input-box">
								<label class="switch">
									<input type="hidden" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_GATEWAY_REFUND; ?>]" value="0" />
									<input type="checkbox" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_GATEWAY_REFUND; ?>]" value="1" <?php echo( Utility::checkBooleanString( WordPress::get_option( BBMSL::PARAM_GATEWAY_REFUND, 1) )?'checked':'' ); ?> />
									<span class="slider round"></span>
								</label>
							</div>
						</div>
						
						<div class="spacer" style="min-height:30px;"></div>

						<h3 class="heading"><?php echo __( 'About Refunds', 'bbmsl-gateway' ); ?></h3>
						<p><?php echo __( 'There are 2 modes for refund, be sure to choose Refund via BBMSL to refund transactins over our gateway, otherwise it will be manual refund, which is not reflected on the merchant portal.' ); ?></p>
						<p><?php echo __( 'Our gateway can process refunds only when the order is after settlement, and once only per order. Please contact our support for cases beyond this processing scope.', 'bbmsl-gateway' ); ?></p>
						<a class="bbmsl-btn" href="<?php echo esc_url( Setup::supportLink() ); ?>" target="_blank" rel="noreferrer noopener"><?php echo __( 'Contact Support', 'bbmsl-gateway' ); ?></a>
					</div>
				</div>
				
				<div class="row mb-3">
					<div class="col-12">
						<hr />
					</div>
				</div>

				<div class="row mb-3">
					<div class="col-12">
						<h3 class="heading"><?php echo __( 'Express Checkout', 'bbmsl-gateway' ); ?></h3>
						<span class="inline"><?php echo __( 'Display Cart Express Checkout.', 'bbmsl-gateway' ); ?>
							<div class="hint-box">
								<span class="dashicons dashicons-info"></span>
								<div class="popover">
									<img class="img-fluid w-100" src="<?php echo plugin_dir_url( BBMSL_PLUGIN_FILE ) . 'public/images/instruction/gateway_express_checkout.png?v=' . BBMSL::$version; ?>" />
								</div>
							</div></span>
						<div class="input-group">
							<div class="input-box">
								<label class="switch">
									<input type="hidden" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_EXPRESS_CHECKOUT; ?>]" value="0" />
									<input type="checkbox" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_EXPRESS_CHECKOUT; ?>]" value="1" <?php echo( Utility::checkBooleanString( WordPress::get_option( BBMSL::PARAM_EXPRESS_CHECKOUT ) )?'checked':'' ); ?> />
									<span class="slider round"></span>
								</label>
							</div>
						</div>
						<p><?php echo __( 'Show BBMSL checkout option from within the mini-cart of the theme.', 'bbmsl-gateway' ); ?></p>
					</div>
				</div>
				
				<div class="row mb-3">
					<div class="col-12">
						<div class="spacer" style="min-height:200px;"></div>
					</div>
				</div>
			</div>
				
			<div class="pane" id="content-settings" style="display:none;">
				<div class="row mb-3">
					<div class="col-12">
						<h3 class="heading"><?php echo __( 'Content Settings', 'bbmsl-gateway' ); ?></h3>
						
						<div class="input-group">
							<span class="inline"><?php echo __( 'Gateway Display Name', 'bbmsl-gateway' ); ?>
								<div class="hint-box">
									<span class="dashicons dashicons-info"></span>
									<div class="popover">
										<img class="img-fluid w-100" src="<?php echo plugin_dir_url( BBMSL_PLUGIN_FILE ) . 'public/images/instruction/gateway_display_name.png?v=' . BBMSL::$version; ?>" />
									</div>
								</div>
							</span>
							<div class="language-item">
								<span class="language">English</span>
								<div class="language-input">
									<div class="input-box">
										<input type="text" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_GATEWAY_DISPLAY_NAME; ?>]" maxlength="256" placeholder="" value="<?php echo WordPress::get_option( BBMSL::PARAM_GATEWAY_DISPLAY_NAME ); ?>" style="width:300px;max-width:100%;"/>
										<span class="line"></span>
									</div>
								</div>
							</div>
							<?php if(false){ ?>
							<div class="language-item">
								<span class="language">繁體中文</span>
								<div class="language-input">
									<div class="input-box">
										<input type="text" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_GATEWAY_DISPLAY_NAME_TC; ?>]" maxlength="256" placeholder="" value="<?php echo WordPress::get_option( BBMSL::PARAM_GATEWAY_DISPLAY_NAME_TC ); ?>" style="width:300px;max-width:100%;"/>
										<span class="line"></span>
									</div>
								</div>
							</div>
							<?php } ?>
						</div>
						
						<div class="row mb-3">
							<div class="col-12">
								<div class="spacer" style="min-height:30px;"></div>
							</div>
						</div>

						<div class="input-group">
							<span class="inline"><?php echo __( 'Description', 'bbmsl-gateway' ); ?>
								<div class="hint-box">
									<span class="dashicons dashicons-info"></span>
									<div class="popover">
										<img class="img-fluid w-100" src="<?php echo plugin_dir_url( BBMSL_PLUGIN_FILE ) . 'public/images/instruction/field_description.png?v=' . BBMSL::$version; ?>" />
									</div>
								</div>
							</span>
							<p><?php echo __( 'Content to display when customer is going to checkout with BBMSL gateway.', 'bbmsl-gateway' ); ?></p>
							<div class="row">
								<div class="col-12 col-md-6">
									<div class="language-item">
										<span class="language">English</span>
										<div class="language-input">
											<div class="input-box">
												<textarea class="tinymce" spellcheck="true" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_GATEWAY_DESCRIPTION; ?>]"><?php echo WordPress::get_option( BBMSL::PARAM_GATEWAY_DESCRIPTION, '', true ); ?></textarea>
											</div>
										</div>
									</div>
								</div>
								<?php if(false){ ?>
								<div class="col-12 col-md-6">
									<div class="language-item">
										<span class="language">繁體中文</span>
										<div class="language-input">
											<div class="input-box">
												<textarea class="tinymce" spellcheck="true" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_GATEWAY_DESCRIPTION_TC; ?>]"><?php echo WordPress::get_option( BBMSL::PARAM_GATEWAY_DESCRIPTION_TC, '', true ); ?></textarea>
											</div>
										</div>
									</div>
								</div>
								<?php } ?>
							</div>
						</div>
					
						<div class="row mb-3">
							<div class="col-12">
								<div class="spacer" style="min-height:30px;"></div>
							</div>
						</div>

						<div class="input-group">
							<span class="inline"><?php echo __( 'Available Gateways', 'bbmsl-gateway' ); ?>
								<div class="hint-box">
									<span class="dashicons dashicons-info"></span>
									<div class="popover">
										<img class="img-fluid w-100" src="<?php echo plugin_dir_url( BBMSL_PLUGIN_FILE ) . 'public/images/instruction/gateway_payment_icons.png?v=' . BBMSL::$version; ?>" />
									</div>
								</div>
							</span>
							<p>
								<?php echo __( 'Select to show these icons at checkout option.', 'bbmsl-gateway' ); ?>
								<?php echo __( 'This seleciton and sorting will also reflect on payment gateway checkout page.', 'bbmsl-gateway' ); ?>
							</p>
							<p>
								<a class="me-2" href="javascript:void(0)" id="bbmsl-gateway-select-all"><?php echo __( 'Select All', 'bbmsl-gateway' ); ?></a>
								<a class="me-2" href="javascript:void(0)" id="bbmsl-gateway-deselect-all"><?php echo __( 'Deselect All', 'bbmsl-gateway' ); ?></a>
							</p>
							<table class="gateway-option-label" id="sortable_payment_methods">
								<?php foreach( BBMSL::getCoeasedMethods() as $key => $method) { ?>
								<tr>
									<td>
										<span class="dashicons dashicons-menu handle"></span>
									</td>
									<td>
										<label class="switch payment-method">
											<input type="checkbox" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_GATEWAY_METHODS; ?>][]" value="<?php echo esc_attr( $key ); ?>" <?php echo( BBMSL::hasSelectedMethod( $key )?'checked':'' ); ?> />
											<span class="slider round"></span>
										</label>
									</td>
									<td>
										<img class="logo" src="<?php echo plugin_dir_url( BBMSL_PLUGIN_FILE ) . $method[ 'logo' ]; ?>" />
									</td>
									<td>
										<span class="name"><?php echo esc_html( $method[ 'name' ] ); ?></span>
									</td>
								</tr>
								<?php } ?>
							</table>
							<p><?php echo sprintf( __( 'Hint: Drag %s to adjust sorting.', 'bbmsl-gateway' ), '<span class="dashicons dashicons-menu handle"></span>' ); ?></p>
						</div>
					
						<div class="row mb-3">
							<div class="col-12">
								<div class="spacer" style="min-height:30px;"></div>
							</div>
						</div>

						<div class="input-group">
							<span class="inline"><?php echo __( 'Thank You Page Content', 'bbmsl-gateway' ); ?>
								<div class="hint-box">
									<span class="dashicons dashicons-info"></span>
									<div class="popover">
										<img class="img-fluid w-100" src="<?php echo plugin_dir_url( BBMSL_PLUGIN_FILE ) . 'public/images/instruction/field_thank_you.png?v=' . BBMSL::$version; ?>" />
									</div>
								</div>
									</span>
							<p><?php echo __( 'The content displayed after the customer checks out an order and returned to the confirmation page. ', 'bbmsl-gateway' ); ?></p>
							
							<div class="row">
								<div class="col-12 col-md-6">
									<div class="language-item">
										<span class="language">English</span>
										<div class="language-input">
											<div class="input-box">
												<textarea class="tinymce" spellcheck="true" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_GATEWAY_THANK_YOU_PAGE; ?>]"><?php echo WordPress::get_option( BBMSL::PARAM_GATEWAY_THANK_YOU_PAGE, '', true ); ?></textarea>
											</div>
										</div>
									</div>
								</div>
								<?php if(false){ ?>
								<div class="col-12 col-md-6">
									<div class="language-item">
										<span class="language">繁體中文</span>
										<div class="language-input">
											<div class="input-box">
												<textarea class="tinymce" spellcheck="true" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_GATEWAY_THANK_YOU_PAGE_TC; ?>]"><?php echo WordPress::get_option( BBMSL::PARAM_GATEWAY_THANK_YOU_PAGE_TC, '', true ); ?></textarea>
											</div>
										</div>
									</div>
								</div>
								<?php } ?>
							</div>
						</div>
					
						<div class="row mb-3">
							<div class="col-12">
								<div class="spacer" style="min-height:30px;"></div>
							</div>
						</div>
						
						<div class="input-group">
							<span class="inline"><?php echo __( 'Email Content', 'bbmsl-gateway' ); ?>
								<div class="hint-box">
									<span class="dashicons dashicons-info"></span>
									<div class="popover">
										<img class="img-fluid w-100" src="<?php echo plugin_dir_url( BBMSL_PLUGIN_FILE ) . 'public/images/instruction/field_email_content.png?v=' . BBMSL::$version; ?>" />
									</div>
								</div>
							</span>
							<p><?php echo __( 'The content display to the customer in the invoice email.', 'bbmsl-gateway' ); ?></p>
							
							<div class="row">
								<div class="col-12 col-md-6">
									<div class="language-item">
										<span class="language">English</span>
										<div class="language-input">
											<div class="input-box">
												<textarea class="tinymce" spellcheck="true" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_GATEWAY_EMAIL_CONTENT; ?>]"><?php echo WordPress::get_option( BBMSL::PARAM_GATEWAY_EMAIL_CONTENT, '', true ); ?></textarea>
											</div>
										</div>
									</div>
								</div>
								<?php if(false){ ?>
								<div class="col-12 col-md-6">
									<div class="language-item">
										<span class="language">繁體中文</span>
										<div class="language-input">
											<div class="input-box">
												<textarea class="tinymce" spellcheck="true" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_GATEWAY_EMAIL_CONTENT_TC; ?>]"><?php echo WordPress::get_option( BBMSL::PARAM_GATEWAY_EMAIL_CONTENT_TC, '', true ); ?></textarea>
											</div>
										</div>
									</div>
								</div>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<?php if(false){ ?>
			<div class="pane" id="other-settings" style="display:none;">
				<div class="row mb-3">
					<div class="col-12">
						<h3 class="heading"><?php echo __( 'Others Settings', 'bbmsl-gateway' ); ?></h3>
					</div>
					<div class="col-12">
						<h3 class="heading"><?php echo __( 'Show language tools', 'bbmsl-gateway' ); ?></h3>
						<span class="inline"><?php echo __( 'Show switch language options for the gateway page.', 'bbmsl-gateway' ); ?>
							<div class="hint-box">
								<span class="dashicons dashicons-info"></span>
								<div class="popover">
									<img class="img-fluid w-100" src="<?php echo plugin_dir_url( BBMSL_PLUGIN_FILE ) . 'public/images/instruction/language_tools.png?v=' . BBMSL::$version; ?>" />
								</div>
							</div></span>
						<div class="input-group">
							<div class="input-box">
								<label class="switch">
									<input type="hidden" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_SHOW_LANGUAGE_TOOLS; ?>]" value="0" />
									<input type="checkbox" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_SHOW_LANGUAGE_TOOLS; ?>]" value="1" <?php echo( Utility::checkBooleanString( WordPress::get_option( BBMSL::PARAM_SHOW_LANGUAGE_TOOLS ) )?'checked':'' ); ?> />
									<span class="slider round"></span>
								</label>
							</div>
						</div>
					</div>
					<div class="col-12">
						<h3 class="heading"><?php echo __( 'Show Gateway Branding', 'bbmsl-gateway' ); ?></h3>
						<span class="inline"><?php echo __( 'Show powered by text at the bottom of the gateway checkout page.', 'bbmsl-gateway' ); ?>
							<div class="hint-box">
								<span class="dashicons dashicons-info"></span>
								<div class="popover">
									<img class="img-fluid w-100" src="<?php echo plugin_dir_url( BBMSL_PLUGIN_FILE ) . 'public/images/instruction/gateway_branding.png?v=' . BBMSL::$version; ?>" />
								</div>
							</div></span>
						<div class="input-group">
							<div class="input-box">
								<label class="switch">
									<input type="hidden" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_SHOW_GATEWAY_BRAND; ?>]" value="0" />
									<input type="checkbox" name="<?php echo BBMSL::POSTED_KEY; ?>[payment_settings][<?php echo BBMSL::PARAM_SHOW_GATEWAY_BRAND; ?>]" value="1" <?php echo( Utility::checkBooleanString( WordPress::get_option( BBMSL::PARAM_SHOW_GATEWAY_BRAND ) )?'checked':'' ); ?> />
									<span class="slider round"></span>
								</label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php } ?>
		</div>
	</div>
</div>
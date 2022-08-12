<?php
/*
 * vt functions
 */

add_action( 'woocommerce_order_status_completed', 'points_per_amount_spent_on_order' );
function points_per_amount_spent_on_order( $order_id ) {
    $total_points=0;
    $order = wc_get_order( $order_id );
    $user_id = $order->get_customer_id();
    $ordertotal=$order->get_total();
    $ratio = 0.05;
    $previous_points_total = get_user_meta( $user_id, 'points_total', false );
    if ( empty( $previous_points_total ) ) {
        add_user_meta( $user_id, 'points_total', $total_points);
    }
    update_user_meta( $user_id, 'points_total', $previous_points_total[0] + floor($ordertotal*$ratio) );
}


// account page
add_filter ( 'woocommerce_account_menu_items', 'account_points_link', 40 );
function account_points_link( $menu_links ){
	
	$menu_links = array_slice( $menu_links, 0, 5, true ) 
	+ array( 'account-points' => __('Account points', 'testvetorino') )
	+ array_slice( $menu_links, 5, NULL, true );
	
	return $menu_links;

}
add_action( 'init', 'vt_add_endpoint' );
function vt_add_endpoint() {
	add_rewrite_endpoint( 'account-points', EP_PAGES );
}

add_action( 'woocommerce_account_account-points_endpoint', 'vt_my_account_endpoint_content' );
function vt_my_account_endpoint_content() {
    $user_id = get_current_user_id();
    $total_points = get_user_meta( $user_id, 'points_total' , true );
    echo '<h3>'.__('Number of points earned', 'testvetorino').'</h3>';
    echo '<p class="lead"><b>'.sprintf(_n('<span id="cumulated_points">%s</span> point','<span id="cumulated_points">%s</span> points',$total_points,'testvetorino'),$total_points).'</b></p>';
    if ($total_points < 50){
    echo '<p>'.__('Collect at least 50 points to generate a discount coupon of the same value as your points on your next order.', 'testvetorino').'</p>';
    }else{
    echo '<form>
    <div class="form-group">
    '.__('Generate a discount coupon of the same value as your points on your next order.', 'testvetorino').'
    <input id="points_input" type="range" value="1" class="form-range" name="InputPoints" id="InputPoints" min="1" max="'.$total_points.'" oninput="OutputPoints.value = InputPoints.value">
    </div>
    </form>
    <button id="apply_coupon" type="button" class="btn btn-primary">'.sprintf(__('Generate your %s discount coupon', 'testvetorino'),'<output name="OutputPoints" id="OutputPoints">1</output><span> '.get_woocommerce_currency_symbol().'</span>').'</button>';
   }
}



function generate_coupon_code() {
    global $wpdb;
    $coupon_codes = $wpdb->get_col("SELECT post_name FROM $wpdb->posts WHERE post_type = 'shop_coupon'");
    for ( $i = 0; $i < 1; $i++ ) {
      $generated_code = 'VT-'.strtolower(wp_generate_password( 5, false ) );
      if( in_array( $generated_code, $coupon_codes ) ) {
        $i--;
      } else {
        break;
      }
    }
    return $generated_code;
  }

if ( is_admin() ) {
add_action( 'wp_ajax_send_mail', 'ajax_request' );
}
function ajax_request() {
    $amount = intval( $_POST['amount'] );
    $user = wp_get_current_user();
    $discount_type    = 'fixed_cart';
    $coupon = new WC_Coupon();
    $firstname = $user->first_name;
    $lastname = $user->last_name;
    $username = $firstname.' '.$lastname;
    $sitetitle = get_bloginfo( 'name' );
    $adminemail = get_bloginfo('admin_email');
    $email = strtolower($user->billing_email);
    $coupon_code  = generate_coupon_code();
    $coupon->set_code( $coupon_code );
    $coupon->set_discount_type( $discount_type );
    $coupon->set_amount( $amount );
    $coupon->set_usage_limit( 1 );
    $coupon->set_individual_use( true );
    $coupon->set_email_restrictions($email);
    $coupon->save();
    $user_id = $user->ID;
    $previous_points_total = get_user_meta( $user_id, 'points_total', false );
    update_user_meta( $user_id, 'points_total', $previous_points_total[0] - $amount );
    $amountprice = $amount.' '.html_entity_decode(get_woocommerce_currency_symbol());
    $message=sprintf(__('Here is your %s discount coupon to use on your next order.', 'testvetorino'),$amountprice);
    $credentials = SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', get_option('sendinblue_api'));
    $apiInstance = new SendinBlue\Client\Api\TransactionalEmailsApi(new GuzzleHttp\Client(),$credentials);

    $sendSmtpEmail = new \SendinBlue\Client\Model\SendSmtpEmail([
     'subject' => __('Your coupon', 'testvetorino'),
     'sender' => ['name' => $sitetitle, 'email' => get_option('sendinblue_mail')],
     'replyTo' => ['name' => $sitetitle, 'email' => $adminemail ],
     'to' => [[ 'name' => $username, 'email' => $email ]],
     'htmlContent' => '<!doctype html>
     <html>
       <head>
         <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
         <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
         <title>Votre bon de réduction</title>
         <style>
           /* -------------------------------------
               GLOBAL RESETS
           ------------------------------------- */
           
           /*All the styling goes here*/
           body {
             background-color: #f6f6f6;
             font-family: sans-serif;
             -webkit-font-smoothing: antialiased;
             font-size: 14px;
             line-height: 1.4;
             margin: 0;
             padding: 0;
             -ms-text-size-adjust: 100%;
             -webkit-text-size-adjust: 100%; 
           }
     
           table {
             border-collapse: separate;
             mso-table-lspace: 0pt;
             mso-table-rspace: 0pt;
             width: 100%; }
             table td {
               font-family: sans-serif;
               font-size: 14px;
               vertical-align: top; 
           }
     
           /* -------------------------------------
               BODY & CONTAINER
           ------------------------------------- */
     
           .body {
             background-color: #f6f6f6;
             width: 100%; 
           }
     
           /* Set a max-width, and make it display as block so it will automatically stretch to that width, but will also shrink down on a phone or something */
           .container {
             display: block;
             margin: 0 auto !important;
             /* makes it centered */
             max-width: 580px;
             padding: 10px;
             width: 580px; 
           }
     
           /* This should also be a block element, so that it will fill 100% of the .container */
           .content {
             box-sizing: border-box;
             display: block;
             margin: 0 auto;
             max-width: 580px;
             padding: 10px; 
           }
     
           /* -------------------------------------
               HEADER, FOOTER, MAIN
           ------------------------------------- */
           .main {
             background: #ffffff;
             border-radius: 3px;
             width: 100%; 
           }
     
           .wrapper {
             box-sizing: border-box;
             padding: 20px; 
           }
     
           .content-block {
             padding-bottom: 10px;
             padding-top: 10px;
           }
     
     
           /* -------------------------------------
               TYPOGRAPHY
           ------------------------------------- */
           h1{
             color: #000000;
             font-family: sans-serif;
             font-weight: 400;
             line-height: 1.4;
             margin: 0;
             margin-bottom: 30px; 
           }
     
           h1 {
             font-size: 35px;
             font-weight: 300;
             text-align: center;
             text-transform: capitalize; 
           }
     
           p,
           ul,
           ol {
             font-family: sans-serif;
             font-size: 14px;
             font-weight: normal;
             margin: 0;
             margin-bottom: 15px; 
             text-align: center;
           }
           .coupon span{
            text-align:center;
            font-size: 1.25rem;
            font-weight: bold;
            background-color:#f6f6f6;
            padding:10px;
            margin:auto;
            width:200px;
            border-radius: 3px;
           }
           /* -------------------------------------
               OTHER STYLES THAT MIGHT BE USEFUL
           ------------------------------------- */
           .last {
             margin-bottom: 0; 
           }
     
           .first {
             margin-top: 0; 
           }
     
           .align-center {
             text-align: center; 
           }
     
           .align-right {
             text-align: right; 
           }
     
           .align-left {
             text-align: left; 
           }
     
           .clear {
             clear: both; 
           }
     
           .mt0 {
             margin-top: 0; 
           }
     
           .mb0 {
             margin-bottom: 0; 
           }
     
           .preheader {
             color: transparent;
             display: none;
             height: 0;
             max-height: 0;
             max-width: 0;
             opacity: 0;
             overflow: hidden;
             mso-hide: all;
             visibility: hidden;
             width: 0; 
           }
     
           hr {
             border: 0;
             border-bottom: 1px solid #f6f6f6;
             margin: 20px 0; 
           }
     
           /* -------------------------------------
               RESPONSIVE AND MOBILE FRIENDLY STYLES
           ------------------------------------- */
           @media only screen and (max-width: 620px) {
             table.body h1 {
               font-size: 28px !important;
               margin-bottom: 10px !important; 
             }
             table.body p,
             table.body ul,
             table.body ol,
             table.body td,
             table.body span,
             table.body a {
               font-size: 16px !important; 
             }
             table.body .wrapper,
             table.body .article {
               padding: 10px !important; 
             }
             table.body .content {
               padding: 0 !important; 
             }
             table.body .container {
               padding: 0 !important;
               width: 100% !important; 
             }
             table.body .main {
               border-left-width: 0 !important;
               border-radius: 0 !important;
               border-right-width: 0 !important; 
             }
             table.body .btn table {
               width: 100% !important; 
             }
             table.body .btn a {
               width: 100% !important; 
             }
             table.body .img-responsive {
               height: auto !important;
               max-width: 100% !important;
               width: auto !important; 
             }
           }
         </style>
       </head>
       <body>
         <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body">
           <tr>
             <td>&nbsp;</td>
             <td class="container">
               <div class="content">
                 <table role="presentation" class="main">
                   <tr>
                     <td class="wrapper">
                       <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                         <tr>
                           <td>
                             <h1>Bonjour {{params.bodyName}},</h1>
                             <p>{{params.bodyMessage}}</p>
                             <p class="coupon"><span>{{params.bodyCoupon}}</span></p>
                           </td>
                         </tr>
                       </table>
                     </td>
                   </tr>
                 </table>
               </div>
             </td>
             <td>&nbsp;</td>
           </tr>
         </table>
       </body>
     </html>',
     'params' => ['bodyName'=> $firstname,'bodyMessage'=>$message, 'bodyCoupon' => $coupon_code]
]);

try {
    $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
    print_r($result);
} catch (Exception $e) {
    echo $e->getMessage(),PHP_EOL;
}
die();
}
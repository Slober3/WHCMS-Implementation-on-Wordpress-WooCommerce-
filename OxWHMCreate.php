<?php

/***********************************************************************************
*
*   Plugin Name:    OxWHMCreate
*   Plugin URI:     https://whmcr.oxygencore.net
*   Description:    Plugin for creating usesr in WHM/Cpanel
*   Author:         Slober HF
*   Author URI:     https://whmcr.oxygencore.net
*   Version:        1.0
*   License:        CC BY-NC-SA 4.0
*   License URI:    https://creativecommons.org/licenses/by-nc-sa/4.0/legalcode
*
***********************************************************************************/

function oxwhmcr_add_whm_link(){
    // Add link to WHM panel to admin bar
    
    global $wp_admin_bar;
    
    $whmLinkArgs = array(
        'id'    =>  'whm_cpanel_link',
        'title' =>  'WHM Control panel',
        'href'  =>  get_site_url() . '/whm'
    );
    
    $wp_admin_bar->add_menu($whmLinkArgs);
}

function oxwhmcr_add_oxwhmcreate_menu(){
    // Add Top Menu for this plugin
    
    add_menu_page('OxWHMCreate', 
                  'OxWHMCreate', 
                  'manage_options', 
                  'oxwhmcreate', 
                  'oxwhmcr_theme_func_main',
                  '',
                  2
                 );
    
    
    // Add Sub Menu's for this plugin
    add_submenu_page('oxwhmcreate',
                     'View Users', 
                     'View Users', 
                     'manage_options', 
                     'oxwhm-viewusers', 
                     'oxwhmcr_theme_func_view_user'
                    );
    
     add_submenu_page('oxwhmcreate',
                     'Create User', 
                     'Create User', 
                     'manage_options', 
                     'oxwhm-create-user', 
                     'oxwhmcr_theme_func_create_user'
                    );
    
    add_submenu_page('oxwhmcreate',
                     'Settings', 
                     'Settings', 
                     'manage_options', 
                     'oxwhm-settings', 
                     'oxwhmcr_theme_func_settings'
                    );
    
    add_submenu_page('oxwhmcreate',
                     'FAQ', 
                     'FAQ', 
                     'manage_options', 
                     'oxwhm-faq', 
                     'oxwhmcr_theme_func_faq'
                    );

}

function oxwhmcr_theme_func_main(){
    // Create Main page with plugin information and user creation form
    ?>
    <div class="wrap">
        <div id="icon-options-general" class="icon32">
            <br>
        </div>
        <h2>Main Plugin Information Page.</h2>
        
        <p>This plugin uses your current WHM panel to create users.</p>
        <p>You can create users manually and automaticly creating users the manual way shouldn't require any third party plugins.</p>
        <p>Creating users semi-automaticly will require other plugins the plugins required are listed here:</p>
        <ol>
        <li><strong>Woocommerce</strong></li>
        <li><strong>Woo Extra Product Options</strong></li>
        </ol>
        <p>for further support you can contact me or another representative of this plugin on &nbsp;-&nbsp;<strong><a href="mailto:support@oxygencore.net">support@oxygencore.net</a>&nbsp;-</strong></p>
    </div>
        <form action="./admin.php?page=oxwhmcreate" method="post">
                <input type="number" required name="order_id_client" id="order_id_client" placeholder="Order ID. Ex.314" >
                <input type="submit" name="submit" value="Create User" >
</form>
<?php

    
    if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    // Make a connection to WHM
    // Get User and password from Settings
$whmusername = get_option('whm_user');
$whmpassword = get_option('whm_password');

        // GET ORDER from woocom
        $woocom_qry_get_order = get_site_url()."/wc-api/v2/orders/".$_POST['order_id_client']."/?consumer_key=".get_option('consumer_key')."&consumer_secret=".get_option('consumer_secret');
    
    $woocom_exec_get_order = file_get_contents($woocom_qry_get_order);
    
    $json_woocom_order_decode = json_decode($woocom_exec_get_order, true);
   
    $oxwhmcr_user_aangemaakt_mail= $json_woocom_order_decode['order']['billing_address']['email'];
    $oxwhmcr_user_aangemaakt_domein = $json_woocom_order_decode['order']['line_items'][0]['meta'][0]['value'].$json_woocom_order_decode['order']['line_items'][0]['meta'][1]['value'];
        
    $oxwhmcr_user_aangemaakt= $json_woocom_order_decode['order']['line_items'][0]['meta'][0]['value']."ox".rand(5, 370);

            if(empty($oxwhmcr_user_aangemaakt_domein))
{
$oxwhmcr_user_aangemaakt_domein = 'o'.GeraHash(6).'.'.get_option('default_subdom');
$oxwhmcr_user_aangemaakt= 'o'.GeraHash(6).$json_woocom_order_decode['order']['line_items'][0]['meta'][0]['value']."ox".rand(5, 370);

    }
        
    // String/ Query for WHM pannel
$query = "https://" . get_option('whm_url') . ":2087/json-api/createacct?api.version=1&username=".$oxwhmcr_user_aangemaakt."&domain=".$oxwhmcr_user_aangemaakt_domein."&contactemail=".$oxwhmcr_user_aangemaakt_mail;
 
    // Sending the query with curl do the authorization and get a JSON output
    
$curl = curl_init();                                // Create Curl Object
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);       // Allow self-signed certs
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);       // Allow certs that do not match the hostname
curl_setopt($curl, CURLOPT_HEADER,0);               // Do not include header in output
curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);       // Return contents of transfer on curl_exec
$header[0] = "Authorization: Basic " . base64_encode($whmusername.":".$whmpassword) . "\n\r";
curl_setopt($curl, CURLOPT_HTTPHEADER, $header);    // set the username and password
curl_setopt($curl, CURLOPT_URL, $query);            // execute the query
$result = curl_exec($curl);
if ($result == false) {
    error_log("curl_exec threw error \"" . curl_error($curl) . "\" for $query");   
                                                    // log error if curl exec fails
}
curl_close($curl);

// Filter Password from result string
$parsed = oxwhmcr_get_string_between($result, '| PassWord: ', '\n|');
echo "<br /><p>The Username: ".$oxwhmcr_user_aangemaakt." </p>";
echo "<p>The Domain: ".$oxwhmcr_user_aangemaakt_domein." </p>";      
echo "<p>The Emailaddress: ".$oxwhmcr_user_aangemaakt_mail." </p>";        
echo "<p>The Password: ".$parsed." </p><br/><br/><br/>";

$variables = array("domain"=>$oxwhmcr_user_aangemaakt_domein,"username"=>$oxwhmcr_user_aangemaakt,"password"=>$parsed);
$string = get_option('output_txt');

foreach($variables as $key => $value){
    $string = str_replace('['.strtoupper($key).']', $value, $string);
}
        echo "<textarea rows='8' cols='70' >".$string."</textarea>";
        
    }

/*    $ch = curl_init();

curl_setopt($ch, CURLOPT_URL, get_site_url()."/wp-json/wc/v2/orders/".$_POST['order_id_client']."/notes");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "{\n  \"note\": \"".$string."\"\n}");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_USERPWD, get_option('consumer_key') . ":" . get_option('consumer_secret'));

$headers = array();
$headers[] = "Content-Type: application/json";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close ($ch);
    */
?>

    <?php
}

function oxwhmcr_theme_func_view_user(){
    // Create view user page for viewing all users
    ?>
    <div class="wrap">
        <div id="icon-options-general" class="icon32">
            <br>
        </div>
        <h2>View User</h2>
        
        <p>This plugin uses your current WHM panel to View users.</p>
        <p>You will now view all current users</p>
        <p>for further support you can contact me or another representative of this plugin on &nbsp;-&nbsp;<strong><a href="mailto:support@oxygencore.net">support@oxygencore.net</a>&nbsp;-</strong></p>
    </div>
<?php
    // Make a connection to WHM
    // Get User and password from Settings
$whmusername = get_option('whm_user');
$whmpassword = get_option('whm_password');
 
    // String/ Query for WHM pannel
$query = "https://" . get_option('whm_url') . ":2087/json-api/listaccts?api.version=1";
 
    // Sending the query with curl do the authorization and get a JSON output
    
$curl = curl_init();                                // Create Curl Object
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);       // Allow self-signed certs
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);       // Allow certs that do not match the hostname
curl_setopt($curl, CURLOPT_HEADER,0);               // Do not include header in output
curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);       // Return contents of transfer on curl_exec
$header[0] = "Authorization: Basic " . base64_encode($whmusername.":".$whmpassword) . "\n\r";
curl_setopt($curl, CURLOPT_HTTPHEADER, $header);    // set the username and password
curl_setopt($curl, CURLOPT_URL, $query);            // execute the query
$result = curl_exec($curl);
if ($result == false) {
    error_log("curl_exec threw error \"" . curl_error($curl) . "\" for $query");   
                                                    // log error if curl exec fails
}
curl_close($curl);
     
$data =  json_decode($result);
echo "<table class='widefat fixed' cellspacing='0'>
           <tr class='alternate'>
                <td class='column-columnname col-md-1 '><strong>Id</strong></td>
                <td class='column-columnname col-md-2 '><strong>User</strong></td>
                <td class='column-columnname col-md-3 '><strong>Email</strong></td>
                <td class='column-columnname col-md-3 '><strong>Domain</strong></td>
                <td class='column-columnname col-md-1 '><strong>Usage</strong></td>
                <td class='column-columnname col-md-2 sortable desc'><strong>Create Date</strong></td>

</tr>";

    // Get data from the Data object
    // Fetch data from the array
foreach($data as $object):?>
    <?php foreach($object->acct as $mydata)

    { 
?>
        <tr <?php echo "" . $oxwhm_itemnr % 2 ? 'class="alternate"' : '' ?>>
                <td class="column-columnname col-md-1" > <?php echo $oxwhm_itemnr += 1 ; ?></td>
                <td class="column-columnname col-md-2"> <?php echo $mydata->user; ?></td>
                <td class="column-columnname col-md-3"> <a href="mailto:<?php echo $mydata->email; ?>"><?php echo $mydata->email; ?></a></td>
                <td class="column-columnname col-md-3"> <a href="http://<?php echo $mydata->domain; ?>"><?php echo $mydata->domain; ?></a></td>
                <td class="column-columnname col-md-1"> <?php echo $mydata->diskused; ?></td>
                <td class="column-columnname col-md-2"> <?php echo $mydata->startdate; ?></td>
 
        </tr>
         
    <?php
    } 
?>

<?php endforeach;
      echo "</table>";
?>


    <?php
}

function oxwhmcr_theme_func_settings(){
    // Create settings page
    ?>
<div class="wrap">
	<div id="icon-options-general" class="icon32">
	   <br>
	</div>
<h2>Settings</h2>		
    <div class="form_description">
        <br />
			<h2>WHM Login credentials</h2>
			<p>Plese enter your WHM credentials</p>
		<br /> <br />
    </div>
		<form method="post" action="options.php">
	        <?php
               
                /* 
                * Add Settings
                * Generate settings form,...
                */
    
	            settings_fields("section");
	            do_settings_sections("oxwhmcr-options");      
	            submit_button(); 
	        ?>
		</form>
</div>
<?php
}

function oxwhmcr_theme_func_faq(){
        // Create FAQ page
    ?>
    <div class="wrap">
        <div id="icon-options-general" class="icon32">
            <br>
        </div>
        <h2>FAQ</h2>
    </div>
    <?php
}

function oxwhmcr_theme_func_create_user(){
        // Create User Manually page
    ?><div class="wrap">
        <div id="icon-options-general" class="icon32">
            <br>
        </div>
        <h2>Create User Plugin Information Page.</h2>
        
        <p>This plugin uses your current WHM panel to create users.</p>
        <p>You can create users manually and automaticly creating users the manual way shouldn't require any third party plugins.</p>
        <p>Creating users semi-automaticly will require other plugins the plugins required are listed here:</p>
        <ol>
        <li><strong>Woocommerce</strong></li>
        <li><strong>Woo Extra Product Options</strong></li>
        </ol>
        <p>for further support you can contact me or another representative of this plugin on &nbsp;-&nbsp;<strong><a href="mailto:support@oxygencore.net">support@oxygencore.net</a>&nbsp;-</strong></p>
    </div>
        <form action="./admin.php?page=oxwhm-create-user" method="post">
            
                <input type="email"  name="order_mail_client" id="order_mail_client" placeholder="email" >
                <input type="text"  name="order_dom_client" id="order_dom_client" placeholder="Domain" >
                <input type="text"  name="order_uname_client" id="order_uname_client" placeholder="Username" >

            <input type="submit" name="submit" value="Create User" >
</form>
<?php

    
    if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    // Make a connection to WHM
    // Get User and password from Settings
$whmusername = get_option('whm_user');
$whmpassword = get_option('whm_password');

   
    $oxwhmcr_user_aangemaakt_mail= $_POST['order_mail_client'];
    $oxwhmcr_user_aangemaakt_domein = $_POST['order_dom_client'];
    $oxwhmcr_user_aangemaakt= $_POST['order_uname_client'];

        if(empty($oxwhmcr_user_aangemaakt_domein))
{
            $oxwhmcr_user_aangemaakt_domein = "o".GeraHash(6).'.'.get_option('default_subdom');
}
        if(empty($oxwhmcr_user_aangemaakt))
{
            $oxwhmcr_user_aangemaakt= 'o'.GeraHash(6)."ox".rand(5, 370);
}
        if(empty($oxwhmcr_user_aangemaakt_mail))
{
            $oxwhmcr_user_aangemaakt_mail= GeraHash(6).'@'."o".GeraHash(6).'.com';

}
        
    // String/ Query for WHM pannel
$query = "https://" . get_option('whm_url') . ":2087/json-api/createacct?api.version=1&username=".$oxwhmcr_user_aangemaakt."&domain=".$oxwhmcr_user_aangemaakt_domein."&contactemail=".$oxwhmcr_user_aangemaakt_mail;
 
    // Sending the query with curl do the authorization and get a JSON output
    
$curl = curl_init();                                // Create Curl Object
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);       // Allow self-signed certs
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);       // Allow certs that do not match the hostname
curl_setopt($curl, CURLOPT_HEADER,0);               // Do not include header in output
curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);       // Return contents of transfer on curl_exec
$header[0] = "Authorization: Basic " . base64_encode($whmusername.":".$whmpassword) . "\n\r";
curl_setopt($curl, CURLOPT_HTTPHEADER, $header);    // set the username and password
curl_setopt($curl, CURLOPT_URL, $query);            // execute the query
$result = curl_exec($curl);
if ($result == false) {
    error_log("curl_exec threw error \"" . curl_error($curl) . "\" for $query");   
                                                    // log error if curl exec fails
}
curl_close($curl);

// Filter Password from result string
$parsed = oxwhmcr_get_string_between($result, '| PassWord: ', '\n|');
echo "<br /><p>The Username: ".$oxwhmcr_user_aangemaakt." </p>";
echo "<p>The Domain: ".$oxwhmcr_user_aangemaakt_domein." </p>";      
echo "<p>The Emailaddress: ".$oxwhmcr_user_aangemaakt_mail." </p>";        
echo "<p>The Password: ".$parsed." </p><br/><br/><br/>";

        $variables = array("domain"=>$oxwhmcr_user_aangemaakt_domein,"username"=>$oxwhmcr_user_aangemaakt,"password"=>$parsed);
$string = get_option('output_txt');

foreach($variables as $key => $value){
    $string = str_replace('['.strtoupper($key).']', $value, $string);
}
        echo "<textarea rows='8' cols='70' >".$string."</textarea>";
         

    }
?>

    <?php
}


function oxwhmcr_display_whm_user_element()
{
        // Create WHM_user element contains WHM username
	?>
    	<input type="text" name="whm_user" id="whm_user" value="<?php echo get_option('whm_user'); ?>" />
	<?php
}

function oxwhmcr_display_whm_password_element()
{
        // Create WHM_Password element contains WHM password
	?>
    	<input type="password" name="whm_password" id="whm_password" value="<?php echo get_option('whm_password'); ?>"/>
	<?php
}

function oxwhmcr_display_whm_url_element()
{
        // Create WHM_Password element contains WHM password
	?>
    	<input type="text" name="whm_url" id="whm_url" placeholder="Ex. whm.domain.net" value="<?php echo get_option('whm_url'); ?>" />
	<?php
}

function oxwhmcr_display_consumer_key_element()
{
        // Create Consumer_key element contains WHM password
	?>
    	<input type="text" name="consumer_key" id="consumer_key" value="<?php echo get_option('consumer_key'); ?>" />
	<?php
}
function oxwhmcr_display_consumer_secret_element()
{
        // Create Consumer_secret element contains WHM password
	?>
    	<input type="text" name="consumer_secret" id="consumer_secret" value="<?php echo get_option('consumer_secret'); ?>" />
	<?php
}

function oxwhmcr_display_output_txt_element()
{
        // Create Consumer_secret element contains WHM password
	?>
    	<textarea type="text" rows="8" cols="70" name="output_txt" id="output_txt" placeholder="Your order is completed by Oxygencore Cloud for [DOMAIN]
Log in to cPanel at cpanel.oxygencore.net with your credentials:

Username: [USERNAME]
Password: [PASSWORD]

When you use this service you’ll automatically accept the ToS and Privacy policy these policies can be accessed here:
https://oxygencore.net/privacy-policy/
https://oxygencore.net/terms-of-service/" >
<?php echo get_option('output_txt'); ?>
</textarea>
	<?php
}

function oxwhmcr_display_def_subd_element()
{
        // Create Consumer_secret element contains WHM password
	?>
    	<input type="text" name="default_subdom" id="default_subdom" value="<?php echo get_option('default_subdom'); ?>" />
	<?php
}


function oxwhmcr_display_theme_panel_fields()
{
        // Save Settings, Generate Form
	add_settings_section("section", 
                         "All Settings", 
                         null, 
                         "oxwhmcr-options"
                        );
	
	add_settings_field("whm_user", 
                       "WHM Username", 
                       "oxwhmcr_display_whm_user_element", 
                       "oxwhmcr-options", 
                       "section"
                      );
    
    add_settings_field("whm_password", 
                       "WHM Password", 
                       "oxwhmcr_display_whm_password_element", 
                       "oxwhmcr-options", 
                       "section"
                      );
        
    add_settings_field("whm_url", 
                       "WHM URL", 
                       "oxwhmcr_display_whm_url_element", 
                       "oxwhmcr-options", 
                       "section"
                      );
    
    add_settings_field("default_subdom", 
                       "Default Subdomain", 
                       "oxwhmcr_display_def_subd_element", 
                       "oxwhmcr-options", 
                       "section"
                      );
    
    add_settings_field("consumer_key", 
                       "Consumer Key", 
                       "oxwhmcr_display_consumer_key_element", 
                       "oxwhmcr-options", 
                       "section"
                      );
    
    add_settings_field("consumer_secret", 
                       "Consumer Secret", 
                       "oxwhmcr_display_consumer_secret_element", 
                       "oxwhmcr-options", 
                       "section"
                      );
    
    add_settings_field("output_txt", 
                       "Output Text", 
                       "oxwhmcr_display_output_txt_element", 
                       "oxwhmcr-options", 
                       "section"
                      );
    
        // Save settings Register them 
    register_setting("section", 
                     "whm_user"
                    );
    
    register_setting("section",
                     "whm_password"
                    );
    
    register_setting("section",
                     "whm_url"
                    );
    
    register_setting("section",
                     "default_subdom"
                    );
    
    register_setting("section",
                     "consumer_key"
                    );
    
    register_setting("section",
                     "consumer_secret"
                    );  
    
    register_setting("section",
                     "output_txt"
                    );  
}

function oxwhmcr_get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

function GeraHash($qtd){ 
//Under the string $Caracteres you write all the characters you want to be used to randomly generate the code. 
$Caracteres = 'ABCDEFGHIJKLMOPQRSTUVXWYZ0123456789'; 
$QuantidadeCaracteres = strlen($Caracteres); 
$QuantidadeCaracteres--; 

$Hash=NULL; 
    for($x=1;$x<=$qtd;$x++){ 
        $Posicao = rand(0,$QuantidadeCaracteres); 
        $Hash .= substr($Caracteres,$Posicao,1); 
    } 

return $Hash; 
} 



// Add WHM link to Admin bar 
add_action('wp_before_admin_bar_render', 
           'oxwhmcr_add_whm_link'
          );

// Create Menu and sub-menus
add_action('admin_menu', 
           'oxwhmcr_add_oxwhmcreate_menu'
          );

// Create Form and settings
add_action("admin_init", 
           "oxwhmcr_display_theme_panel_fields"
          );



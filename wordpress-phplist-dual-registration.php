<?php
/*
Plugin Name: WordPress & PHPList Dual Registration
Plugin URI: http://www.seanbluestone.com/wordpress-phplist-dual-registration
Description: Whenever a person registers at your WordPress site they are also signed up for your chosen PHPList list.
Version: 1.0
Author: Sean Bluestone
Author URI: http://www.seanbluestone.com
*/

function http_post($host, $path, $poststring, &$http_response, $cookies = ''){
	$http_response = '';
	$fp = fsockopen('localhost', 80, $errno, $errstr, 30);
	if(!$fp){
		$http_response = "$errstr ($errno)";
		return false;
	}else{
		//send the server request
		fputs($fp, "POST $path HTTP/1.1\r\n");
		fputs($fp, "Host: ".$host."\r\n");
		fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
		fputs($fp, "Content-length: ".strlen($poststring)."\r\n");
		fputs($fp, "Cookie: $cookies\r\n");
		fputs($fp, "Connection: close\r\n\r\n");
		fputs($fp, $poststring . "\r\n\r\n");

		//loop through the response from the server
		while(!feof($fp))
			$http_response .= fgets($fp, 1024);
		//close fp - we are done with it
		fclose($fp);
		return true;
	}
}

class wpphplist_sub_phplist {
	var $domain;
	var $lid;   // lid is the default PHPlist List ID to use 
	var $login;
	var $pass;
	var $skipConfirmationEmail;     // Set to 0 if you require a confirmation email to be sent  
	var $default_subscribed;
	var $subscriber_text;
	var $name_id;
	var $email_id;

	function wpphplist_sub_phplist ($domain,  $lid, $login, $pass, $skipConfirmationEmail, $email, $name, $chkboxtxt, $showchkbox) {
		$this->domain = $domain;
		$this->lid = $lid;
		$this->login = $login;
		$this->pass = $pass;
		if ($skipConfirmationEmail=='true') $this->skipConfirmationEmail = 1;
		else $this->skipConfirmationEmail = 0;
		if ($showchkbox=='true') $this->default_subscribed = 1;
		else $this->default_subscribed = 0;			
		$this->name_id = $name;
		$this->email_id = $email;
		$this->subscriber_text = $chkboxtxt;
	}

	function subscribe($input_data) {
		if (!wpphplist_check_curl()) {
				echo 'CURL library not detected on system.  Need to compile php with cURL in order to use this plug-in';
				return(0);
		}
		// $post_data = array();
		foreach ($input_data as $varname => $varvalue) {
			$post_data[$varname] = $varvalue;
		}
		// Ensure email is provided
		$email = $post_data[$this->email_id];
		//  $tmp = $_POST['lid'];
		//  if ($tmp != '') {$lid = $tmp; }	//user may override default list ID
		if ($email == '') {
			echo('You must supply an email address');
			return(0);
		}
		// 3) Login to phplist as admin and save cookie using CURLOPT_COOKIEFILE
		// NOTE: Must log in as admin in order to bypass email confirmation
		$url = $this->domain . "admin/";
		$ch=curl_init();
		if (curl_errno($ch)) {
	 		print '<h3 style="color:red">Init Error: ' . curl_error($ch) .' Errno: '. curl_errno($ch) . "</h3>";
			return(0);
		}

		$post_data='&htmlemail=1&list['.$this->lid.']=signup&subscribe=Subscribe&'.$this->email_id.'='.$email.'&'.$this->name_id.'='.$nameid;

		$URLParts=parse_url($this->domain);
		//echo var_dump($URLParts);
		http_post($URLParts['host'], $URLParts['path'].'?'.$URLParts['query'], $post_data, $response);
	}  // end of function
 
	function show_sub_checkbox()	{ 
 		$set_subscriber_chkbox = ($this->default_subscribed == 'true') ? ' ' : ' checked="checked"';
		echo <<<EOT
		<input type="checkbox" name="subscribe" id="subscribe" value="subscribe" style="width: auto;" {$set_subscriber_chkbox} />
		<label for="subscribe"><small>{$this->subscriber_text}</small></label>
EOT;
	}
	function add_subscriber($cid) {
 		global $wpdb;
		$id = (int) $id;

		$_POST['email']=$_POST['user_email'];
		foreach ($_POST as $varname => $varvalue) {
			if ($varname=='author') $varname=$this->name_id;
			$post_data[$varname] = $varvalue;
		}
		// If user wants to subscribe and is a valid email and not spam
		if (($_POST['subscribe'] == 'subscribe' && is_email($_POST[$this->email_id]))) {
			$this->subscribe($post_data);
		}
		return $cid;
	}
} //End Class

/* Main Entry Point */
$o = wpphplist_get_options();
$subscriber = new wpphplist_sub_phplist($o['php_list_uri'],  $o['php_list_listid'], $o['php_list_login'], $o['php_list_pass'],$o['php_list_skip_confirm'], $o['php_list_email_id'], $o['php_list_name_id'], $o['php_list_chkbx_txt'], $o['php_list_chkbx']);

function wpphplist_get_options(){
		$defaults = array();
		$defaults['php_list_uri'] = 'http://www.yourphplisturl.com/lists/';
		$defaults['php_list_listid'] = 'Enter List ID';
		$defaults['php_list_skip_confirm'] = '';
		$defaults['php_list_email_id']='email';
		$defaults['php_list_name_id']='attribute1';
		$defaults['php_list_chkbx_txt']= 'Check this box to subscribe to our newsletter.';
		$defaults['php_list_chkbx']='';
				
		$options = get_option('wpphplistCommentssettings');

		if (!is_array($options)){
			$options = $defaults;
			update_option('wpphplistCommentssettings', $options);
		}
		return $options;
	}

function wpphplist_check_curl() {
 if (function_exists('curl_exec')) return(1);
	else return (0);
}

function wpphplist_subpanel() {
//     if (isset($_POST['save_settings'])) {
//      	phplist_save_general_settings();
		//If the txt label isn't set, then set all the options to their default settings
		//if (strlen(get_option('php_list_txt_label')) ==0) {
		//phplist_save_default_form_settings();
	    //  }
//	  }

	if (isset($_POST['phplist'])) {
		update_option('wpphplistCommentssettings', $_POST['phplist']);
		$message = '<div class="updated"><p><strong>Options saved.</strong></p></div>';
	}

	if (!wpphplist_check_curl()) {
			echo 'CURL library not detected on system.  Need to compile php with cURL in order to use this plug-in';
			//return(0);
	}  
	$o = wpphplist_get_options();
	$skip_confirmation_chkbox = ($o['php_list_skip_confirm'] == 'true') ? ' checked="checked"' : '';
	$set_subscriber_chkbox = ($o['php_list_chkbx'] == 'true') ? ' checked="checked"' : '';

$URLParts=parse_url($o['php_list_uri']);

echo 'host: '.$URLParts['host'].' path: '.$URLParts['path'].'?'.$URLParts['query'];

  echo <<<EOT
    <div class="wrap">   
		{$message}
        <h2>General Settings</h2>
	<form method="post">
        <fieldset class="options">
	<table>
	<tr>
	 <td><p><strong><label for="php_list_uri">PHPList URL:</label></strong></td>
	 <td><input name="phplist[php_list_uri]" type="text" id="php_list_uri" value="{$o['php_list_uri']}" size="50" /> <em>Enter the URL of your subscribe page. I.e. http://www.yoursite.com/lists/?p=subscribe</em></p></td>
	</tr>
	<tr>
	 <td><p><strong><label for="php_list_listid">PHPList List ID</label></strong></td>
	 <td><input name="phplist[php_list_listid]" type="text" id="php_list_listid" value="{$o['php_list_listid']}" size="50" />
	 <em>Enter the Number of the list you want to subscribe users to. <a href="http://www.jesseheap.com/projects/wordpress-phplist-plugin.php#ListID"><strong>See 
	 help</strong></a> for more info.</em></p></td>
	</tr>
	<!--<tr>
	<td></td>
	<td><p><input name="phplist[php_list_skip_confirm]" type="checkbox" id="php_list_skip_confirm" value="true" {$skip_confirmation_chkbox}/>  <label for="php_list_skip_confirm"><strong>Skip Confirmation Email</strong> (Check to bypass confirmation email)</label></p></td>
	</tr>-->
	</table>
	</fieldset>
	<h2>Form Settings</h2>
<legend>Use these settings to ensure the comment email and name fields are
captured in the correct phplist fields.  <strong>See <a href="http://projects.jesseheap.com/all-projects/wordpress-plugin-phplist-comment-subscriber/#FAQ">FAQ</a> for help</strong></legend>
<fieldset class="form">
	<table>
	<tr> 
        <td width="20%">Email
        </td>
        <td><input name="phplist[php_list_email_id]" type="text" id="php_list_email_id" value="{$o['php_list_email_id']}" size="20" /> <label for="php_list_email_id"><em> PHPList ALWAYS uses email. This should not change</em></label>
        </td>
	</tr>
	<tr> 
        <td width="20%">Name
        </td>
        <td><input name="phplist[php_list_name_id]" type="text" id="php_list_name_id" value="{$o['php_list_name_id']}" size="20" /> <label for="php_list_name_id"><em> Enter the ID phplist uses to store your NAME Field. Leave this blank if you do not have one.</em></label>
        </td>
	</tr>
	</table>

	<table>
	<tr><td width="30%">
	<label for="php_list_chkbx_txt">Add Text for Subscriber Checkbox</label>	 
	<td> <p><input name="phplist[php_list_chkbx_txt]" type="text" id="php_list_chkbx_txt" value="{$o['php_list_chkbx_txt']}" size="50" /> </p></td>	 </td>
	</tr>
	<tr>
	<td colspan="2"><p><input name="phplist[php_list_chkbx]" type="checkbox" id="php_list_chkbx" value="true" {$set_subscriber_chkbox}/>  <label for="php_list_chkbx"><strong>Subscriber Checkbox Default </strong><em>If checked the subscriber checkbox will be checked by default</em></label></p></td>	 
	</tr>
	</table>
        </fieldset>
        <p><div class="submit">
	<input type="submit" name="save_settings" value="Update Options &raquo;" style="font-weight:bold;" /></div>	   
        </p>
        </form>
	</div>

	<div class="wrap">
	<h2>Information & Support</h2>
	<p><strong>Like this script?</strong> Show your support by linking to <a href="http://www.seanbluestone.com">our site</a> - www.seanbluestone.com.<br />
	If you <strong>really</strong> like it you might even consider <a href="http://www.seanbluestone.com/buy-sean-a-coffee">giving a small donation</a>.
	Note that this plugin was adapted from Jesse Heaps <a href="http://projects.jesseheap.com/all-projects/wordpress-plugin-phplist-comment-subscriber/">PHPlist Comment Subscriber</a> plugin and functions almost identically. That page has some useful information for configuring the settings and is worth checking out.
	</p>
	</div>
EOT;
}

function wpphplist_admin_menu() {
	if (function_exists('add_options_page')) {
		add_options_page('WordPress PHPList Dual Registration', 'PHPList Dual Reg', 8, basename(__FILE__), 'wpphplist_subpanel');
	}
}

/*Hooks */
add_action('admin_menu', 'wpphplist_admin_menu');
add_action('register_post', array(&$subscriber,'add_subscriber'));
add_action('register_form', array(&$subscriber, 'show_sub_checkbox'));
?>
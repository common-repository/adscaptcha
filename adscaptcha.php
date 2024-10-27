<?php
/*
Plugin Name: AdsCaptcha
Plugin URI: http://www.adscaptcha.com
Description: Why pay for CAPTCHAs when AdsCaptcha can make you money? AdsCaptcha provides high-level internet security, and you earn a share of every typed ad. Now that’s efficient!
Version: 1.1.0
Author: AdsCaptcha
Author URI: http://www.adscaptcha.com
*/

$ADSCAPTCHA_API = 'api.minteye.com';

$adscaptchaOptions = get_option('adscaptcha_options');

// -------------------------- //
//  Uninstall Plugin          //
// -------------------------- //

//function delete_preferences() {
    //delete_option('adscaptcha_options');
//}

register_deactivation_hook(__FILE__, 'delete_preferences');

// -------------------------- //
//  AdsCaptcha API functions  //
// -------------------------- //

function getCaptcha($captchaId, $publicKey) {
    global $ADSCAPTCHA_API;

	if (!empty($_SERVER['HTTPS']) && ("off" != $_SERVER['HTTPS'])) {
		$protocol = "https://";
	} else {
        $protocol = "http://";
    }
	$dummy = rand(1, 9999999999);
	$urlGet = $protocol . $ADSCAPTCHA_API . "/Get.aspx";
	$urlNoScript = $protocol . $ADSCAPTCHA_API . "/NoScript.aspx";
	$params = "?CaptchaId="  . $captchaId .
			  "&PublicKey=" . $publicKey .
			  "&Dummy=" . $dummy;

	$result  = "<script src='" . $urlGet . $params . "' type='text/javascript'></script>\n";
	$result .= "<noscript>\n";
	$result .= "\t<iframe src='" . $urlNoScript . $params . "' width='300' height='110' frameborder='0' marginheight='0' marginwidth='0' scrolling='no'></iframe>\n";
	$result .= "\t<table>\n";
	$result .= "\t<tr><td>Type challenge here:</td><td><input type='text' name='adscaptcha_response_field' value='' /></td></tr>\n";
	$result .= "\t<tr><td>Paste code here:</td><td><input type='text' name='adscaptcha_challenge_field' value='' /></td></tr>\n";
	$result .= "\t</table>\n";
	$result .= "</noscript>\n";

	return $result;
}

function ValidateCaptcha($captchaId, $privateKey) {
    global $ADSCAPTCHA_API, $_POST, $_SERVER;

	$host = $ADSCAPTCHA_API;
	$path = "/Validate.aspx";
	$data = "CaptchaId="      . $captchaId .
			  "&PrivateKey="    . $privateKey .
			  "&ChallengeCode=" . $_POST['adscaptcha_challenge_field'] .
			  "&UserResponse="  . $_POST['adscaptcha_response_field'] .
			  "&RemoteAddress=" . $_SERVER["REMOTE_ADDR"];

    $result = HttpPost($host, $path, $data);
	return $result;
}

function FixEncoding($str) {
	$curr_encoding = mb_detect_encoding($str) ;

	if($curr_encoding == "UTF-8" && mb_check_encoding($str,"UTF-8")) {
		return $str;
	} else {
		return utf8_encode($str);
	}
}

function HttpPost($host, $path, $data, $port = 80) {
	$data = FixEncoding($data);

	$http_request  = "POST $path HTTP/1.0\r\n";
	$http_request .= "Host: $host\r\n";
	$http_request .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$http_request .= "Content-Length: " . strlen($data) . "\r\n";
	$http_request .= "\r\n";
	$http_request .= $data;

	$response = '';
	if (($fs = @fsockopen($host, $port, $errno, $errstr, 10)) == false) {
		die ('Could not open socket! ' . $errstr);
	}

	fwrite($fs, $http_request);

	while (!feof($fs))
		$response .= fgets($fs, 1160);
	fclose($fs);

	$response = explode("\r\n\r\n", $response, 2);
	return $response[1];
}

// ------------------- //
//  Registration form  //
// ------------------- //

// Display the AdsCaptcha challenge on the registration form
function display_adscaptcha_registration() {
	global $adscaptchaOptions;

    $captchaId = ($adscaptchaOptions['adscaptcha_registration_captcha_id'] == null ? $adscaptchaOptions['adscaptcha_captcha_id'] : $adscaptchaOptions['adscaptcha_registration_captcha_id']);
    $publicKey = $adscaptchaOptions['adscaptcha_public_key'];

    echo(getCaptcha($captchaId, $publicKey));
}

// Check the AdsCaptcha challenge on the registration form
function validate_adscaptcha_registration($errors) {
    global $adscaptchaOptions, $ADSCAPTCHA_API;

    if (empty($_POST['adscaptcha_response_field'])) {
        $errors->add('error_blank', $adscaptchaOptions['adscaptcha_message_blank']);
        return $errors;
    }

    $captchaId  = ($adscaptchaOptions['adscaptcha_registration_captcha_id'] == null ? $adscaptchaOptions['adscaptcha_captcha_id'] : $adscaptchaOptions['adscaptcha_registration_captcha_id']);
	$privateKey = $adscaptchaOptions['adscaptcha_private_key'];

    $result = ValidateCaptcha($captchaId, $privateKey);

	if ($result != "true") {
		$errors->add('error_incorrect', $adscaptchaOptions['adscaptcha_message_incorrect']);
        return $errors;
    }

    return $errors;
}

// If registration CAPTCHA is enabled, hook it!
if ($adscaptchaOptions['adscaptcha_registration_enable']) {
    add_action('register_form', 'display_adscaptcha_registration');
    add_filter('registration_errors', 'validate_adscaptcha_registration');
}

// -------------- //
//  Comment form  //
// -------------- //

define ("ADSCAPTCHA_WP_HASH",  "c567eba78ca8798c89087230589a989");
$adscaptcha_error = '';

function adscaptcha_wp_hash($key)
{
    global $adscaptchaOptions;

    if (function_exists('wp_hash'))
        return wp_hash(ADSCAPTCHA_WP_HASH . $key);
    else
        return md5(ADSCAPTCHA_WP_HASH . $adscaptchaOptions['adscaptcha_private_key'] . $key);
}

// Display the AdsCaptcha challenge on the comment form
function display_adscaptcha_comment() {
   global $adscaptchaOptions;

    if (!$adscaptchaOptions['adscaptcha_comment_enable'])
        return;

    if (is_user_logged_in() && $adscaptchaOptions['adscaptcha_comment_hide']) {
       if (current_user_can($adscaptchaOptions['adscaptcha_comment_hide_permission_level'])) {
            return;
       }
    }

    if ($_GET['rerror'] == 'adscaptcha_message_blank')
        echo "<p>" . str_replace("\'", "'", $adscaptchaOptions['adscaptcha_message_blank']) . "</p>";

    if ($_GET['rerror'] == 'adscaptcha_message_incorrect')
        echo "<p>" . str_replace("\'", "'", $adscaptchaOptions['adscaptcha_message_incorrect']) . "</p>";

    $captchaId = ($adscaptchaOptions['adscaptcha_comment_captcha_id'] == null ? $adscaptchaOptions['adscaptcha_captcha_id'] : $adscaptchaOptions['adscaptcha_comment_captcha_id']);
    $publicKey = $adscaptchaOptions['adscaptcha_public_key'];

    echo(getCaptcha($captchaId, $publicKey));

    if ($adscaptchaOptions['adscaptcha_comment_rearrange']) {
        echo("<script type='text/javascript'>");
        echo("var oComment = document.getElementById('comment');");
        echo("var oParent = oComment.parentNode;");
        echo("var oCaptcha = document.getElementById('adscaptcha_widget');");
        echo("oParent.appendChild(oCaptcha, oComment);");
        echo("</script>");
    }
}

function validate_adscaptcha_comment($comment_data) {
    global $user_ID, $adscaptchaOptions, $adscaptcha_error;

    if (!$adscaptchaOptions['adscaptcha_comment_enable'])
        return $comment_data;

    if (is_user_logged_in() && $adscaptchaOptions['adscaptcha_comment_hide']) {
       if (current_user_can($adscaptchaOptions['adscaptcha_comment_hide_permission_level'])) {
            return $comment_data;
       }
    }

	if ($comment_data['comment_type'] == '') {
        if (empty($_POST['adscaptcha_response_field'])) {
            $adscaptcha_error = 'adscaptcha_message_blank';
            add_filter('pre_comment_approved', create_function('$a', 'return \'spam\';'));
            return $comment_data;
        }

        $captchaId = ($adscaptchaOptions['adscaptcha_comment_captcha_id'] == null ? $adscaptchaOptions['adscaptcha_captcha_id'] : $adscaptchaOptions['adscaptcha_comment_captcha_id']);
		$privateKey = $adscaptchaOptions['adscaptcha_private_key'];

		if (ValidateCaptcha($captchaId, $privateKey) == "true") {
			return $comment_data;
        } else {
            $adscaptcha_error = 'adscaptcha_message_incorrect';
            add_filter('pre_comment_approved', create_function('$a', 'return \'spam\';'));
            return $comment_data;
        }
    }

    return $comment_data;
}

function adscaptcha_comment_post_redirect($location, $comment) {
    global $adscaptcha_error;

    if($adscaptcha_error != '') {
        $location = substr($location, 0,strrpos($location, '#')) .
            ((strrpos($location, "?") === false) ? "?" : "&") .
            'rcommentid=' . $comment->comment_ID .
            '&rerror=' . $adscaptcha_error .
            '&rchash=' . adscaptcha_wp_hash ($comment->comment_ID).
            '#commentform';
    }
    return $location;
}

function adscaptcha_wp_saved_comment() {
   if (!is_single() && !is_page())
      return;

   if ($_GET['rcommentid'] && $_GET['rchash'] == adscaptcha_wp_hash ($_GET['rcommentid'])) {
      $comment = get_comment($_GET['rcommentid']);
      $com = preg_replace('/([\\/\(\)\+\;\'\"])/e','\'%\'.dechex(ord(\'$1\'))', $comment->comment_content);
      $com = preg_replace('/\\r\\n/m', '\\\n', $com);
      wp_delete_comment($comment->comment_ID);
   }
}

// If comment CAPTCHA is enabled, hook it!
if ($adscaptchaOptions['adscaptcha_comment_enable']) {
    add_action('comment_form', 'display_adscaptcha_comment');
    add_filter('wp_head', 'adscaptcha_wp_saved_comment', 0);
    add_filter('preprocess_comment', 'validate_adscaptcha_comment', 0);
    add_filter('comment_post_redirect', 'adscaptcha_comment_post_redirect', 0, 2);
}

// ---------------- //
//  Administration  //
// ---------------- //

function adscaptcha_permissions_dropdown($select_name, $checked_value="") {
	$permissions = array (
	 	'All registered users' => 'read',
	 	'Edit posts' => 'edit_posts',
	 	'Publish Posts' => 'publish_posts',
	 	'Moderate Comments' => 'moderate_comments',
	 	'Administer site' => 'level_10'
	 	);
	echo '<select name="' . $select_name . '" id="' . $select_name . '">';
	foreach ($permissions as $text => $value) :
		if ($value == $checked_value) $checked = ' selected="selected" ';
		echo '<option value="' . $value . '"' . $checked . ">$text</option>";
		$checked = NULL;
	endforeach;
	echo "</select>";
 }

// Add a link to the configuration options in the WordPress options menu
function add_adscaptcha_settings_page() {
	add_options_page('AdsCaptcha', 'AdsCaptcha', 8, __FILE__, 'adscaptcha_settings_page');
}

// Display AdsCaptcha settings page
function adscaptcha_settings_page() {
	$adscaptchaOptionsArray = array(
		'adscaptcha_public_key'	=> '',
		'adscaptcha_private_key' => '',
		'adscaptcha_captcha_id' => '',
        'adscaptcha_registration_enable' => true,
        'adscaptcha_registration_captcha_id' => '',
        'adscaptcha_comment_enable' => true,
        'adscaptcha_comment_captcha_id' => '',
        'adscaptcha_comment_hide' => true,
        'adscaptcha_comment_hide_permission_level' => 'read',
        'adscaptcha_comment_rearrange' => false,
        'adscaptcha_message_blank' => '<strong>ERROR</strong>: Please complete the CAPTCHA.',
        'adscaptcha_message_incorrect' => '<strong>ERROR</strong>: The CAPTCHA was incorrect.');

	add_option('adscaptcha_options', $adscaptchaOptionsArray);

	if (isset($_POST[ 'submit' ])) {
		$adscaptchaOptionsArray['adscaptcha_public_key'] = trim($_POST['adscaptcha_public_key']);
		$adscaptchaOptionsArray['adscaptcha_private_key'] = trim($_POST['adscaptcha_private_key']);
		$adscaptchaOptionsArray['adscaptcha_captcha_id'] = trim($_POST['adscaptcha_captcha_id']);
        $adscaptchaOptionsArray['adscaptcha_registration_enable'] = trim($_POST['adscaptcha_registration_enable']);
        $adscaptchaOptionsArray['adscaptcha_registration_captcha_id'] = trim($_POST['adscaptcha_registration_captcha_id']);
        $adscaptchaOptionsArray['adscaptcha_comment_enable'] = trim($_POST['adscaptcha_comment_enable']);
        $adscaptchaOptionsArray['adscaptcha_comment_captcha_id'] = trim($_POST['adscaptcha_comment_captcha_id']);
        $adscaptchaOptionsArray['adscaptcha_comment_hide'] = trim($_POST['adscaptcha_comment_hide']);
        $adscaptchaOptionsArray['adscaptcha_comment_hide_permission_level'] = trim($_POST['adscaptcha_comment_hide_permission_level']);
        $adscaptchaOptionsArray['adscaptcha_comment_rearrange'] = trim($_POST['adscaptcha_comment_rearrange']);
        $adscaptchaOptionsArray['adscaptcha_message_blank'] = trim(str_replace("\'", "'", $_POST['adscaptcha_message_blank']));
        $adscaptchaOptionsArray['adscaptcha_message_incorrect'] = trim(str_replace("\'", "'", $_POST['adscaptcha_message_incorrect']));

		update_option('adscaptcha_options', $adscaptchaOptionsArray);
	}

	$adscaptchaOptions = get_option('adscaptcha_options');
?>
<div class="wrap">

    <script type="text/javascript">
		function toggleHelp(id) {
			var e = document.getElementById(id);
			if(e.style.display == 'block')
				e.style.display = 'none';
			else
				e.style.display = 'block';
        }
    </script>

    <h2>AdsCaptcha Options</h2>

    <!--
    <p>
        <a href="http://wordpress.org/extend/plugins/adscaptcha/" target="_blank">Rate this</a> |
        <a href="http://wordpress.org/extend/plugins/adscaptcha/faq/" target="_blank">FAQ</a>
    </p>
    -->


    <h3>About AdsCaptcha</h3>
    <p>
    AdsCaptcha is a free CAPTCHA service that generates income while blocking spam on your website!<br/>
    For more details, visit the <a href="http://www.adscaptcha.com/">AdsCaptcha website</a>.
    </p>

    <form method="post" action="<?php echo $_SERVER[ 'PHP_SELF' ] . '?page=' . plugin_basename(__FILE__); ?>&updated=true">
	    <?php wp_nonce_field( 'update-options' ); ?>

        <h3>Options</h3>
        <table class="optiontable" cellspacing="10">
            <tr valign="top">
                <td width="200">Keys</td>
                <td>
                    AdsCaptcha requires an API keys. You can sign up for a <a href="http://www.adscaptcha.com/Publisher/" target="_blank">FREE AdsCaptcha keys</a>.<br/>
                    <table>
                        <tr>
                            <td>Captcha ID: </td><td><input type="text" id="adscaptcha_captcha_id" name="adscaptcha_captcha_id" size="6" maxlength="10" autocomplete="off" value="<?php echo $adscaptchaOptions['adscaptcha_captcha_id']; ?>" /></td>
                        </tr>
                        <tr>
                            <td>Public key: </td><td><input type="text" id="adscaptcha_public_key" name="adscaptcha_public_key" size="50" maxlength="100" autocomplete="off" value="<?php echo $adscaptchaOptions['adscaptcha_public_key']; ?>" /></td>
                        </tr>
                        <tr>
                            <td>Private key: </td><td><input type="text" id="adscaptcha_private_key" name="adscaptcha_private_key" size="50" maxlength="100" autocomplete="off" value="<?php echo $adscaptchaOptions['adscaptcha_private_key']; ?>" /></td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr valign="top">
                <td>Registration Options</td>
                <td>
                    <input type="checkbox" name="adscaptcha_registration_enable" id="adscaptcha_registration_enable" value="1" <?php if($adscaptchaOptions['adscaptcha_registration_enable'] == true){echo 'checked="checked"';} ?> />
                    <label name="adscaptcha_registration_enable" for="adscaptcha_registration_enable">Enable CAPTCHA on the registration form.</label><br/>
                    Captcha ID: <input type="text" name="adscaptcha_registration_captcha_id" size="6" maxlength="10" autocomplete="off" value="<?php echo $adscaptchaOptions['adscaptcha_registration_captcha_id']; ?>" /> <span style="font-size:80%;">(optional)</span>
                    <a style="cursor:pointer;font-size:80%;" title="Click for help!" onclick="toggleHelp('adscaptcha_registration_captcha_id_help');">Help</a><br/>
                    <div style="margin:5px 0 0 20px;font-size:85%;display:none;" id="adscaptcha_registration_captcha_id_help">
                        On your AdsCaptcha account, you may define several CAPTCHAs for the same website.<br/>
                        For example: An highly secure random CAPTCHA for your registration form and a "less tougher" one for your comments form.<br/>
                        If you want to use different CAPTCHA for your registration form, set its ID above.<br/>
                        Notice: If you leave it blank, the default CAPTCHA ID will be used.
                    </div>
                </td>
            </tr>
            <tr valign="top">
                <td>Comments Options</td>
                <td>
                    <input type="checkbox" name="adscaptcha_comment_enable" id="adscaptcha_comment_enable" value="1" <?php if($adscaptchaOptions['adscaptcha_comment_enable'] == true){echo 'checked="checked"';} ?> />
                    <label name="adscaptcha_comment_enable" for="adscaptcha_comment_enable">Enable CAPTCHA on the comment form.</label><br/>
                    Captcha ID: <input type="text" name="adscaptcha_comment_captcha_id" size="6" maxlength="10" autocomplete="off" value="<?php echo $adscaptchaOptions['adscaptcha_comment_captcha_id']; ?>" /> <span style="font-size:80%;">(optional)</span>
                        <a style="cursor:pointer;font-size:80%;" title="Click for help!" onclick="toggleHelp('adscaptcha_comment_captcha_id_help');">Help</a><br/>
                        <div style="margin:5px 0 0 20px;font-size:85%;display:none;" id="adscaptcha_comment_captcha_id_help">
                            On your AdsCaptcha account, you may define several CAPTCHAs for the same website.<br/>
                            For example: An highly secure random CAPTCHA for your registration form and a "less tougher" one for your comments form.<br/>
                            If you want to use different CAPTCHA for your comments form, set its ID above.<br/>
                            Notice: If you leave it blank, the default CAPTCHA ID will be used.
                        </div>
                    <input type="checkbox" name="adscaptcha_comment_hide" id="adscaptcha_comment_hide" value="1" <?php if($adscaptchaOptions['adscaptcha_comment_hide'] == true){echo 'checked="checked"';} ?> />
                    <label name="adscaptcha_comment_hide" for="adscaptcha_comment_hide">Hide CAPTCHA for <b>registered</b> users who can: </label><?php adscaptcha_permissions_dropdown('adscaptcha_comment_hide_permission_level',$adscaptchaOptions['adscaptcha_comment_hide_permission_level']); ?>
                    <a style="cursor:pointer;font-size:80%;" title="Click for help!" onclick="toggleHelp('adscaptcha_comment_hide_help');">Help</a><br/>
                    <div style="margin:5px 0 0 20px;font-size:85%;display:none;" id="adscaptcha_comment_hide_help">
                        The CAPTCHA might be annoying to highly active users.<br/>
                        You can decide whether to hide the CAPTCHA from users according to their permission level.
                    </div>
                    <input type="checkbox" name="adscaptcha_comment_rearrange" id="adscaptcha_comment_rearrange" value="1" <?php if($adscaptchaOptions['adscaptcha_comment_rearrange'] == true){echo 'checked="checked"';} ?> />
                    <label name="adscaptcha_comment_rearrange" for="adscaptcha_comment_rearrange">Rearrange CAPTCHA's position on the comment form automatically?</label>
                    <a style="cursor:pointer;font-size:80%;" title="Click for help!" onclick="toggleHelp('adscaptcha_comment_rearrange_help');">Help</a>
                    <div style="margin:5px 0 0 20px;font-size:85%;display:none;" id="adscaptcha_comment_rearrange_help">
                        Your CAPTCHA displays AFTER or ABOVE the submit button on the comment form?<br/>
                        If so, edit your current theme comments.php file and locate this line:<br/>
                        <font color="Blue">&lt;?</font><font color="Red">php</font> do_action('comment_form', $post->ID); <font color="Blue">?&gt;</font><br/>
                        Move this line to BEFORE the comment textarea, uncheck the option box above, and the problem should be fixed.<br/>
                        Alernately, you can just check this box and javascript will <b>attempt</b> to rearrange it for you. <b>This option is less recomended.</b>
                    </div>
                </td>
            </tr>
            <tr valign="top">
                <td>Error Messages</td>
                <td>
                    The following are the messages to display when the user does not enter a CAPTCHA response or enters the incorrect CAPTCHA response.<br/>
                    <table>
                        <tr>
                            <td>No response:</td><td><input type="text" name="adscaptcha_message_blank" size="80" autocomplete="off" value="<?php echo $adscaptchaOptions['adscaptcha_message_blank']; ?>" /></td>
                        </tr>
                        <tr>
                            <td>Incorrect answer:</td><td><input type="text" name="adscaptcha_message_incorrect" size="80" autocomplete="off" value="<?php echo $adscaptchaOptions['adscaptcha_message_incorrect']; ?>" /></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

	    <p class="submit">
	        <input type="submit" name="submit" value="<?php _e('Update Options &raquo;'); ?>" />
	    </p>
	    </form>
	</div>
<?php
}

// Hook the add_config_page function into WordPress
add_action( 'admin_menu', 'add_adscaptcha_settings_page' );

// Display a warning if the public and private keys are missing
if ( !($adscaptchaOptions['adscaptcha_public_key'] && $adscaptchaOptions['adscaptcha_private_key'] && $adscaptchaOptions['adscaptcha_captcha_id']) && !isset($_POST['submit']) ) {
	function adscaptcha_warning() {
		$path = plugin_basename(__FILE__);
		echo "<div id='error_incorrect' class='updated fade-ff0000'><p><strong>AdsCaptcha is not active</strong>. You must <a href='options-general.php?page=" . $path . "'>enter your AdsCaptcha API keys</a> for it to work.</p></div>";
	}
	add_action('admin_notices', 'adscaptcha_warning');
	return;
}
?>

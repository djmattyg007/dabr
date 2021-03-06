<?php

/*
Syntax is 
'Name|links,bodybg,bodyt,small,odd,even,replyodd,replyeven,menubg,menut,menua',
Assembled in theme_css()
*/

$GLOBALS["colour_schemes"] = array(
	0 => "Pretty In Pink|c06,fcd,623,c8a,fee,fde,ffa,dd9,c06,fee,fee",
	1 => "Ugly Orange|b50,ddd,111,555,fff,eee,ffa,dd9,e81,c40,fff",
	2 => "Touch Blue|138,ddd,111,555,fff,eee,ffa,dd9,138,fff,fff",
	5 => "#red|d12,ddd,111,555,fff,eee,ffa,dd9,c12,fff,fff",
);

menu_register(array(
	"settings" => array(
		"callback"	=> "settings_page",
	),
	"reset" => array(
		"hidden"	=> true,
		"callback"	=> "cookie_monster",
	),
));

function cookie_monster() {
	$cookies = array(
		"browser",
		"settings",
		"utc_offset",
		"search_favourite",
		"perPage",
		"USER_AUTH",
	);
	$duration = time() - 3600;
	foreach ($cookies as $cookie) {
		setcookie($cookie, null, $duration, "/");
		setcookie($cookie, null, $duration);
	}
	return theme("page", "Cookie Monster", "<p>The cookie monster has logged you out and cleared all settings. Try logging in again now.</p>");
}

function setting_fetch($setting, $default = null) {
	$settings = (array) unserialize(base64_decode($_COOKIE['settings']));
	if (array_key_exists($setting, $settings)) {
		return $settings[$setting];
	} else {
		return $default;
	}
}

function setcookie_year($name, $value) {
	$duration = time() + (3600 * 24 * 365);
	setcookie($name, $value, $duration, '/');
}

function settings_page($args) {
	if ($args[1] == "save") {
		$settings["browser"]		= $_POST["browser"];
		$settings["gwt"]			= $_POST["gwt"];
		$settings["colours"]		= $_POST["colours"];
		$settings["reverse"]		= $_POST["reverse"];
		$settings["timestamp"]		= $_POST["timestamp"];
		$settings["hide_inline"]	= $_POST["hide_inline"];
		$settings["hideNSFW"]		= $_POST["hideNSFW"];
		$settings["utc_offset"]		= (float) $_POST["utc_offset"];
		$settings["emoticons"]		= $_POST["emoticons"];
		$settings["lastDM"]			= $_POST["lastDM"];

		// Perform validation on the "tweets per page" value
		if (is_numeric($_POST["perPage"])) {
			if ($_POST["perPage"] < 10) {
				$settings["perPage"] = 10;
			} else if ($_POST["perPage"] > 200) {
				$settings["perPage"] = 200;
			} else {
				$settings["perPage"] = $_POST["perPage"];
			}
		} else {
			$settings["perPage"] = settings_fetch("perPage", 20);
		}

		// Save a user's oauth details to a MySQL table
		if (MYSQL_USERS == "ON" && $newpass = $_POST["newpassword"]) {
			user_is_authenticated();
			list($key, $secret) = explode("|", $GLOBALS["user"]["password"]);
			$sql = sprintf("REPLACE INTO user (username, oauth_key, oauth_secret, password) VALUES ('%s', '%s', '%s', MD5('%s'))",  mysql_escape_string(user_current_username()), mysql_escape_string($key), mysql_escape_string($secret), mysql_escape_string($newpass));
			mysql_query($sql);
		}
		
		setcookie_year("settings", base64_encode(serialize($settings)));
		twitter_refresh('');
	}

	$modes = array(
		"mobile" 	=> "Normal phone",
		"touch"		=> "Touch Screen",
		"bigtouch"	=> "Touch Screen Big Icons",
		"desktop"	=> "PC/Laptop",
		"text"		=> "Text only",
		"worksafe"	=> "Work Safe",
	);
	
	$gwt = array(
		"off" => "direct",
		"on"  => "via GWT",
	);
	
	$colour_schemes = array();
	foreach ($GLOBALS["colour_schemes"] as $id => $info) {
		list($name, $colours) = explode("|", $info);
		$colour_schemes[$id] = $name;
	}
	
	$utc_offset = setting_fetch("utc_offset", 0);
/* returning 401 as it calls http://api.twitter.com/1/users/show.json?screen_name= (no username???)	
	if (!$utc_offset) {
		$user = twitter_user_info();
		$utc_offset = $user->utc_offset;
	}
*/
	if ($utc_offset > 0) {
		$utc_offset = "+" . $utc_offset;
	}

	// Create settings form
	$content = '<form action="settings/save" method="post">';

	// Colour scheme dropdown
	$content .= '<p>Colour scheme:<br /><select name="colours">';
	$content .= theme('options', $colour_schemes, setting_fetch('colours', 0));
	$content .= '</select></p>';

	// Mode dropdown
	$content .= '<p>Mode:<br /><select name="browser">';
	$content .= theme('options', $modes, $GLOBALS['current_theme']);
	$content .= '</select></p>';
	
	$content .= '<p><label>Tweets Per Page:<br />';
	$content .= '<input type="textbox" name="perPage" size="3" value="'. setting_fetch('perPage', 20) . '" />';
	$content .= '</label><br /></p>';
	
	$content .= '<p>External links go:<br /><select name="gwt">';
	$content .= theme('options', $gwt, setting_fetch('gwt', $GLOBALS['current_theme'] == 'text' ? 'on' : 'off'));
	$content .= '</select><small><br />Google Web Transcoder (GWT) converts third-party sites into small, speedy pages suitable for older phones and people with less bandwidth.</small></p>';

	$content .= '<p><label><input type="checkbox" name="timestamp" value="yes" ' . (setting_fetch('timestamp') == 'yes' ? ' checked="checked" ' : '') . ' /> Show the timestamp ' . twitter_date('H:i') . ' instead of 25 sec ago</label></p>';
	$content .= '<p><label><input type="checkbox" name="lastDM" value="yes"' . (setting_fetch('lastDM') == 'yes' ? ' checked="checked"' : '') . ' /> Display last DM on compose page</label</p>';
	$content .= '<p><label><input type="checkbox" name="reverse" value="yes" ' . (setting_fetch('reverse') == 'yes' ? ' checked="checked" ' : '') . ' /> Attempt to reverse the conversation thread view.</label></p>';
	$content .= '<p><label><input type="checkbox" name="hide_inline" value="yes" ' . (setting_fetch('hide_inline') == 'yes' ? ' checked="checked" ' : '') . ' /> Hide inline media (eg TwitPic thumbnails)</label></p>';
	$content .= '<p><label><input type="checkbox" name="emoticons" value="on"' . (setting_fetch('emoticons') == 'on' ? ' checked="checked"' : '') . ' /> Use images for emoticons</label></p>';
	$content .= '<p><label><input type="checkbox" name="hideNSFW" value="yes" ' . (setting_fetch('hideNSFW') == 'yes' ? ' checked="checked" ' : '') . ' /> Hide images marked NSFW</label></p>';

	$content .= '<p><label>The time in UTC is currently ' . gmdate('H:i') . ', by using an offset of <input type="text" name="utc_offset" value="'. $utc_offset .'" size="3" /> we display the time as ' . twitter_date('H:i') . '.<br />Adjust this value if the time appears to be wrong.</label></p>';

	// Allow users to choose a Dabr password if accounts are enabled
	if (MYSQL_USERS == "ON" && user_is_authenticated()) {
		$content .= '<fieldset><legend>' . APP_NAME . ' account</legend><small>If you want to sign in to ' . APP_NAME . ' without going via Twitter.com in the future, create a password and we\'ll remember you.</small></p><p>Change ' . APP_NAME . ' password<br /><input type="password" name="newpassword" /><br /><small>Leave blank if you don\'t want to change it</small></fieldset>';
	}
	
	$content .= '<p><input type="submit" value="Save" /></p></form>';

	$content .= '<hr /><p>Visit <a href="reset">Reset</a> if things go horribly wrong - it will log you out and clear all settings.</p>';

	return theme("page", "Settings", $content);
}

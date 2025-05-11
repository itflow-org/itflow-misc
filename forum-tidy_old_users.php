<?php
# Tidying up old users (GDPR)

$dbhost = 'localhost';
$dbusername = 'forum_db';
$dbpassword = 'forum_db';
$database = 'forum_db';
$mysqli = mysqli_connect($dbhost, $dbusername, $dbpassword, $database) or die('Database Connection Failed');


# 1 - Grab 5 "Email unverified" users older than a month & delete them
#  This removes both users who never verified their email, and those initially inactive for over 2 years that didn't re-confirm their account
#  (Note: The userId provided in the auth header is required, but doesn't really do anything)
#  https://docs.flarum.org/it/2.x/rest-api/#api-keys

$users_sql = mysqli_query($mysqli, "SELECT * FROM `users` WHERE `is_email_confirmed` = 0 AND `last_seen_at` < DATE_SUB(NOW(),INTERVAL 1 MONTH) LIMIT 5");
while ($row = mysqli_fetch_array($users_sql)) {
	
	$user_id = intval($row['id']);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://forum.itflow.org/api/users/$user_id");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		"Authorization: Token API_TOKEN_HERE; userId=1",
		"Accept: application/json",
	]);
	$response = curl_exec($ch);
	$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);	
}

# 2 - Grab all inactive users for over two years: Reset their last_seen to now (so they don't get deleted for a month), unverify their email, send them a new email confirmation request
#  They will get around a month to re-confirm their account before its deleted

$users_sql = mysqli_query($mysqli, "SELECT * FROM `users` WHERE `is_email_confirmed` = 1 AND `last_seen_at` < DATE_SUB(NOW(),INTERVAL 26 MONTH)");
while ($row = mysqli_fetch_array($users_sql)) {
	
	$user_id = intval($row['id']);
	
	// Reset their activated/verified status
	mysqli_query($mysqli, "UPDATE users SET is_email_confirmed = 0, last_seen_at = NOW() WHERE id = $user_id");
	
	// Resend a confirmation email from the forum ("as them")
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://forum.itflow.org/api/users/$user_id/send-confirmation");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		"Authorization: Token API_TOKEN_HERE; userId=" . $user_id,
		"Accept: application/json",
	]);
	$response = curl_exec($ch);
	$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	
}

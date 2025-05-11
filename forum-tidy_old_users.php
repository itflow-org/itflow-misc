<?php
# Tidying up old users (deleting)

$dbhost = 'localhost';
$dbusername = 'forum_db';
$dbpassword = 'forum_db';
$database = 'forum_db';
$mysqli = mysqli_connect($dbhost, $dbusername, $dbpassword, $database) or die('Database Connection Failed');


# 1 - Grab 5 "Email unverified" users older than a month & delete them
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
		"Authorization: Token API_KEY_HERE; userId=1",
		"Accept: application/json",
	]);

	$response = curl_exec($ch);
	$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if ($http_status === 204) {
		echo "User deleted successfully.";
	} else {
		echo "Failed to delete user. HTTP Status Code: $http_status\n";
		echo "Response: $response";
	}
	
}

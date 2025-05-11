<?php
# Tidying up old discussions (locking/ip redaction)

$dbhost = 'localhost';
$dbusername = 'forum_db';
$dbpassword = 'forum_db';
$database = 'forum_db';
$mysqli = mysqli_connect($dbhost, $dbusername, $dbpassword, $database) or die('Database Connection Failed');

# 1 - Lock 'Completed' / Abandoned 'Support' discussions with a last post older a month:
mysqli_query($mysqli, "UPDATE discussions LEFT JOIN discussion_tag ON discussions.id = discussion_tag.discussion_id SET discussions.is_locked = 1 WHERE (discussion_tag.tag_id = 3 OR discussion_tag.tag_id = 4) AND discussions.last_posted_at < NOW() - INTERVAL 1 MONTH");
 
#2 - Lock General (Tech + ITFlow) discussions with no activity in 3 months
mysqli_query($mysqli, "UPDATE discussions LEFT JOIN discussion_tag ON discussions.id = discussion_tag.discussion_id SET discussions.is_locked = 1 WHERE (discussion_tag.tag_id = 1 OR discussion_tag.tag_id = 7) AND discussions.last_posted_at < NOW() - INTERVAL 3 MONTH");

#3 - Redact IP Addresses for posts older than three months (GDPR/data retention)
mysqli_query($mysqli, "UPDATE `posts` SET `ip_address` = 'ip-redacted' WHERE `ip_address` IS NOT NULL AND `created_at` < DATE_SUB(NOW(),INTERVAL 3 MONTH)");

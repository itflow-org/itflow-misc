<?php

/*
 * CUSTOM CRON - SEND EMAIL ALERTS FOR NEW TICKETS
 *  May add to ITFlow core at a later date
 */

// Populate the address to send notifications to:
$notification_email_address = 'someone@example.com';

require_once("config.php");
require_once("functions.php");

$sql_companies = mysqli_query($mysqli, "SELECT * FROM companies, settings WHERE companies.company_id = settings.company_id AND companies.company_id = 1");

$row = mysqli_fetch_array($sql_companies);

// Company Details
$company_name = $row['company_name'];
$company_phone = formatPhoneNumber($row['company_phone']);
$company_email = $row['company_email'];
$company_website = $row['company_website'];
$company_city = $row['company_city'];
$company_state = $row['company_state'];
$company_country = $row['company_country'];

// Company Settings
$config_enable_cron = intval($row['config_enable_cron']);
$config_cron_key = $row['config_cron_key'];
$config_smtp_host = $row['config_smtp_host'];
$config_smtp_username = $row['config_smtp_username'];
$config_smtp_password = $row['config_smtp_password'];
$config_smtp_port = intval($row['config_smtp_port']);
$config_smtp_encryption = $row['config_smtp_encryption'];
$config_mail_from_email = $row['config_mail_from_email'];
$config_mail_from_name = $row['config_mail_from_name'];

// Tickets
$config_ticket_prefix = $row['config_ticket_prefix'];
$config_ticket_from_name = $row['config_ticket_from_name'];
$config_ticket_from_email = $row['config_ticket_from_email'];
$config_ticket_client_general_notifications = intval($row['config_ticket_client_general_notifications']);

$argv = $_SERVER['argv'];

// Check cron is enabled
if ($config_enable_cron == 0) {
    exit("Cron: is not enabled -- Quitting..");
}

// Check Cron Key
if ($argv[1] !== $config_cron_key ) {
    exit("Cron: Key invalid  -- Quitting..");
}

// Check email notifications are enabled
if ($config_ticket_client_general_notifications == 0) {
    exit("Cron: General ticket notifications are not enabled.");
}

// Get any 'new'/non-updated tickets in the last 10 minutes
$sql_new_tickets = mysqli_query(
    $mysqli,
    "SELECT * FROM TICKETS 
    LEFT JOIN clients on ticket_client_id = client_id 
    WHERE ticket_created_at > NOW() - INTERVAL 10 MINUTE 
    AND ticket_updated_at IS NULL"
);

// Cycle through each ticket
while ($row = mysqli_fetch_array($sql_new_tickets)) {
    $ticket_id = intval($row['ticket_id']);
    $ticket_prefix = sanitizeInput($row['ticket_prefix']);
    $ticket_number = intval($row['ticket_number']);
    $ticket_subject = sanitizeInput($row['ticket_subject']);
    $ticket_details = sanitizeInput($row['ticket_details']);
    $ticket_status = sanitizeInput($row['ticket_status']);

    $client_id = intval($row['ticket_client_id']);
    $client_name = sanitizeInput($row['client_name']);

    // Notify

    $subject = "New ticket - $client_name - [$ticket_prefix$ticket_number] - $ticket_subject";
    $body    = "Hello! <br><br>A new ticket regarding \"$ticket_subject\" has been created and requires initial triage / review.<br><br>--------------------------------<br>$ticket_details<br>--------------------------------<br><br>Client: $client_name<br>Ticket: $ticket_prefix$ticket_number<br>Subject: $ticket_subject<br>Status: Open<br>Portal: https://$config_base_url/ticket.php?id=$ticket_id<br><br>~<br>$company_name<br>ITFlow<br>";

    $mail = sendSingleEmail(
        $config_smtp_host,
        $config_smtp_username,
        $config_smtp_password,
        $config_smtp_encryption,
        $config_smtp_port,
        $config_ticket_from_email,
        $config_ticket_from_name,
        $notification_email_address,
        $notification_email_address,
        $subject,
        $body
    );

    // Update the ticket_updated_at field to ensure we don't notify the same ticket twice
    mysqli_query($mysqli, "UPDATE tickets SET ticket_updated_at = NOW() WHERE ticket_id = $ticket_id LIMIT 1");
}

<?php
// this file defines site-specific constants and secrets

// used for e-mails, X-XRDS-Location header, endpoint location, referer checking etc.
$sitepath = "https://piratenid.janschejbal.de/"; // do not forget '/' at the end!

// Additional headers (like From/Reply-To) for system e-mails
$mailheaders = 'From: PiratenID <noreply@piratenpartei.de>'."\r\n".'Reply-To: PiratenID-Support <IDServer@it.piratenpartei.de>';

// List of domains that may receive extended attributes (NOTE: Code is currently disabled!)
$extendedAttributeRealms = array();

// IP address of the remote client (to allow trusted reverse proxies to provide the real client address).
$remoteClientIP = $_SERVER['REMOTE_ADDR'];
//$remoteClientIP = $_SERVER['HTTP_X_FORWARDED_FOR']; // use this if and only if working behind a trusted reverse proxy that sets the header (or in a similar setup)

// Database login data (if compromised, change. no DB access from outside should be possible, and user should have very limited rights)
function getDatabasePDO() {
	return new PDO('mysql:dbname=piratenid;host=127.0.0.1', "root", "");
}

// The pseudonym secret is used for pseudonym calculation.
// If changed, all pseudonyms will change.
// Provides additional security, but is not critical as long as the user secrets (in the database) stay secret.
// If only this secret is compromised, it does NOT need to be changed immediately.
// Disclosure of this secret AND the user secrets allows an attacker to link pseudonyms
// Suggested procedure in case of compromise:
//   Notify web services
//   Create a new attribute "old_pseudonym" calculated using the old values (which are kept under a different name)
//   Create new values for this secret and user secrets
$pseudonymsecret = "cfS5Ld1oVxfKbrtgFrHi"; 

// An additional salt used for hashing passwords.
// If changed, all password hashes become invalid!
// Provides some additional security, in particular, making it impossible to bruteforce the passwords if the attacker has only the database.
$passwordsaltsecret = "1nNEwuawyI0ZOn7WAt9u";

// Secret key for response HMAC. If compromised, replace with new random value and clear "openid" table.
// As every response is cross-verified using that table, even key compromise should not cause issues
$openid_hmacsecret = "9EU4rWIiRpfQDVtc2W5233Jz6rEUy7uTipRmwfhlkOLTKHC7djIszs5qqHxn";

?>
<?php
	require_once("piratenid.php");
	PiratenID::$imagepath =  "/";
	PiratenID::$realm     = "https://localhost.janschejbal.de/"; // Sicherheitsrelevant! Festen Wert vorgeben, keine Variablen wie $_SERVER nutzen!
		                                                         // (siehe http://blog.oncode.info/2008/05/07/php_self-ist-boese-potentielles-cross-site-scripting-xss/)
	PiratenID::$logouturl  =  "/example.php?piratenid_logout";   // Wenn die Realm-URL keinen Button enthält, muss eine Logout-URL angegeben werden, damit das Logout funktioniert.
	                                                             // Dabei kann entweder eine URL, auf der ein Button zu sehen ist, zusammen mit dem Parameter piratenid_logout
																 // angegeben werden, oder eine eigene Logout-URL. Siehe auch die Hinweise im Handbuch.
	PiratenID::$attributes =  "mitgliedschaft-bund,mitgliedschaft-land";
	$button = PiratenID::run(); // VOR allen anderen Ausgaben aufrufen, damit das Session-Cookie gesetzt werden kann!
?>
<html>
<head>
<title>PiratenID-Demo</title>
</head>
<body>
<!-- An prominenter Stelle den Button einbinden -->
<div style="float: right;"><?php echo $button; ?></div>
<h1>PiratenID-Demo</h1>
<p>Diese Seite demonstriert, wie das PiratenID-System funktioniert.</p>
<div>
<?php

	if ($_SESSION['piratenid_user']['authenticated']) {
		// Nutzer ist angemeldet.
		echo "Willkommen, ";
		// Wir haben "mitgliedschaft-bund" abgefragt. Daher können sich auch Nichtpiraten anmelden, aber wir können feststellen, ob jemand Pirat ist:
		if ($_SESSION['piratenid_user']['attributes']['mitgliedschaft-bund'] === "ja") {
			// Alle Ausgaben (außer dem Button) müssen escaped werden!
			echo "Pirat aus ". htmlentities($_SESSION['piratenid_user']['attributes']['mitgliedschaft-land']) ."!";
		} else {
			echo "Nichtpirat!";
		}
	} else {
		echo "Bitte oben anmelden!";
	}

?>
</div>
</body>
</html>
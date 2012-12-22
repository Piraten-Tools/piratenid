== Funktionsbeschreibung PiratenID Export/Import ==
Das Export-Skript auf einem Exportserver holt die Daten von einer Datenbank und konvertiert sie.
Anschlie�end sendet es sie verschl�sselt und authentifiziert per HTTP POST an das Import-Skript auf dem PiratenID-Server.
Das Importskript importiert die Daten in die PiratenID-Datenbank und erstellt optional eine Textdatei mit Statistiken.

Beide Skripte pr�fen die G�ltigkeit der Daten. Das Exportskript zeigt bei gelungenem Export Statistiken an.

Das Importskript meldet anschlie�end an das Importskript, welche Token nun g�ltig sind, und welche bereits verbraucht sind.
(Beachte: Nicht mehr g�ltige Token k�nnen verbraucht sein!)
Das Exportskript schreibt diese Informationen in eine separate Tabelle auf dem SAGE-Datenbankserver.
So erh�lt SAGE Zugriff auf diese Informationen und kann z. B. das Neuausstellen von verlorenen Token automatisieren.

=== Sicherheit ===
Die Daten werden ausschlie�lich �ber das gesicherte, interne Netzwerk �bertragen.
Die Token-Hashes werden vom Exportskript sortiert, um R�ckschl�sse aus der Reihenfolge zu verhindern.
Die �bertragenen Daten bestehen aus einem Token-Hash, Informationen �ber Verbandsmitgliedschaften und die Stimmberechtigung.
Sie enthalten keine personenbezogenen Angaben und m�ssen daher nicht besonders gesch�tzt werden.

Dennoch wird durch mehrere Ma�nahmen sichergestellt, dass die Daten nicht in fremde H�nde gelangen:
* Die Daten�bertragung erfolgt �ber ein internes, gesichertes und vertrauensw�rdes Netzwerk
* F�r die �bertragung wird SSL mit Clientauthentifizierung mit fest installierten Zertifikaten verwendet
* Die Daten, welche vom Export-Server zum PiratenID-Server gesendet werden, werden zus�tzlich symmetrisch verschl�sselt

Durch mehrere Ma�nahmen wird sichergestellt, dass keine verf�lschten Daten importiert werden k�nnen:
* Die Daten�bertragung erfolgt �ber ein internes, gesichertes und vertrauensw�rdes Netzwerk
* F�r die �bertragung wird SSL mit Clientauthentifizierung mit fest installierten Zertifikaten verwendet
* Nur der Export-Server kann auf das Importskript zugreifen.
  * Die Webserver-Konfiguration erlaubt nur Zugriffe von der IP des Export-Servers
  * Das Importskript pr�ft die IP erneut
* Ein Import erfolgt nur, wenn die Nachricht einen (konstanten) Authentifizierungssschl�ssel enth�lt
* Die Daten werden mit einem symmetrischen Schl�ssel integrit�tsgesichert
* Um Replay-Attacken zu verhindern, wird ein Timestamp mitsigniert.

Durch folgende Ma�nahmen wird sichergestellt, dass die R�ckmeldung vom ID-Server zum Export-Server nicht verf�lscht werden kann:
* Die Daten�bertragung erfolgt �ber ein internes, gesichertes und vertrauensw�rdes Netzwerk
* F�r die �bertragung wird SSL mit Clientauthentifizierung mit fest installierten Zertifikaten verwendet
  * die R�ckmeldung erfolgt �ber die selbe Verbindung, �ber welche auch die �bertragung erfolgt ist


=== Dateien ===
* README.txt ist diese Hilfedatei
* piratenid-verify.php enth�lt gemeinsame Routinen zur Datenpr�fung
* piratenid-import.php ist das Importskript
* piratenid-import-config.php ist die Konfigurationsdatei f�r das Importskript
* piratenid-export.php ist das Exportskript
* piratenid-export-config.php ist die Konfigurationsdatei f�r das Exportskript
* piratenid-mktoken.php ist KEIN Teil der Export/Import-Architektur, sondern dient zur Erstellung von Testtokens auf dem Testserver
* Das Verzeichnis cert-generator enth�lt ein Skript zur Erstellung von Zertifikaten (siehe "Konfiguration von SSL")

== Installationsanleitung f�r PiratenID Export/Import ==

=== Konfiguration von SSL ===
Sowohl f�r den Server als auch f�r den Client m�ssen Zertifikate erstellt werden.
Hierf�r beinhaltet das Verzeichnis "cert-generator" eine OpenSSL-Config und ein entsprechendes Skript.
ACHTUNG: Unter Windows ben�tigt dieses Skript korrekt installierte UnxUtils und OpenSSL!
Trotz der Dateiendung kann das Skript unter Linux auch als Shellskript benutzt werden!

Das Skript erstellt im Verzeichnis "output" folgende Dateien:
 * idserver.crt         - �ffentliches (Server-)Zertifikat f�r den Import-Endpoint. Wird auf dem Export-Server installiert.
 * idserver.key         - privater Schl�ssel (inkl. Zertifikat) f�r den Import-Endpoint. Wird NUR auf dem ID-Server installiert!
 * updater.crt          - �ffentliches Clientzertifikat f�r den Export-Server. Wird auf dem ID-Server installiert.
 * updater.key          - privater Schl�ssel (inkl. Zertifikat) f�r den Export-Server. Wird NUR auf dem Export-Server installert!

Alternativ k�nnen die Schl�ssel und Zertifikate nat�rlich mit den Befehlen aus dem Skript manuell auf den jeweiligen Hosts erstellt werden,
sodass die privaten Schl�ssel sich nie au�erhalb des jeweiligen Hosts aufhalten.
Die �ffentlichen Zertifikate m�ssen jeweils auf den anderen Host �bertragen werden.

Die privaten Schl�ssel sollten durch entsprechende Rechtevergabe gesch�tzt werden
  # ID-Server
  chmod 400 idserver.key
  chown root idserver.key
  # Export-Server
  chown 400 updater.key
  chown export-user updater.key

Auf dem ID-Server sind die Pfade zu Schl�ssel und Zertifikaten in der nginx.conf einzutragen (siehe unten).
Auf dem Update-Server sind die Pfade in der piratenid-export-config.php einzutragen (siehe Kommentare in der Datei).
 
=== Importseite (auf PiratenID-Server) ===

1. piratenid-verify.php, piratenid-import.php und piratenid-import-config.php in ein nicht �ffentlich zug�ngigliches Verzeichnis auf dem Server platzieren.
2. Datenbankzugang mit ausreichenden Rechten (nur SELECT, DELETE und INSERT nur auf die Tabelle "tokens", zus�tzlich SELECT auf token-Spalte in users) anlegen
3. (Optional) Verzeichnis f�r Statistiken anlegen, in welchem PHP schreiben darf, und per Statistikdatei per Alias �ffentlich lesbar machen:
----------------------------------------------------------------------------------------------------
		# Innerhalb der Server-Direktive fuer den OEFFENTLICHEN Teil des Servers!
		location /stats.txt {
			alias /srv/www/piratenid_test_import/stats/importstats.txt;
		}
----------------------------------------------------------------------------------------------------

4. piratenid-import-config.php anpassen
    * Neues Secret generieren und eintragen (dieses muss sp�ter auch in piratenid-export-config.php eingetragen werden)
    * IP, von welcher die Importe kommen, eintragen
    * DB-Zugangsdaten eintragen
	* Pfad zur Statistikdatei eintragen oder auf false setzen.

5. Nginx einrichten.
   Nur der Export-Server darf auf das Import-Skript zugreifen, und die Zugriffsm�glcihkeiten sind restriktiv zu vergeben.
   Es muss SSL mit Clientzertifikaten verwendet werden.
   Eine Beispielkonfiguration folgt, darin m�ssen Pfade und die allow-IP (IP des Export-Servers) angepasst werden:
----------------------------------------------------------------------------------------------------
	server { # HTTPS endpoint for imports
		listen 10443;
		ssl on;
		ssl_verify_client on;
		ssl_certificate /srv/www/piratenid_test_import/idserver.key;
		ssl_certificate_key /srv/www/piratenid_test_import/idserver.key;
		ssl_client_certificate /srv/www/piratenid_test_import/updater.crt;

		server_name idtest-import;
		access_log /var/log/nginx/piratenid_test_import-access.log;
		error_log /var/log/nginx/piratenid_test_import-error.log;
		root /dev/null;

		location /import {
			allow 10.20.1.34;
			deny all;

			include /etc/nginx/fastcgi_params;
			fastcgi_pass 127.0.0.1:9000;
			fastcgi_param SCRIPT_FILENAME /srv/www/piratenid_test_import/piratenid-import.php;
		}

		location / {
			deny all;
		}
	}
----------------------------------------------------------------------------------------------------

==== Troubleshooting ====
Wenn das Importskript nur eine wei�e Seite liefert, deutet das auf einen Internal Server Error hin -- Errorlog pr�fen!
H�ufigste Ursache: PDO (Datenbankzugriff) falsch konfiguriert.



=== Exportseite (auf einem Export-Server) ===
Der Export-Server ben�tigt Zugriff auf eine Datenbank, welche die Export-Daten bereitstellt und R�ckmeldungsdaten entgegennimmt.
Er muss HTTP-Zugang zum oben konfigurierten Import-Endpunkt haben.

F�r den Export sollte ein separater Datenbank-Benutzer angelegt werden, welcher nur auf die Export-Daten zugreifen und R�ckmeldungsdaten schreiben kann.
Der Export-Nutzer sollte nur vom Export-Server aus nutzbar sein, und der Export-Server sollte sich nur mit dem Export-Nutzer auf die DB zugreifen k�nnen.

Auf dem Export-Server muss eine aktuelle PHP-Version vorhanden sein, welche per PDO auf die Datenbank zugreifen kann.
Es muss somit f�r die verwendete Datenbank entweder ein PDO-Treiber vorhanden sein, oder ODBC muss korrekt konfiguriert sein.
Beim Zugriff auf eine MSSQL-Datenbank sollte ODBC verwendet werden.

Unter Ubuntu kann ODBC mit folgenden Befehlen eingerichtet werden:
    sudo apt-get install freetds-bin freetds-common tdsodbc odbcinst php5-odbc unixodbc
    sudo cp /usr/share/doc/freetds-common/examples/odbcinst.ini /etc/odbcinst.ini
(Falls ODBC auch aus Webanwendungen heraus genutzt werden soll, m�ssen noch der Webserver bzw. php-fastcgi neu gestartet werden.)
Anschlie�end kann mit folgenden Einstellungen gearbeitet werden:
  $SOURCEPDO = 'odbc:Driver=FreeTDS; Server=127.0.0.1; Port=1433; Database=datenbank; UID=benutzername; PWD=passwort';
  $SOURCEUSER = ''; // User und Passwort MUESSEN im PDO-String angegeben werden, Variablen bleiben leer!
  $SOURCEPASS = '';
IP und Port sind anzupassen, "datenbank", "benutzername" und "passwort" jeweils durch Datenbanknamen, Benutzername und Passwort zu ersetzen.
	
Auf dem Export-Server werden die Dateien piratenid-verify.php, piratenid-export.php und piratenid-export-config.php ben�tigt.
Die Konfiguration ist entsprechend anzupassen (gleiches Secret wie in der Import-Config).

Der Export/Import wird durch einfaches Ausf�hren des Skripts piratenid-export.php durchgef�hrt. Dies kann manuell oder automatisiert erfolgen.


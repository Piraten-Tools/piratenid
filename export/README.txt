== Funktionsbeschreibung PiratenID Export/Import ==
Das Export-Skript auf einem Exportserver holt die Daten von einer Datenbank und konvertiert sie.
Anschlie�end sendet es sie verschl�sselt und authentifiziert per HTTP POST an das Import-Skript auf dem PiratenID-Server.
Das Importskript importiert die Daten in die PiratenID-Datenbank und erstellt optional eine Textdatei mit Statistiken.

Beide Skripte pr�fen die G�ltigkeit der Daten. Das Exportskript zeigt bei gelungenem Export Statistiken an.

=== Sicherheit ===
Die Daten werden ausschlie�lich �ber das gesicherte, interne Netzwerk �bertragen.
Die Token-Hashes werden vom Exportskript sortiert, um R�ckschl�sse aus der Reihenfolge zu verhindern.
Die �bertragenen Daten bestehen aus einem Token-Hash, Informationen �ber Verbandsmitgliedschaften und die Stimmberechtigung.
Sie enthalten keine personenbezogenen Angaben und m�ssen daher nicht besonders gesch�tzt werden.

Dennoch wird durch mehrere Ma�nahmen sichergestellt, dass die Daten nicht in fremde H�nde gelangen:
* Die Daten�bertragung erfolgt �ber ein internes, gesichertes und vertrauensw�rdes Netzwerk
* Die Daten werden symmetrisch verschl�sselt

Durch mehrere Ma�nahmen wird sichergestellt, dass keine verf�lschten Daten importiert werden k�nnen:
* Die Daten�bertragung erfolgt �ber ein internes, gesichertes und vertrauensw�rdes Netzwerk
* Nur der Export-Server kann auf das Importskript zugreifen.
  * Die Webserver-Konfiguration erlaubt nur Zugriffe von der IP des Export-Servers
  * Das Importskript pr�ft die IP erneut
* Ein Import erfolgt nur, wenn die Nachricht einen (konstanten) Authentifizierungssschl�ssel enth�lt
* Die Daten werden mit einem symmetrischen Schl�ssel integrit�tsgesichert
* Um Replay-Attacken zu verhindern, wird ein Timestamp mitsigniert.

=== Dateien ===
* README.txt ist diese Hilfedatei
* piratenid-verify.php enth�lt gemeinsame Routinen zur Datenpr�fung
* piratenid-import.php ist das Importskript
* piratenid-import-config.php ist die Konfigurationsdatei f�r das Importskript
* piratenid-export.php ist das Exportskript
* piratenid-export-config.php ist die Konfigurationsdatei f�r das Exportskript
* piratenid-mktoken.php ist KEIN Teil der Export/Import-Architektur, sondern dient zur Erstellung von Testtokens auf dem Testserver


== Installationsanleitung f�r PiratenID Export/Import ==

=== Importseite (auf PiratenID-Server) ===

1. piratenid-verify.php, piratenid-import.php und piratenid-import-config.php in ein nicht �ffentlich zug�ngigliches Verzeichnis auf dem Server platzieren.
2. Datenbankzugang mit ausreichenden Rechten (nur DELETE und INSERT nur auf die Tabelle "tokens") anlegen
3. (Optional) Verzeichnis f�r Statistiken anlegen, in welchem PHP schreiben darf, und per Statistikdatei per Alias �ffentlich lesbar machen:
----------------------------------------------------------------------------------------------------
		# Innerhalb der Server-Direktive fuer den OEFFENTLICHEN Teil des Servers!
		location /stats.txt {
			alias /srv/www/piratenid_test_import/stats/importstats.txt;
		}
----------------------------------------------------------------------------------------------------

4. piratenid-import-config.php anpassen
    * DB-Zugangsdaten eintragen
    * IP, von welcher die Importe kommen, eintragen
    * Neues Secret generieren und eintragen (dieses muss sp�ter auch in piratenid-export-config.php eingetragen werden)

5. Nginx einrichten. Nur der Export-Server darf auf das Import-Skript zugreifen, und die Zugriffsm�glcihkeiten sind restriktiv zu vergeben.
   Eine Beispielkonfiguration folgt, darin m�ssen Pfade und die allow-IP (IP des Export-Servers) angepasst werden:
----------------------------------------------------------------------------------------------------
	server { # HTTP endpoint for imports
		listen 81;
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
Der Export-Server ben�tigt Zugriff auf eine Datenbank, welche die Export-Daten bereitstellt.
Er muss HTTP-Zugang zum oben konfigurierten Import-Endpunkt haben.

F�r den Export sollte ein separater Datenbank-Benutzer angelegt werden, welcher nur auf die Export-Daten zugreifen kann.
Der Export-Nutzer sollte nur vom Export-Server aus nutzbar sein, und der Export-Server sollte sich nur mit dem Export-Nutzer auf die DB zugreifen k�nnen.

Auf dem Export-Server muss eine aktuelle PHP-Version vorhanden sein, welche per PDO auf die Datenbank zugreifen kann.
Es muss somit f�r die verwendete Datenbank entweder ein PDO-Treiber vorhanden sein, oder ODBC muss korrekt konfiguriert sein.
Beim Zugriff auf eine MSSQL-Datenbank sollte ODBC verwendet werden.

Auf dem Export-Server werden die Dateien piratenid-verify.php, piratenid-export.php und piratenid-export-config.php ben�tigt.
Die Konfiguration ist entsprechend anzupassen (gleiches Secret wie in der Import-Config).

Der Export/Import wird durch einfaches Ausf�hren des Skripts piratenid-export.php durchgef�hrt. Dies kann manuell oder automatisiert erfolgen.


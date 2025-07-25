
## Hier sind einige Beispiel-User-Stories, die eine Organisation typischerweise in einem Vorstellungsgespräch zum Testen geben könnte:

- Als Benutzer möchte ich mich registrieren können, damit ich Zugang zur Plattform erhalte.
- Als Benutzer möchte ich mich einloggen können, um meine persönlichen Daten zu sehen.
- Als Administrator möchte ich Benutzerkonten deaktivieren können, um die Sicherheit zu gewährleisten.
- Als Kunde möchte ich Produkte in den Warenkorb legen, um sie später zu kaufen.
- Als Kunde möchte ich meine Bestellungen einsehen, um den Lieferstatus zu überprüfen.
- Als Support-Mitarbeiter möchte ich Tickets bearbeiten können, um Kundenanfragen zu lösen.
- Als Manager möchte ich Berichte exportieren können, um die Geschäftsentwicklung zu analysieren.
- Als Benutzer möchte ich mein Passwort zurücksetzen können, falls ich es vergessen habe.

## Hier sind Beispiele für Akzeptanzkriterien zu den genannten User Stories:

- **Registrierung:**  
  - Das Registrierungsformular muss Felder für E-Mail und Passwort enthalten.  
  - Nach erfolgreicher Registrierung erhält der Benutzer eine Bestätigungs-E-Mail.  
  - Fehlende oder ungültige Eingaben werden mit einer Fehlermeldung angezeigt.

- **Login:**  
  - Benutzer können sich mit gültigen Zugangsdaten einloggen.  
  - Bei falschen Zugangsdaten erscheint eine Fehlermeldung.  
  - Nach dem Login wird der Benutzer auf sein Dashboard weitergeleitet.

- **Benutzerkonto deaktivieren (Admin):**  
  - Administratoren sehen eine Liste aller Benutzerkonten.  
  - Nach Deaktivierung kann sich der Benutzer nicht mehr einloggen.  
  - Die Deaktivierung wird im System protokolliert.

- **Produkt in Warenkorb legen:**  
  - Produkte können über einen Button zum Warenkorb hinzugefügt werden.  
  - Der Warenkorb zeigt die aktuelle Anzahl und Summe der Produkte an.  
  - Produkte können wieder aus dem Warenkorb entfernt werden.

- **Bestellungen einsehen:**  
  - Benutzer sehen eine Liste ihrer Bestellungen mit Status und Datum.  
  - Details zu jeder Bestellung sind einsehbar.  
  - Stornierte Bestellungen werden entsprechend markiert.

- **Support-Ticket bearbeiten:**  
  - Support-Mitarbeiter können Tickets öffnen und den Status ändern.  
  - Änderungen am Ticket werden protokolliert.  
  - Der Kunde erhält eine Benachrichtigung bei Statusänderungen.

- **Berichte exportieren (Manager):**  
  - Berichte können als PDF oder CSV exportiert werden.  
  - Exportierte Berichte enthalten alle relevanten Daten.  
  - Der Export ist nur für Manager verfügbar.

- **Passwort zurücksetzen:**  
  - Benutzer können eine Passwort-Reset-Anfrage stellen.  
  - Nach der Anfrage wird eine E-Mail mit Reset-Link versendet.  
  - Das neue Passwort muss den Sicherheitsanforderungen entsprechen.

## Negative Akzeptanzkriterien beschreiben, was **nicht** passieren darf oder welche Fehlerfälle korrekt behandelt werden müssen. Hier einige Beispiele zu den genannten User Stories:

- **Registrierung:**  
  - Das Formular darf keine Registrierung zulassen, wenn Pflichtfelder fehlen.
  - Es darf keine Registrierung mit bereits verwendeter E-Mail möglich sein.
  - Schwache Passwörter werden abgelehnt.

- **Login:**  
  - Login mit falschen Zugangsdaten darf nicht erfolgreich sein.
  - Nach zu vielen Fehlversuchen wird der Zugang gesperrt.

- **Benutzerkonto deaktivieren (Admin):**  
  - Ein deaktivierter Benutzer darf sich nicht mehr einloggen.
  - Ein Admin kann sein eigenes Konto nicht deaktivieren.

- **Produkt in Warenkorb legen:**  
  - Produkte mit Lagerbestand 0 dürfen nicht hinzugefügt werden.
  - Der Warenkorb darf keine doppelten Einträge desselben Produkts enthalten.

- **Bestellungen einsehen:**  
  - Benutzer dürfen keine Bestellungen anderer Nutzer sehen.
  - Stornierte Bestellungen können nicht mehr bearbeitet werden.

- **Support-Ticket bearbeiten:**  
  - Tickets mit Status „geschlossen“ dürfen nicht mehr bearbeitet werden.
  - Support-Mitarbeiter ohne Berechtigung können keine Tickets ändern.

- **Berichte exportieren (Manager):**  
  - Nicht-Manager dürfen keine Berichte exportieren.
  - Export mit ungültigen Filterparametern wird abgelehnt.

- **Passwort zurücksetzen:**  
  - Passwort-Reset-Links dürfen nach Ablauf nicht mehr funktionieren.
  - Das neue Passwort darf nicht identisch mit dem alten sein.
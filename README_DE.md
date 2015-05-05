#Kurze Beschreibung der SYSTOPIA OSM-Extension:

Die OSM-Extension erweitert die in CiviCRM nativen Geocoding-Optionen.
Nativ können Koordinaten für Adressen von Yahoo oder Google abgerufen werden.
Mit der OSM-Extension können Koordinaten auch von Open-Street-Map bezogen werden.

Allgemeine Funktionsweise:
Die Funktionsweise entspricht den nativen Geo-Coding-Optionen:
*   Beim Anlegen oder Aktualisieren von Adressen wird ein Hook aufgerufen, der
    die zugehörigen Koordinaten entsprechend der Geocoding-Konfiguration von
    einem Server herunter lädt.
*   Außerdem steht ein "Scheduled Job" zur Verfügung, der das Ermitteln aller
    noch fehlenden Daten anstößt.

Allgemeine Anwendung:
 1.  OSM-Extension installieren und aktivieren.
 2.  Unter "Administer"->"System Settings"->"Mapping and Geocoding"
    für "Geocoding Provider" "OpenStreetMapCoding" auswählen.
   1.  Beim Anlegen oder Verändern von Adress-Daten werden die Koordinaten
        ermittelt und gespeichert.
   2.  Unter "Administer"->"System Settings"->"Scheduled Jobs"
        "Geocode and Parse Addresses" konfigurieren und aktivieren.
        Dieser Job ermittelt für alle Adressen die Koordinaten, sofern sie noch
        nicht existieren.
 4.  Möchte man generell vermeiden, dass bereits gesetzte Koordinaten vom
    Geocoding überschrieben werden, so kann für die Koordinaten die Option
    "Override automatic geocoding" aktiviert werden.

Umgang mit der API:
Fehlerhafte Adress-Daten werden nicht bereinigt. Der Api-Call wird mit den
Daten generiert, die in der Datenbank vorgefunden werden.
Fünf Fälle werden unterschieden:
 1.  Adresse ist unvollständig:
    Es fehlen Angaben für das Land, die Stadt oder die Straße.
    Um die Stadt zu identifizieren reicht in der Regel entweder der Name oder
    die Postleitzahl.
 2.  Die Antwort der API kann nicht verarbeitet werden.
 3.  Die Adresse kann vom Server nicht identifiziert werden.
 4.  Die Adresse kann vom Server nicht eindeuting aufgelöst werden.
    Es werden Daten für mehrere Orte zurückgegeben.
 5.  Die Adresse kann eindeutig aufgelöst werden.

Die OSM-Extension verhält sich wie folgt:
 1.  Die Koordinaten in CiviCRM werden auf NULL gesetzt.
 2.  Die Koordinaten in CiviCRM bleiben unangetastet.
 3.  Die Koordinaten in CiviCRM werden auf NULL gesetzt.
 4.  Es werden die Koordinaten des ersten zurückgegebenen Ortes verwendet.
 5.  Die Koordinaten des zurückgegebenen Ortes werden gespeichert.

Bekannte Probleme und Fehler:
Bei unsauberen Datenstand können viele Adressen nicht aufgelöst werden.
Darüber hinaus sind keine Fehler bekannt.

Vorsicht ist bei der Aktivierung des geplanten Jobs "Geocode and Parse Addresses". Dort werden alle noch nicht aufgelösten Adressen zum geokodieren geschickt. Das beinhaltet aber leider auch diejenigen Adressen, die beim letzten Mal fehlgeschlagen sind - was dazu führt, das diese fehlerhaften Adressen immer und immer wieder angefragt werden.

In der Regel ist dieser aber gar nicht notwendig; neu eingetragene Adressen und Adressänderungen werden automatisch neu aufgelöst. Daher reicht es meist, den Job "Geocode and Parse Addresses" nach Installation einmal manuell auszuführen und nicht als geplanten Job zu aktivieren.

Bei großen Datensets kann das manuelle Auslösen einen Tiemout verursachen - in diesen Fällen empfehlen wir, den Cronjob zu aktivieren und nach einem erfolgreichen Abschluss wieder zu deaktivieren.

Verbesserungsmöglichkeiten:
*   Aufbereitung der Adress-Daten vor jedem API-Call um eine höhere Erfolgs-
    Quote zu erlangen.
*   Adress-Daten anhand der Rückgabe-Werte des Servers vervollständigen.

Links:
*   Open-Street-Map-Project:   http://www.openstreetmap.org
*   Open-Street-Map-Server:    http://nominatim.openstreetmap.org/
*   Beschreibung der API:      http://wiki.openstreetmap.org/wiki/API_v0.6

<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for lang/de/auth_magiclink.php.
 *
 * @package   auth_magiclink
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */

$string['loginmethoddescription'] = 'Wir senden dir einen einmalig verwendbaren Anmeldelink per E-Mail.';
$string['loginintro'] = 'Du erhältst einen einmalig verwendbaren Magic Link. Wenn die Kontoerstellung erlaubt ist und noch kein Konto besteht, ergänzt du anschließend nur deinen Namen.';
$string['emailsent'] = 'Wenn du dich mit dieser Adresse anmelden kannst, erhältst du in Kürze einen Anmeldelink.';
$string['emailsubject'] = 'Dein Anmeldelink für {$a}';
$string['emailbody'] = 'Öffne diesen Link, um sich bei {$a->site} anzumelden:

{$a->url}

Der Link ist {$a->minutes} Minuten gültig und nur einmal verwendbar. Wenn du diese Anfrage nicht gestellt hast, ignorierst du diese E-Mail.';
$string['completeaccountintro'] = 'Die E-Mail-Adresse {$a} ist bestätigt. Für dein neues Konto fehlen nur noch dein Vor- und Nachname.';
$string['confirmsigninintro'] = 'Melde dich als {$a} an. Der Link wird erst mit deiner Bestätigung verwendet.';
$string['emailambiguous'] = 'Diese E-Mail-Adresse ist mehreren Konten zugeordnet. Bitte wende dich an den Support.';
$string['usernamecollision'] = 'Das Konto konnte nicht eindeutig angelegt werden. Bitte versuche es erneut.';
$string['showonloginpage_help'] = 'Ergänzt Magic Link auf der normalen Moodle-Anmeldeseite. Die üblichen Anmeldeverfahren bleiben verfügbar. Aktiviere Magic Link in der Moodle-Authentifizierung, um diese Anmeldung anzubieten.';
$string['authdisabled'] = 'Magic-Link-Anmeldung ist auf dieser Website deaktiviert. Verwende eine andere Anmeldemethode.';
$string['checkmailbox'] = 'Prüfe dein Postfach und den Spamordner. Öffne den Link in der E-Mail, um fortzufahren.';
$string['settingshelp_overview_article'] = '<p>Magic Link ermöglicht die Anmeldung über einen einmal verwendbaren Link per E-Mail. Aktiviere Magic Link in der Moodle-Authentifizierung, um diese Anmeldung anzubieten. Mit „Auf der Moodle-Anmeldeseite anzeigen“ ergänzt du den Einstieg auf der normalen Anmeldeseite.</p><p>Eine Person fordert den Link für ihre E-Mail-Adresse an und bestätigt die Anmeldung auf der verlinkten Seite. Das bloße Öffnen des E-Mail-Links verbraucht den Link nicht; erst die Bestätigung tut das. Ein ungenutzter Link läuft nach der hier gewählten „Gültigkeitsdauer“ ab.</p><p>Bestehende Konten behalten ihr bisheriges Anmeldeverfahren. Die normalen Moodle-Anmeldewege bleiben verfügbar. Nach erfolgreicher Anmeldung wird ein zulässiges lokales Rücksprungziel beibehalten.</p><p>Führe Moodle-Cron jede Minute aus, damit angefragte Anmeldelinks zeitnah versendet werden.</p>';
$string['settingshelp_allownewaccounts_article'] = '<p>Ist „Neue Konten erlauben“ eingeschaltet, können Personen ohne vorhandenes Konto nach Bestätigung ihrer E-Mail-Adresse Vor- und Nachname angeben. Erst dann wird das neue passwortlose Konto angelegt. Websiteweite Registrierungsbeschränkungen bleiben maßgeblich.</p><p>Neue Konten verwenden das Anmeldeverfahren Magic Link und erhalten dabei kein lokales Passwort. Vorhandene bestätigte Konten behalten ihre bisherige Authentifizierung; die Option stellt sie nicht auf Magic Link um.</p><p>Schalte die Option aus, wenn nur bereits angelegte Konten diese Anmeldealternative verwenden sollen. Für die normalen Moodle-Anmeldewege gelten weiterhin deren eigene Einstellungen.</p>';
$string['settingshelp_mfaemailconfirmation_article'] = '<p>Voraussetzung sind aktivierte Moodle-MFA und ein aktiver E-Mail-Faktor mit positiver Gewichtung. Nach einem erfolgreich eingelösten, einmal verwendbaren Magic Link wird nur der native E-Mail-Faktor für das tatsächlich angemeldete Konto bestätigt. Moodle muss dafür keinen zweiten E-Mail-Code versenden.</p><p>Die eingestellte Gewichtung bleibt wirksam. Reicht sie nicht für die gesamte MFA-Regel aus, verlangt Moodle die noch erforderlichen Faktoren. Widerrufene, gesperrte oder fehlgeschlagene E-Mail-Faktoren werden nicht durch diese Option freigegeben. Ohne aktive Moodle-MFA funktioniert die normale Magic-Link-Anmeldung weiter.</p><p>Diese Kombination erleichtert die Anmeldung, bietet aber keinen unabhängigen zweiten Nachweis gegen einen Zugriff auf das E-Mail-Postfach. Für besonders schützenswerte Konten sind Authenticator-App oder Sicherheitsschlüssel die stärkere Ergänzung. Lass „Website-Administrator/innen erlauben“ deshalb vorzugsweise ausgeschaltet.</p><p>Bei einem erfolgreichen Magic-Link-Kontowechsel wird vorheriger MFA-Sitzungszustand verworfen. Ungültige, abgelaufene oder schon verbrauchte Links können keine Anmeldung oder neue MFA-Bestätigung auslösen.</p>';
$string['settingshelp_trusteddevices_article'] = '<p>Wenn die Bestätigung des E-Mail-Faktors und der native Faktor für vertraute Geräte aktiv sind, kann die Magic-Link-Bestätigung eine freiwillige Geräteauswahl anbieten. Sie ist nicht vorausgewählt.</p><p>Erst wenn die gesamte MFA-Regel erfüllt ist, legt Moodle den nativen Gerätenachweis an. Dauer und nächtlicher Ablauf richten sich nach den Einstellungen des Moodle-Faktors; Magic Link führt dafür keine zweite eigene Vertrauensliste.</p><p>Verwende diese Auswahl nur auf einem Gerät, das du selbst kontrollierst. Auf gemeinsam genutzten oder öffentlichen Geräten lässt du sie ausgeschaltet.</p>';

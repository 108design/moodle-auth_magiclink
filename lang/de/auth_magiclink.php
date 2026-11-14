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
$string['pluginname'] = 'Magic-Link-Anmeldung';
$string['auth_description'] = 'Allgemeine passwortlose Moodle-Anmeldung und optionale Kontoerstellung über einen kurzlebigen E-Mail-Link.';
$string['auth_magiclinkdescription'] = $string['auth_description'];
$string['passwordlesslogin'] = 'Mit Magic Link anmelden';
$string['loginbutton'] = 'Magic Link per E-Mail erhalten';
$string['loginmethodheading'] = 'Ohne Passwort anmelden';
$string['loginmethoddescription'] = 'Wir senden Ihnen einen einmalig verwendbaren Anmeldelink per E-Mail.';
$string['loginintro'] = 'Sie erhalten einen einmalig verwendbaren Magic Link. Wenn die Kontoerstellung erlaubt ist und noch kein Konto besteht, ergänzen Sie anschließend nur Ihren Namen.';
$string['sendlink'] = 'Anmeldelink senden';
$string['standardlogin'] = 'Mit bestehendem Moodle-Zugang anmelden';
$string['emailsent'] = 'Wenn die Adresse verwendet werden kann, wurde ein Anmeldelink versendet. Bitte prüfen Sie auch den Spam-Ordner.';
$string['emailsubject'] = 'Ihr Anmeldelink für {$a}';
$string['emailbody'] = 'Öffnen Sie diesen Link, um sich bei {$a->site} anzumelden:' . "\n\n"
    . '{$a->url}' . "\n\n"
    . 'Der Link ist {$a->minutes} Minuten gültig und nur einmal verwendbar. Wenn Sie diese Anfrage nicht gestellt haben, ignorieren Sie diese E-Mail.';
$string['completeaccount'] = 'Konto vervollständigen';
$string['completeaccountintro'] = 'Die E-Mail-Adresse {$a} ist bestätigt. Für Ihr neues Konto fehlen nur noch Ihr Vor- und Nachname.';
$string['createaccountcontinue'] = 'Konto erstellen und fortfahren';
$string['confirmsignin'] = 'Anmeldung bestätigen';
$string['confirmsigninintro'] = 'Melden Sie sich als {$a} an. Der Link wird erst mit Ihrer Bestätigung verwendet.';
$string['signincontinue'] = 'Anmelden und fortfahren';
$string['tokeninvalid'] = 'Dieser Anmeldelink ist ungültig, abgelaufen oder wurde bereits verwendet.';
$string['emailambiguous'] = 'Diese E-Mail-Adresse ist mehreren Konten zugeordnet. Bitte wenden Sie sich an den Support.';
$string['accountunavailable'] = 'Dieses Konto kann nicht mit einem Magic Link angemeldet werden.';
$string['newaccountsdisabled'] = 'Die Kontoerstellung über Magic Links ist momentan nicht verfügbar.';
$string['namesrequired'] = 'Vor- und Nachname sind erforderlich.';
$string['usernamecollision'] = 'Das Konto konnte nicht eindeutig angelegt werden. Bitte versuchen Sie es erneut.';
$string['taskcleanuptokens'] = 'Abgelaufene E-Mail-Anmeldelinks löschen';
$string['showonloginpage'] = 'Auf der Moodle-Anmeldeseite anzeigen';
$string['showonloginpage_help'] = 'Ergänzt Magic Link auf der normalen Moodle-Anmeldeseite. Die üblichen Anmeldeverfahren bleiben verfügbar. Aktiviere Magic Link in der Moodle-Authentifizierung, um diese Anmeldung anzubieten.';
$string['showonloginpage_desc'] = 'Zeigt den Magic-Link-Einstieg zusätzlich zur normalen Moodle-Anmeldung an.';
$string['allownewaccounts'] = 'Neue Konten erlauben';
$string['allownewaccounts_help'] = 'Erlaubt nach bestätigtem E-Mail-Link ein neues Konto mit Vor- und Nachname. Websiteweite Registrierungsbeschränkungen gelten weiterhin.';
$string['allownewaccounts_desc'] = 'Erstellt nach bestätigter E-Mail-Adresse und Angabe von Vor- und Nachname ein neues passwortloses Moodle-Konto.';
$string['allowsiteadmins'] = 'Website-Administratoren erlauben';
$string['allowsiteadmins_desc'] = 'Erlaubt Website-Administratoren die Nutzung von Magic Link. Aus Sicherheitsgründen standardmäßig deaktiviert; die eingestellte MFA-Regel gilt weiterhin.';
$string['allowsiteadmins_help'] = 'Erlaubt Website-Administratoren die Anmeldung per Magic Link. Wir empfehlen, dies ausgeschaltet zu lassen, besonders bei der Bestätigung des MFA-E-Mail-Faktors durch Magic Link.';
$string['linklifetime'] = 'Gültigkeitsdauer';
$string['linklifetime_help'] = 'Ein ungenutzter Link läuft nach dieser Anzahl Minuten ab. Er ist nur einmal verwendbar und wird erst durch Absenden der Bestätigung verbraucht, nicht bereits durch Öffnen des E-Mail-Links.';
$string['mfaemailconfirmation'] = 'MFA-E-Mail-Faktor durch Magic Link bestätigen';
$string['mfaemailconfirmation_desc'] = 'Rechnet einen bestätigten Magic Link als E-Mail-Faktor an, damit Moodle keinen zweiten E-Mail-Code versendet.';
$string['mfaemailconfirmation_help'] = 'Ein eingelöster Magic Link bestätigt zugleich den E-Mail-Faktor, sodass kein zweiter E-Mail-Code nötig ist. Beide Nachweise beruhen auf demselben Postfach; weitere MFA-Anforderungen bleiben wirksam.';
$string['mfaadminrecommendation'] = 'Empfehlung: Bei Verwendung einer einzigen E-Mail „Website-Administratoren erlauben“ ausgeschaltet lassen. Administratorzugänge mit Passwort und Authenticator-App oder Sicherheitsschlüssel absichern.';
$string['settingshelp'] = 'Hilfe zu Magic Link';
$string['settingshelpmore'] = 'Mehr dazu im Hilfepanel';
$string['linklifetime_desc'] = 'Zeit in Minuten, nach der ein noch nicht verwendeter Magic Link abläuft.';
$string['privacy:metadata:auth_magiclink_token'] = 'Kurzlebige Magic Links und die dafür erforderliche E-Mail-Adresse.';

$string['authdisabled'] = 'Magic-Link-Anmeldung ist auf dieser Website deaktiviert. Verwenden Sie eine andere Anmeldemethode.';

$string['checkmailbox'] = 'Prüfe Dein Postfach und den Spamordner. Öffne den Link in der E-Mail, um fortzufahren.';
$string['useanotheremail'] = 'Andere E-Mail-Adresse verwenden';

$string['linkunavailable'] = 'Dieser Anmeldelink ist abgelaufen oder kann nicht verwendet werden. Bitte fordere einen neuen Link an.';
$string['requestnewlink'] = 'Neuen Anmeldelink anfordern';

$string['settingshelptopic'] = 'Thema';

$string['settingshelp_overview'] = 'Magic Link verwenden';

$string['settingshelp_overview_article'] = '<p>Magic Link ermöglicht die Anmeldung über einen einmal verwendbaren Link per E-Mail. Aktiviere Magic Link in der Moodle-Authentifizierung, um diese Anmeldung anzubieten. Mit „Auf der Moodle-Anmeldeseite anzeigen“ ergänzt Du den Einstieg auf der normalen Anmeldeseite.</p><p>Eine Person fordert den Link für ihre E-Mail-Adresse an und bestätigt die Anmeldung auf der verlinkten Seite. Das bloße Öffnen des E-Mail-Links verbraucht den Link nicht; erst die Bestätigung tut das. Ein ungenutzter Link läuft nach der hier gewählten „Gültigkeitsdauer“ ab.</p><p>Bestehende Konten behalten ihr bisheriges Anmeldeverfahren. Die normalen Moodle-Anmeldewege bleiben verfügbar. Nach erfolgreicher Anmeldung wird ein zulässiges lokales Rücksprungziel beibehalten.</p>';

$string['settingshelp_allownewaccounts'] = 'Neue Konten erlauben';

$string['settingshelp_allownewaccounts_article'] = '<p>Ist „Neue Konten erlauben“ eingeschaltet, können Personen ohne vorhandenes Konto nach Bestätigung ihrer E-Mail-Adresse Vor- und Nachname angeben. Erst dann wird das neue passwortlose Konto angelegt. Websiteweite Registrierungsbeschränkungen bleiben maßgeblich.</p><p>Neue Konten verwenden das Anmeldeverfahren Magic Link und erhalten dabei kein lokales Passwort. Vorhandene bestätigte Konten behalten ihre bisherige Authentifizierung; die Option stellt sie nicht auf Magic Link um.</p><p>Schalte die Option aus, wenn nur bereits angelegte Konten diese Anmeldealternative verwenden sollen. Für die normalen Moodle-Anmeldewege gelten weiterhin deren eigene Einstellungen.</p>';

$string['settingshelp_allowsiteadmins'] = 'Website-Administratoren erlauben';

$string['settingshelp_allowsiteadmins_article'] = '<p>Diese Option betrifft Website-Administratoren. Sie erteilt keine Administratorrechte und ersetzt keine MFA-Regel. Ausgeschaltet können Website-Administratoren Magic Link nicht für ihre Anmeldung verwenden.</p><p>Wir empfehlen, die Option ausgeschaltet zu lassen und einen funktionierenden Administratorzugang mit Passwort und Authenticator-App oder Sicherheitsschlüssel beizubehalten. Das gilt besonders bei „MFA-E-Mail-Faktor durch Magic Link bestätigen“: Dann ersetzt ein Zugriff auf das Postfach sowohl den Magic Link als auch die zusätzliche E-Mail-Bestätigung.</p><p>Die allgemeine MFA-Regel wird separat in der Website-Administration eingestellt. Diese Magic-Link-Einstellung schaltet MFA weder ein noch aus und richtet keine App oder keinen Schlüssel ein.</p>';

$string['settingshelp_mfaemailconfirmation'] = 'MFA-E-Mail-Faktor durch Magic Link bestätigen';

$string['settingshelp_mfaemailconfirmation_article'] = '<p>Voraussetzung sind aktivierte Moodle-MFA und ein aktiver E-Mail-Faktor mit positiver Gewichtung. Nach einem erfolgreich eingelösten, einmal verwendbaren Magic Link wird nur der native E-Mail-Faktor für das tatsächlich angemeldete Konto bestätigt. Moodle muss dafür keinen zweiten E-Mail-Code versenden.</p><p>Die eingestellte Gewichtung bleibt wirksam. Reicht sie nicht für die gesamte MFA-Regel aus, verlangt Moodle die noch erforderlichen Faktoren. Widerrufene, gesperrte oder fehlgeschlagene E-Mail-Faktoren werden nicht durch diese Option freigegeben. Ohne aktive Moodle-MFA funktioniert die normale Magic-Link-Anmeldung weiter.</p><p>Diese Kombination erleichtert die Anmeldung, bietet aber keinen unabhängigen zweiten Nachweis gegen einen Zugriff auf das E-Mail-Postfach. Für besonders schützenswerte Konten sind Authenticator-App oder Sicherheitsschlüssel die stärkere Ergänzung. Lasse „Website-Administratoren erlauben“ deshalb vorzugsweise ausgeschaltet.</p><p>Bei einem erfolgreichen Magic-Link-Kontowechsel wird vorheriger MFA-Sitzungszustand verworfen. Ungültige, abgelaufene oder schon verbrauchte Links können keine Anmeldung oder neue MFA-Bestätigung auslösen.</p>';

$string['settingshelp_trusteddevices'] = 'Vertraute Geräte';

$string['settingshelp_trusteddevices_article'] = '<p>Wenn die Bestätigung des E-Mail-Faktors und der native Faktor für vertraute Geräte aktiv sind, kann die Magic-Link-Bestätigung eine freiwillige Geräteauswahl anbieten. Sie ist nicht vorausgewählt.</p><p>Erst wenn die gesamte MFA-Regel erfüllt ist, legt Moodle den nativen Gerätenachweis an. Dauer und nächtlicher Ablauf richten sich nach den Einstellungen des Moodle-Faktors; Magic Link führt dafür keine zweite eigene Vertrauensliste.</p><p>Verwende diese Auswahl nur auf einem Gerät, das Du selbst kontrollierst. Auf gemeinsam genutzten oder öffentlichen Geräten lässt Du sie ausgeschaltet.</p>';

$string['contexthelptopics'] = 'Hilfethemen';


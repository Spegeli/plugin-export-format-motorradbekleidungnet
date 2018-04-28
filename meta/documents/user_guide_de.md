
# User Guide für das Elastic Export Motorradbekleidung.net Plugin

<div class="container-toc"></div>

## 1 Bei Motorradbekleidung.net registrieren

Auf dem Marktplatz Motorradbekleidung.net bieten Sie Ihre Artikel zum Verkauf an.

## 2 Das Format MotorradbekleidungNET-Plugin in plentymarkets einrichten

Um dieses Format nutzen zu können, benötigen Sie das Plugin Elastic Export.

Auf der Handbuchseite [Daten exportieren](https://knowledge.plentymarkets.com/basics/datenaustausch/daten-exportieren#30) werden allgemein die einzelnen Formateinstellungen beschrieben.

In der folgenden Tabelle finden Sie spezifische Hinweise zu den Einstellungen, Formateinstellungen und empfohlenen Artikelfiltern für das Format **MotorradbekleidungNET-Plugin**.

| **Einstellung**                                 | **Erläuterung**|
| :---                                            | :--- |                                            
| **Einstellungen**                               |
| Format                                          | Das Format **MotorradbekleidungNET-Plugin** wählen. |
| Bereitstellung                                  | Die Bereitstellung **URL** wählen. |
| Dateiname                                       | Der Dateiname muss auf **.csv** enden, damit Motorradbekleidung.net die Datei erfolgreich importieren kann. |
| **Artikelfilter**                               |
| Aktiv                                           | **Aktiv** wählen. |
| Märkte                                          | **Motorradbekleidung.net** wählen. |
| **Formateinstellungen**                         |
| Produkt-URL                                     | Diese Option ist für dieses Format nicht relevant. |
| URL-Parameter                                   | Diese Option ist für dieses Format nicht relevant. |
| Bild                                            | **Erstes Bild** wählen. |
| Bestandspuffer                                  | Der Bestandspuffer für Varianten mit der Beschränkung auf den Netto-Warenbestand. |
| Bestand für Varianten ohne Bestandsbeschränkung | Der Bestand für Varianten ohne Bestandsbeschränkung.|
| Bestand für Varianten ohne Bestandsführung      | Der Bestand für Varianten ohne Bestandsführung. |
| Versandkosten                                   | Diese Option ist für dieses Format nicht relevant. |
| MwSt.-Hinweis                                   | Diese Option ist für dieses Format nicht relevant. |
| Artikelverfügbarkeit überschreiben              | Diese Einstellung muss aktiviert sein, da Motorradbekleidung.net nur spezifische Werte akzeptiert, die hier eingetragen werden müssen. <br />Weitere Informationen dazu im Abschnitt **3 Übersicht der verfügbaren Spalten** bei **lieferzeit**. |
 
 ## 3 Übersicht der verfügbaren Spalten
 
| **Spaltenbezeichnung**   | **Erläuterung** |
| :---                     | :--- |
| id                       | **Pflichtfeld** bei Variantenartikeln <br />**Inhalt**: Die **Artikel-ID** des Artikels mit dem Präfix **#**. |
| variante_zu_id           | **Pflichtfeld** bei Variantenartikeln <br />**Inhalt**: Die **Artikel-ID** des Artikels mit dem Präfix **#**. |
| artikelnummer            | **Pflichtfeld** <br />**Beschränkung**: max. 30 Zeichen <br />**Inhalt**: Die SKU des Artikels. |
| produkt_bestellbar       | **Pflichtfeld** <br />**Inhalt**: **1**, wenn für die Variante Bestand vorhanden oder diese nicht auf den Netto-Warenbestand beschränkt ist, ansonsten **0**. |
| produktname              | **Pflichtfeld** <br />**Beschränkung**: max. 100 Zeichen <br />**Inhalt**: Entsprechend der Formateinstellung **Artikelname**. |
| hersteller               | **Beschränkung**: max. 30 Zeichen <br />**Inhalt**: Der **Name des Herstellers** des Artikels. Der **Externe Name** unter **Einstellungen » Artikel » Hersteller** wird bevorzugt, wenn vorhanden. |
| beschreibung             | **Pflichtfeld** <br />**Inhalt**: Entsprechend der Formateinstellung **Beschreibung**. |
| variante                 | **Pflichtfeld** bei Variantenartikeln <br />**Inhalt**: Die **Namen der Attribute**, die mit den Varianten verknüpft sind. |
| variantenwert            | **Pflichtfeld** bei Variantenartikeln <br />**Inhalt**: Die **Namen der Attributswerte**, die mit den Varianten verknüpft sind. |
| isbn_ean                 | **Beschränkung**: ISBN-10, ISBN-13, GTIN-13 <br />**Inhalt**: Entsprechend der Formateinstellung **Barcode**. |
| lagerbestand             | **Beschränkung**: 0 bis 9999 <br />**Inhalt**: Der **Netto-Warenbestand der Variante**. Bei Artikeln, die nicht auf den Netto-Warenbestand beschränkt sind, wird **999** übertragen. |
| preis                    | **Pflichtfeld** <br />**Beschränkung**: max. 5 Vorkommastellen <br />**Inhalt**: Der **Verkaufspreis** der Variante. Wenn der **UVP** in den Formateinstellungen aktiviert wurde und höher ist als der Verkaufspreis, wird dieser hier eingetragen. |
| grundpreis_inhalt        | **Beschränkung**: max. 3 Vor- und Nachkommastellen <br />**Inhalt**: Der **Inhalt** der Variante. |
| grundpreis_einheit       | **Beschränkung**: ml, l, kg, g, m, m², m³<br />**Inhalt**: Die **Einheit** des Inhalts der Variante. |
| reduzierter_preis        | **Beschränkung**: max. 5 Vorkommastellen <br />**Inhalt**: Wenn die Formateinstellung **UVP** und/oder **Angebotspreis** aktiviert wurde, steht hier der Verkaufspreis oder Angebotspreis. |
| bezug_reduzierter_preis  | **UVP** oder **VK**. Wird in Abhängigkeit der Spalte **preis** gefüllt. |
| mwst_klasse              | **Pflichtfeld** <br />**Beschränkung**: 1,2,3,4 (1 = 19%, 2 = 7%, 3 = 0%, 4 = 10,7%) <br />**Inhalt**: Wird entsprechend des Mehrwertsteuersatzes übersetzt. |
| bestandsverwaltung_aktiv | **Inhalt**: **1**, wenn die Variante auf den Netto-Warenbestand beschränkt ist, ansonsten **0**. |
| bild1                    | **Pflichtfeld**: <br />**Inhalt**: Erstes Bild der Variante. |
| bild2-10                 | **Inhalt**: Entsprechende Bilder der Variante. |
| kategorien               | **Pflichtfeld** <br />**Inhalt**: **Kategoriepfad der Standard-Kategorie** für den in den Formateinstellungen definierten **Mandanten**. |
| lieferzeit               | **Pflichtfeld** <br />**Beschränkung**: 0,3,5,7,10,15,20,30,40,50,60 <br />**Inhalt**: Übersetzung gemäß der Formateinstellung **Artikelverfügbarkeit überschreiben**. |
| tradoria_kategorie       | **Inhalt**: **Kategorieverknüpfung der Standard-Kategorie** für den in den Formateinstellungen definierten **Mandanten**. |
| sichtbar                 | **Inhalt**: Immer **1**. |
| free_var_1-20            | **Inhalt**: Steht für das **Freitextfeld**. |
| MPN                      | **Inhalt**: Das **Model** der Variante. |
| technical_data           | **Inhalt**: **Technische Daten** des Artikels. |
| energie_klassen_gruppe   | **Beschränkung**: Festgelegte Werte als Merkmalwert vorgegeben. <br />**Inhalt**: Wert wird über ein Merkmal vom Typ **Kein** verknüpft. Wert muss unter **Einstellungen » Artikel » Merkmale » Merkmal öffnen** über die Option **Motorradbekleidung.net-Merkmal** festgelegt werden. |
| energie_klasse           | **Beschränkung**: Festgelegte Werte als Merkmalwert vorgegeben. <br />**Inhalt**: Wert wird über ein Merkmal vom Typ **Kein** verknüpft. Wert muss unter **Einstellungen » Artikel » Merkmale » Merkmal öffnen** über die Option **Motorradbekleidung.net-Merkmal** festgelegt werden. |
| energie_klasse_bis       | **Beschränkung**: Festgelegte Werte als Merkmalwert vorgegeben. <br />**Inhalt**: Wert wird über ein Merkmal vom Typ **Kein** verknüpft. Wert muss unter **Einstellungen » Artikel » Merkmale » Merkmal öffnen** über die Option **Motorradbekleidung.net-Merkmal** festgelegt werden. |
| energie_klassen_bild     | **Inhalt**: Das Bild mit der Position gemäß der Formateinstellung **Bildposition der Energieetikette** wird als Energieetikette übertragen, falls vorhanden. |

## 4 Lizenz

Das gesamte Projekt unterliegt der GNU AFFERO GENERAL PUBLIC LICENSE – weitere Informationen finden Sie in der [LICENSE.md](https://github.com/plentymarkets/plugin-elastic-export-rakuten-de/blob/master/LICENSE.md).


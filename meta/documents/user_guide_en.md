
# Motorradbekleidung.net plugin user guide

<div class="container-toc"></div>

## 1 Registering with Motorradbekleidung.net

Items are sold on the market Motorradbekleidung.net.

## 2 Setting up the data format MotorradbekleidungNET-Plugin in plentymarkets

To use this format, you need the Elastic Export plugin.

Refer to the [Exporting data](https://knowledge.plentymarkets.com/en/basics/data-exchange/exporting-data#30) page of the manual for further details about the individual format settings.

The following table lists details for settings, format settings and recommended item filters for the format **MotorradbekleidungNET-Plugin**.

| **Setting**                                       | **Explanation**|
| :---                                              | :--- |                                            
| **Settings**                                      |
| Format                                            | Choose **MotorradbekleidungNET-Plugin**. |
| Provisioning                                      | Choose **URL**. |
| File name                                         | The file name must have the ending **.csv** for Motorradbekleidung.net to be able to import the file successfully. |
| **Item filter**                                   |
| Active                                            | Choose **active**. |
| Markets                                           | Choose **Motorradbekleidung.net**. |
| **Format settings**                               |
| Product URL                                       | This option does not affect this format. |
| URL parameter                                     | This option does not affect this format. |
| Image                                             | Choose **First image**. |
| Stockbuffer                                       | The stock buffer for variations with the limitation to the netto stock. |
| Stock for variations without stock limitation     | The stock for variations without stock limitation.|
| Stock for variations without stock administration | The stock for variations without stock administration. |
| Shipping costs                                    | This option does not affect this format. |
| VAT note .                                        | This option does not affect this format. |
| Overwrite item availability                       | This option has to be activated as Motorradbekleidung.net only accepts specific values which have to be set here. <br />Additional information is provided in **3 Overview of available columns** in the column **lieferzeit**. |
 
 ## 3 Overview of available columns
 
| **Column description**   | **Explanation** |
| :---                     | :--- |
| id                       | **Required** for variations <br />**Content**: The **item ID** of the item with the prefix **#**. |
| variante_zu_id           | **Required** for variations <br />**Content**: The **item ID** of the item with the prefix **#**. |
| artikelnummer            | **Required** <br />**Limitation**: max. 30 characters <br />**Content**: The **SKU** of the item. For parent rows the parent SKU will be exported. |
| produkt_bestellbar       | **Required** <br />**Content**: **1** if the variation has stock and is not limited to net stock. Otherwise **0**. |
| produktname              | **Required** <br />**Limitation**: max. 100 characters <br />**Content**: According to the format setting **Item name**. |
| hersteller               | **Limitation**: max. 30 characters <br />**Content**: The **name of the manufacturer** of the item. The **external name** within **Settings » Items » Manufacturer** will be preferred if existing. |
| beschreibung             | **Required** <br />**Content**: According to the format setting **Description**. |
| variante                 | **Required** for variations <br />**Content**: The **names of the attributes** linked to the item. |
| variantenwert            | **Required** for variations <br />**Content**: The **names of the attribute values** linked to the variation. |
| isbn_ean                 | **Limitation**: ISBN-10, ISBN-13, GTIN-13 <br />**Content**: According to the format setting **Barcode**. |
| lagerbestand             | **Limitation**: from 0 to 9999 <br />**Content**: The **net stock of the variation**. If the variation is not limited to net stock **999** will be set as value. |
| preis                    | **Required** <br />**Limitation**: max. 5 predecimals <br />**Content**: The **sales price** of the variation. If the **RRP** was activated in the format settings and is higher than the sales price the RRP will be used here. |
| grundpreis_inhalt        | **Limitation**: max. 3 predecimals and decimals <br />**Content**: The **content** of the variation. |
| grundpreis_einheit       | **Limitation**: ml, l, kg, g, m, m², m³<br />**Content**: The **unit** of the content of the variation. |
| reduzierter_preis        | **Limitation**: max. 5 predecimals <br />**Content**: If the format setting **RRP** and/or **offer price** was activated, the sales price or offer price will be used here. |
| bezug_reduzierter_preis  | **RRP** or **RP**. This will be set in dependency to the column **preis**. |
| mwst_klasse              | **Required** <br />**Limitation**: 1,2,3,4 (1 = 19%, 2 = 7%, 3 = 0%, 4 = 10,7%) <br />**Content**: Will be translated on basis of the vate rate. |
| bestandsverwaltung_aktiv | **Content**: **1**, if the variation is limited to net stock. Otherwise **0**. |
| bild1                    | **Required**: <br />**Content**: The first image of the variation. |
| bild2-10                 | **Content**: The corresponding images of the variation. |
| kategorien               | **Required** <br />**Content**: **Category path of the standard category** for the **client** configured in the format settings. |
| lieferzeit               | **Required** <br />**Limitation**: 0,3,5,7,10,15,20,30,40,50,60 <br />**Content**: Translation according to the format setting **Override item availability**. |
| tradoria_kategorie       | **Content**: The **category path of the default category** for the defined **client** in the default settings. |
| sichtbar                 | **Content**: Always set to **1**. |
| free_var_1-20            | **Content**: The corresponding **Free text field**. |
| MPN                      | **Content**: The **model** of the variations. |
| technical_data           | **Content**: The **technical data** of the item. |
| energie_klassen_gruppe   | **Limitation**: Fixed value as given for the property link. <br />**Content**: Value is set through a property of type **No**. The value has to be set in the menu **Settings » Item » Properties » Open property** through the option **Motorradbekleidung.net property**. |
| energie_klasse           | **Limitation**: Fixed value as given for the property link. <br />**Content**: Value is set through a property of type **No**. The value has to be set in the menu **Settings » Item » Properties » Open property** through the option **Motorradbekleidung.net property**. |
| energie_klasse_bis       | **Limitation**: Fixed value as given for the property link. <br />**Content**: Value is set through a property of type **No**. The value has to be set in the menu **Settings » Item » Properties » Open property** through the option **Motorradbekleidung.net property**. |
| energie_klassen_bild     | **Content**: The image with the position corresponding to the format settings **Image position of the energy efficiency label** if given. |

## 4 Licence

This project is licensed under the GNU AFFERO GENERAL PUBLIC LICENSE.- find further information in the [LICENSE.md](https://github.com/plentymarkets/plugin-elastic-export-rakuten-de/blob/master/LICENSE.md).

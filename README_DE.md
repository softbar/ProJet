# ProJet Module / IPS-868 Stripe

Diese Module erweitert das IPS-868 Stripe Module um folgende möglichkeiten
- Konfiguration
    - Dim Speed If greater than 0 then all changes in color, brightness or white value are executed as a Dim operation
- SetState(IpsInstanceID, true||false):
    - Ermöglicht das Ein/Ausschalten und speichert bzw läd die letzten RGB+W Werte
- SetLevel(IpsInstanceID, Helligkeit)	
    - Regulieren der RGB Helligkeit unter Berücksichtigung der eingestellten Farbe
- SetWhite(IpsInstanceID, Helligkeit)		
    - Regulieren der Helligkeit des "weiß" Kanals
- DimUp(IpsInstanceID)
    - Erhöht die RGB Helligkeit um 5 , wenn das Licht vorher aus ist wird der zuletzt gespeicherte RGB-W Wert als Grundlage benutzt
- DimDown(IpsInstanceID)
    - Veringert den DimLevel um 5 und speichert den letzten RGB-W Wert als Grundlage

Die folgenden Funktionen entsprechen dem IPS-868 Stripe

- SetRGBW(IpsInstanceID, Rot,Grün,Blau,Weiß)
- DimRGBW(IpsInstanceID, Rot,Zeit,Grün,Zeit,Blau,Zeit,Weiß,Zeit)
- RunProgram(IpsInstanceID, ProgramID)


Viel Spass ;-)
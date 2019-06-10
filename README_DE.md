# ProJet Module / IPS-868 Stripe

Diese Module erweitert das IPS-868 Stripe Module um folgende möglichkeiten
- Konfiguration
    - DimGeschwindigkeit 	Wenn größer als 0, werden alle Änderungen in Farbe, Helligkeit oder Weißwert als Dimmoperation ausgeführt
- PJX_SetState(IpsInstanceID, true||false):
    - Ermöglicht das Ein/Ausschalten und speichert bzw läd die letzten RGB+W Werte
- PJX_SetLevel(IpsInstanceID, Helligkeit)	
    - Regulieren der RGB Helligkeit unter Berücksichtigung der eingestellten Farbe
- PJX_SetWhite(IpsInstanceID, Helligkeit)		
    - Regulieren der Helligkeit des "weiß" Kanals
- PJX_DimUp(IpsInstanceID)
    - Erhöht die RGB Helligkeit um 5 , wenn das Licht vorher aus ist wird der zuletzt gespeicherte RGB-W Wert als Grundlage benutzt
- PJX_DimDown(IpsInstanceID)
    - Veringert den DimLevel um 5 und speichert den letzten RGB-W Wert als Grundlage

Die folgenden Funktionen entsprechen dem IPS-868 Stripe

- PJX_SetRGBW(IpsInstanceID, Rot,Grün,Blau,Weiß)
- PJX_DimRGBW(IpsInstanceID, Rot,Zeit,Grün,Zeit,Blau,Zeit,Weiß,Zeit)
- PJX_RunProgram(IpsInstanceID, ProgramID)


Viel Spass ;-)
# ProJet Module / IPS-868 Stripe

These modules extend the IPS-868 Stripe Module with the following options
- Configuration 
    - Dimming speed 	If greater than 0 then all changes of the color, brightness or the white value are executed as a Dim operation
- SetState (IpsInstanceID, true || false):
     - Allows switching on / off and saves or loads the last RGB + W values
- SetLevel (IpsInstanceID, Brightness)
     - Regulate the RGB brightness taking into account the set color
- SetWhite (IpsInstanceID, Brightness)
     - Regulate the brightness of the "white" channel
- DimUp (IpsInstanceID)
     - Increases the RGB brightness by 5. If the dim level is increased by 5 when the light is off, the last saved RGB-W value will be used as the basis
- DimDown (IpsInstanceID)
     - Decreases the DimLevel by 5 and saves the last RGB-W value as a basis

The following functions correspond to the IPS-868 Stripe
- SetRGBW (IpsInstanceID, Red, Green, Blue, White)
- DimRGBW (IpsInstanceID, Red, Time, Green, Time, Blue, Time, White, Time)
- RunProgram (IpsInstanceID, ProgramID)


Have fun ;-)
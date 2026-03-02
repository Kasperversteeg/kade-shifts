# Shifts

## Workflows

admin maakt gebruiker aan > e-mail versturen met link naar app > gebruiker voert aanvullende gegevens in > zodra alle benodigde info aanwezig is (zoals contract soort, uurtarief, geboorte datum, volledige naam, start datum) wordt er op basis van een template een contract aangemaakt. > Contract wordt opgeslagen als docx bestand en kan gedownload/geprint worden > getekende contract wordt ge-upload en beschikbaar voor zowel admin als desbetreffende gebruiker.

werknemer vult start, pauze en eindtijd per dag in > accoordeer flow wordt getriggered > eind van de maand acoordeert de werkgever uren en krijgt een overzicht van de uren per maand > kan uren totalen van alle werknemers downloaden

## Features

De huidige app zal twee delen bevatten, een gebruiker/werknemer kant waarbij deze uren in kan vullen en verwachte loon/ huidige contract gegevens kan inzien. De Admin/werkgever zal een complete HR dashboard hebben met een werknemer overzicht/ plannings tool/ uren accoordatie flow/

### Gebruikers/werknemers:

- Dashboard
    - uren invoeren
    - uur tarief zien
    - potentieel loon
- Gewerkte uren overzicht
    - per maand overzicht

### Admin gedeelte/werkgever:

- Collega's/Gebruikers overzicht
    - uurtarief/ in dienst vanaf/
- Gewerkte Uren overzicht per maand
    - exporteer naar csv (of kopier naar clipboard?)
- Acoorderen flow scherm
    - Gewerkte uren acoorderen
- plan module
    - weekoverzicht
    - werknemers in plannen -> drag and drop horizontal flow

## RULES

- Use spatie permissions for all controllers, there should be no permission bugs
- Responsive, work on both mobile and desktop
- No hour entity should be hard deleted
- No user entity should be hard deleted
- Seeders schrijven voor handmatige tests
- workflow voor uren model

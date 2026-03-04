# Shifts

## Workflows

admin maakt gebruiker aan > e-mail versturen met link naar app > gebruiker voert aanvullende gegevens in > zodra alle benodigde info aanwezig is (zoals contract soort, uurtarief, geboorte datum, volledige naam, start datum) wordt er op basis van een template een contract aangemaakt. > Contract wordt opgeslagen als docx bestand en kan gedownload/geprint worden > getekende contract wordt ge-upload en beschikbaar voor zowel admin als desbetreffende gebruiker.

werknemer vult start, pauze en eindtijd per dag in > accoordeer flow wordt getriggered > eind van de maand acoordeert de werkgever uren en krijgt een overzicht van de uren per maand > kan uren totalen van alle werknemers downloaden

## RULES

- Use spatie permissions for all controllers, there should be no permission bugs
- Responsive, work on both mobile and desktop
- No hour entity should be hard deleted
- No user entity should be hard deleted
- Seeders schrijven voor handmatige tests
- workflow voor uren model

# Userstories

## gebruiker/werknemer

dash

- als gebruiker wil ik in kunnen loggen
- als gebruiker wil ik een nieuw wachtwoord aan kunnen vragen als ik het vergeten ben
- als gebruiker wil ik bij na het inloggen een dashboard zien
- als gebruiker wil ik via het dashboard gelijk mijn gewerkte uren van de dag in kunnen vullen
- als gebruiker wil ik mijn eerst komende dienst zien op het dashboard
- als gebruiker wil ik mijn "toekomstige" maandsalaris alvast zien (uitgerekend gebaseerd op een formule)

uren

- als gebruiker wil ik gebaseerd op de planning uren in kunnen vullen
- als gebruiker wil naast de planning ook makkelijk uren in kunnen vullen
- als gebruiker wil ik mijn ingevoerde uren per maand kunnen zien
- als gebruiker moet het niet meer mogelijk zijn om ge-accoordeerde uren aan te passen

planning

- als gebruiker wil ik mijn planning per week kunnen zien
- als gebruiker wil ik duidelijk zien wanneer ik weer moet werken

gegevens

- als gebruiker moet ik de voor en achterkant van mijn ID kaart o.i.d. kunnen uploaden (of dmv camera direct)
- als gebruiker wil ik graag mijn eigen gegevens aan kunnen passen

## admin/werkgever

dash

- als admin is het uren overzicht mijn dashboard

werknemers

- als admin wil ik een overzicht van alle werknemers
- als admin wil ik een detail overzicht van alle gegevens van werknemers
- als admin wil ik een alle documenten die gelinked zijn aan een werknemer zien in het detail overzicht
- als admin wil ik een nieuwe werknemer aanmaken en uitnodigen voor het platform
- als admin wil ik een uitnodiging opnieuw kunnen versturen
- als admin wil ik dat er op basis van de ingevulde gegevens een contract gegenereerd wordt (zodra alle gegevens ingevuld zijn)
- als admin wil ik de gegevens van werknemers kunnen aanpassen die geen invloed hebben op hun login flow
- als admin wil ik op de hoogte gehouden worden van de contract einddatums en twee en één maand van te voren een mail/herinnering krijgen dat het contract binnenkort verloopt

uren

- als admin wil ik een overzicht van de gewerkte uren per maand
- als admin wil ik per maand zien wat de kosten zijn
- als admin wil ik gewerkte uren kunnen accoorderen
- als admin wil ik gewerkte uren aan kunnen passen als dat nodig is

planning

- als admin wil ik een planning kunnen maken op basis van de werknemers die er zijn
- als admin wil ik een dienst toe kunnen voegen
- als admin wil ik een drag and drop week overzicht waarbij ik makkelijk een planning kan maken
- als admin wil ik een drag and drop dag overzicht waarbij ik makkelijk een planning kan maken

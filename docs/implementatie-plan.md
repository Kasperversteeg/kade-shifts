# Implementatieplan Kade Shifts v1

> Stappenplan voor de eerste lancering onder personeel.
> Fasen zijn geordend op afhankelijkheden — elke fase bouwt voort op de vorige.

---

## Huidige staat (MVP)

Wat al gebouwd is:

- Uren invoer (start, einde, pauze, notities) met cross-midnight berekening
- Dashboard met quick-add formulier
- Maandoverzicht per gebruiker met maandnavigatie
- Admin overzicht van alle werknemers per maand
- CSV + PDF export
- Uitnodigingssysteem met tokens (7 dagen geldig)
- Maandrapport e-mail (queued)
- PWA (installeerbaar op telefoon)
- Google OAuth login
- Meertalig (NL/EN)
- Responsive design (mobiel + desktop)

---

## Fase 1: Fundament

> Alles hierna bouwt hierop voort. Moet eerst afgerond worden.

### 1.1 Spatie Permissions

- Installeer `spatie/laravel-permission`
- Vervang huidige enum-role (`user`/`admin`) door Spatie rollen
- Rollen: `admin`, `user`
- Permissions: `manage-users`, `approve-hours`, `manage-planning`, `manage-invitations`, `view-all-hours`
- Vervang custom `AdminMiddleware` door Spatie middleware
- Alle controllers beveiligen via permissions (geen permission bugs)

### 1.2 Soft Deletes

- `SoftDeletes` trait toevoegen aan `User` en `TimeEntry` models
- Migrations: `deleted_at` kolom op `users` en `time_entries` tabellen
- Bestaande delete-logica aanpassen (geen hard deletes meer)
- Admin kan gearchiveerde records nog inzien

### 1.3 Uitgebreid Gebruikersprofiel

Nieuwe velden op `users` tabel:

| Veld | Type | Toelichting |
|---|---|---|
| `hourly_rate` | decimal(5,2) | Uurtarief |
| `contract_type` | enum | vast / flex / oproep |
| `contract_start_date` | date | Startdatum contract |
| `contract_end_date` | date, nullable | Einddatum contract (nullable voor vast) |
| `birth_date` | date | Geboortedatum |
| `start_date` | date | Datum in dienst |
| `bsn` | string, encrypted | BSN (versleuteld opgeslagen) |
| `phone` | string, nullable | Telefoonnummer |
| `address` | string, nullable | Adres |
| `city` | string, nullable | Woonplaats |
| `postal_code` | string, nullable | Postcode |
| `contract_expiry_notified_at` | timestamp, nullable | Bijhouden of notificatie al verstuurd is |

- Admin kan deze gegevens bewerken (behalve login-gerelateerde velden)
- Profiel-completeness check (welke velden zijn nog niet ingevuld)
- Werknemer kan eigen gegevens aanpassen (naam, telefoon, adres)

### 1.4 UX Basis

- Toast-notificaties (success/error feedback na elke actie)
- Bevestigingsdialogen bij destructieve acties (verwijderen, etc.)
- Empty states met helpende tekst (wanneer er geen data is)
- Loading states op formulieren (disabled button + spinner tijdens submit)

---

## Fase 2: Accordeerflow

> Kernfeature — maakt uren betrouwbaar voor de boekhouder.

### 2.1 Workflow Status op TimeEntry

Nieuw veld: `status` enum op `time_entries` tabel.

Statussen: `draft` → `submitted` → `approved` / `rejected`

| Actie | Wie | Resultaat |
|---|---|---|
| Uren invoeren | Werknemer | Status = `draft` |
| "Indienen" klikken | Werknemer | Status = `submitted` |
| Uren goedkeuren | Admin | Status = `approved` (locked voor werknemer) |
| Uren afwijzen | Admin | Status = `rejected` (met reden, werknemer kan aanpassen en opnieuw indienen) |

- `approved` uren zijn locked — werknemer kan niet meer aanpassen
- Admin kan altijd aanpassen (ook goedgekeurde uren)
- `rejected` uren gaan terug naar `draft` zodat werknemer kan corrigeren

### 2.2 Admin Accordeeroverzicht

- Per werknemer per maand: totaal uren, status overzicht, goedkeur-knop
- Bulk-accorderen: alle uren van een werknemer in een maand in een keer goedkeuren
- Filter op status (openstaand / goedgekeurd / afgewezen)
- Visueel onderscheid tussen statussen (kleuren/badges)

### 2.3 Export Aanpassen

- CSV/PDF export toont status per entry
- Optie om alleen `approved` uren te exporteren (voor boekhouder)

---

## Fase 3: ATW Compliance (Arbeidstijdenwet)

> Zachte waarschuwingen — niet blokkeren, wel duidelijk zichtbaar.
> De Arbeidsinspectie kan boetes tot EUR 10.000 per werknemer per overtreding opleggen.

### 3.1 Pauzevalidatie

- Waarschuwing bij shift >5,5 uur en pauze <30 minuten
- Waarschuwing bij shift >10 uur en pauze <45 minuten
- Tonen bij invoer (inline warning) en in admin overzicht

### 3.2 Dienst-limieten

- Waarschuwing bij shift >12 uur (ATW maximum)
- Waarschuwing bij <11 uur rust tussen twee opeenvolgende diensten (minimum rusttijd)
- Controle op basis van vorige/volgende entry van dezelfde werknemer

### 3.3 Week- en Maandoverzicht met Limieten

- Weektotalen tonen naast maandtotalen
- Waarschuwing bij >60 uur per week (absoluut maximum)
- Waarschuwing bij >48 uur gemiddeld over 16 weken
- Visuele indicator: oranje bij waarschuwing, rood bij overschrijding

### ATW Referentietabel

| Regel | Limiet |
|---|---|
| Maximum per dienst | 12 uur |
| Maximum per week | 60 uur |
| Gemiddelde over 16 weken | 48 uur/week |
| Minimale pauze (shift >5,5u) | 30 minuten |
| Minimale pauze (shift >10u) | 45 minuten |
| Minimale rust tussen diensten | 11 aaneengesloten uren |
| Wekelijkse rust | 36 aaneengesloten uren (of 72 per 2 weken) |

---

## Fase 4: Document Uploads & Contractgeneratie

### 4.1 Document Upload Systeem

- Polymorphic `documents` tabel (koppelbaar aan user, contract, etc.)
- Velden: `documentable_type`, `documentable_id`, `type` (id_front, id_back, contract_signed, overig), `filename`, `path`, `mime_type`, `uploaded_by`
- Upload: ID-kaart (voor + achterkant), getekend contract, overige documenten
- Camera-capture optie op mobiel (via file input met `capture` attribute)
- Admin en werknemer kunnen eigen documenten zien
- Beveiligde opslag (private disk, niet publiek toegankelijk, download via signed URL)
- Admin ziet alle documenten gelinked aan een werknemer in het detail overzicht

### 4.2 Contractgeneratie (extern Python script)

- Extern Python script uit eigen GitHub repository
- Importeren als Git submodule of via Composer script
- Laravel roept het script aan via `Process` facade (shell command)
- Input: gebruikersgegevens (naam, geboortedatum, uurtarief, contracttype, startdatum, etc.)
- Output: DOCX bestand
- Trigger: admin klikt "Genereer contract" — knop alleen zichtbaar wanneer profiel compleet is
- Gegenereerd contract wordt automatisch opgeslagen in het document-systeem (fase 4.1)
- Getekend contract kan daarna ge-upload worden door admin

---

## Fase 5: Verlof- & Ziekteregistratie

### 5.1 Verlofaanvraag Systeem

Nieuwe `leave_requests` tabel:

| Veld | Type |
|---|---|
| `user_id` | foreign key |
| `type` | enum: vakantie, bijzonder_verlof, onbetaald_verlof |
| `start_date` | date |
| `end_date` | date |
| `reason` | text, nullable |
| `status` | enum: pending, approved, rejected |
| `reviewed_by` | foreign key, nullable |
| `reviewed_at` | timestamp, nullable |
| `rejection_reason` | text, nullable |

- Werknemer dient verlofaanvraag in (start- en einddatum, type, reden)
- Admin keurt goed of wijst af (met reden)
- Verlofsaldo bijhouden:
  - Wettelijke vakantiedagen: 20 (configureerbaar)
  - Bovenwettelijke dagen: configureerbaar per werknemer of organisatiebreed
- Verlofdagen zichtbaar in maandoverzicht (als aparte entries/markering)
- Overzicht van resterend saldo op dashboard werknemer

### 5.2 Ziekteregistratie

Nieuwe `sick_leaves` tabel:

| Veld | Type |
|---|---|
| `user_id` | foreign key |
| `start_date` | date |
| `end_date` | date, nullable (null = nog ziek) |
| `notes` | text, nullable |
| `registered_by` | foreign key |

- Admin registreert ziekmelding (werknemer kan ook zelf ziekmelden)
- Start- en einddatum, notities
- Overzicht ziektedagen per werknemer per jaar
- Zichtbaar in uren-overzicht (als aparte status/markering)
- Hersteldmelding door admin of werknemer

---

## Fase 6: Planning / Roostering

> Meest complexe feature — bewust als laatste grote feature gepland.

### 6.1 Data Model

Nieuwe `shifts` tabel:

| Veld | Type |
|---|---|
| `id` | primary key |
| `date` | date |
| `start_time` | time |
| `end_time` | time |
| `user_id` | foreign key, nullable (null = open dienst) |
| `position` | string, nullable (bijv. bar, bediening, keuken) |
| `notes` | text, nullable |
| `published` | boolean, default false |
| `created_by` | foreign key |

Optioneel: `shift_templates` tabel voor herhalende wekelijkse patronen.

### 6.2 Admin Planbord

- Weekoverzicht: dagen als kolommen, werknemers als rijen
- Drag & drop: diensten aanmaken, toewijzen, verplaatsen
- Dag-detail view voor fijnmaziger plannen
- Dienst aanmaken: datum, start/eind tijd, positie, toewijzen aan werknemer
- Planning publiceren: admin klikt "Publiceer" → werknemers worden genotificeerd via e-mail
- Ongepubliceerde diensten zijn alleen zichtbaar voor admin

### 6.3 Werknemer Planning-view

- Weekplanning zien met eigen diensten highlighted
- Eerstvolgende dienst prominent op dashboard
- Uren invullen op basis van geplande dienst: prefill start/eind/pauze vanuit de planning
- Duidelijk onderscheid tussen geplande en gewerkte uren

---

## Fase 7: Afronding & Polish

### 7.1 Deactiveer / Archiveer Gebruikers

- Admin kan werknemer deactiveren (soft-state, geen login meer mogelijk)
- Data blijft bewaard (minimaal 52 weken voor ATW compliance)
- Overzicht actieve vs. inactieve werknemers in admin panel
- Gedeactiveerde gebruiker kan later opnieuw geactiveerd worden

### 7.2 Admin Dashboard Verbeteren

- Wie werkt er vandaag (op basis van planning)
- Openstaande accordeerverzoeken (submitted uren die wachten op goedkeuring)
- Openstaande verlofaanvragen
- Totale geschatte loonkosten deze maand (uren x uurtarief per werknemer)
- Contracten die binnenkort aflopen

### 7.3 E-mail Notificaties

- Nieuwe planning gepubliceerd → e-mail naar betrokken werknemers
- Contract loopt bijna af → e-mail naar admin

### 7.4 Contract-verloop Controle

- Laravel Scheduled Command: dagelijks draaien via `schedule:run`
- Controleert `contract_end_date` van alle actieve werknemers
- Stuurt e-mail naar admin wanneer contract binnen X dagen afloopt
- Standaard: 45 dagen van tevoren (configureerbaar via `config/contracts.php`)
- Eenmalige notificatie per contract (bijgehouden via `contract_expiry_notified_at` op user)
- Config voorbeeld:

```php
// config/contracts.php
return [
    'expiry_notification_days' => 45,
];
```

---

## Buiten Scope v1 (voor v2+)

| Feature | Toelichting |
|---|---|
| Split shifts | Meerdere entries per dag (lunch + diner dienst) |
| Klok in/uit | Live timer naast handmatige invoer |
| Horeca CAO toeslagen | Avond-, nacht-, weekend-, feestdagtoeslagen |
| Payroll-integratie | Export naar AFAS, Nmbrs, Loket, Exact |
| Beschikbaarheid management | Werknemers geven beschikbare dagen/tijden op |
| Dienst-ruil verzoeken | Werknemers onderling diensten ruilen |
| GPS / geofencing | Locatiecontrole bij klok in/uit |
| Uitgebreid audit log | Wie heeft wat wanneer gewijzigd |
| Kiosk modus | Gedeeld tablet op werkplek voor in/uitklokken |
| CAO-specifieke regelengine | Configureerbare regels per collectieve arbeidsovereenkomst |
| Native mobiele app | iOS/Android app naast PWA |
| Automatische roosterplanning | AI-gestuurde optimale roosterindeling |

---

## Technische Regels (doorlopend)

- **Spatie permissions** op alle controllers — geen permission bugs
- **Responsive design** — werkt op mobiel en desktop
- **Geen hard deletes** op users en time entries
- **Seeders schrijven** voor handmatige tests per fase
- **Tests schrijven** per feature (Feature tests via `ddev artisan test`)

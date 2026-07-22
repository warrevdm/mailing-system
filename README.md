# AAB – Fiets klaar mailer

Interne webapp waarmee een medewerker de klantnaam, het e-mailadres en het fietstype invult. De klant ontvangt een professionele HTML-mail met een knop naar Microsoft Bookings.

De verzending verloopt via Microsoft Graph en OAuth 2.0 client credentials. Er wordt geen mailboxwachtwoord gebruikt.

## Vereisten

- PHP 8.1 of hoger
- PHP-extensies: cURL, JSON en mbstring
- Microsoft Entra-appregistratie
- HTTPS op de productieserver

## Installatie

1. Haal de laatste versie binnen:

```bash
git pull origin main
```

2. Werk Composer bij:

```bash
composer update --no-dev
```

3. Kopieer de configuratie indien nodig:

```bash
cp src/config.example.php src/config.php
```

PowerShell:

```powershell
Copy-Item src/config.example.php src/config.php
```

4. Vul in `src/config.php` in:

- `GRAPH_TENANT_ID`
- `GRAPH_CLIENT_ID`
- `GRAPH_CLIENT_SECRET`
- `GRAPH_SENDER_ADDRESS`, standaard `verkoop@aertsactionbike.be`

Zet `src/config.php` nooit op GitHub.

## Microsoft Entra configureren

1. Open Microsoft Entra admin center.
2. Ga naar **App registrations** en maak een nieuwe registratie.
3. Noteer de **Directory (tenant) ID** en **Application (client) ID**.
4. Ga naar **Certificates & secrets** en maak een client secret.
5. Kopieer de secret **Value** onmiddellijk. Gebruik niet de Secret ID.
6. Ga naar **API permissions**.
7. Kies **Microsoft Graph → Application permissions → Mail.Send**.
8. Klik **Grant admin consent**.

De app gebruikt daarna:

- tokenendpoint: `/{tenant}/oauth2/v2.0/token`
- scope: `https://graph.microsoft.com/.default`
- verzendendpoint: `/v1.0/users/verkoop@aertsactionbike.be/sendMail`

`Mail.Send` als application permission kan standaard als iedere mailbox verzenden. Beperk de app daarom bij voorkeur in Exchange Online tot alleen `verkoop@aertsactionbike.be`.

## Lokaal starten

```powershell
php -S localhost:8000
```

Open daarna `http://localhost:8000`.

## Bestanden

- `index.php`: formulier en preview
- `send.php`: validatie, OAuth-token en Microsoft Graph-verzending
- `src/mail-template.php`: HTML- en tekstmail
- `src/config.example.php`: veilig configuratievoorbeeld
- `assets/style.css`: huisstijl
- `assets/app.js`: validatie en mailpreview

## Booking-link

De knop in de mail verwijst naar:

`https://outlook.office365.com/book/Verkochtefietsen@aertsactionbike.be/?ismsaljsauthenabled=true`

## Beveiliging

- Plaats secrets uitsluitend in `src/config.php` of veilige environment variables.
- Geef de Entra-app alleen de vereiste rechten.
- Beperk de Graph-app tot de bedoelde mailbox.
- Beveilig de webapp met Microsoft-login, VPN of serverauthenticatie.
- Voeg rate limiting en een auditlog toe voor productiegebruik.

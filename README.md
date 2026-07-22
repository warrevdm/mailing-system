# AAB – Fiets klaar mailer

Interne webapp waarmee een medewerker de klantnaam, het e-mailadres en het fietstype invult. De klant ontvangt een professionele HTML-mail met een knop naar Microsoft Bookings.

## Vereisten

- PHP 8.1 of hoger
- Composer
- Een SMTP-account dat mail mag versturen
- HTTPS op de productieserver

## Installatie

1. Upload de map naar de webserver.
2. Open een terminal in de projectmap.
3. Installeer PHPMailer:

```bash
composer install --no-dev
```

4. Kopieer de configuratie:

```bash
cp src/config.example.php src/config.php
```

5. Vul in `src/config.php` de SMTP-gegevens in.
6. Beperk de pagina bij voorkeur tot personeel, bijvoorbeeld via Microsoft-login, VPN, serverauthenticatie of een beveiligde intranetomgeving.

## Microsoft 365 SMTP

Standaardwaarden:

- Host: `smtp.office365.com`
- Poort: `587`
- Encryptie: `tls`

De mailbox moet SMTP-verzending toelaten. Gebruik geen persoonlijk wachtwoord in een publieke repository. Bewaar productiegeheimen buiten Git of werk met omgevingsvariabelen.

## Bestanden

- `index.php`: formulier en preview
- `send.php`: validatie en verzending
- `src/mail-template.php`: HTML- en tekstmail
- `src/config.example.php`: SMTP-configuratievoorbeeld
- `assets/style.css`: huisstijl
- `assets/app.js`: validatie en mailpreview

## Booking-link

De knop in de mail verwijst naar:

`https://outlook.office365.com/book/Verkochtefietsen@aertsactionbike.be/?ismsaljsauthenabled=true`

## Aanbevolen volgende beveiligingsstappen

- Login voor medewerkers
- Verzending loggen zonder gevoelige mailinhoud
- Rate limiting
- Automatisch intern BCC-adres of auditlog
- SMTP-wachtwoord via environment variables

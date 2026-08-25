const form = document.querySelector('#mailForm');
const previewButton = document.querySelector('#previewButton');
const previewDialog = document.querySelector('#previewDialog');
const closePreview = document.querySelector('#closePreview');
const previewContent = document.querySelector('#previewContent');
const note = document.querySelector('#pickup_note');
const noteCount = document.querySelector('#noteCount');
const bikeInput = document.querySelector('#bike_type');
const quickIdCard = document.querySelector('#quickIdCard');
const quickLeaseABikePin = document.querySelector('#quickLeaseABikePin');
const quickPickupDate = document.querySelector('#quickPickupDate');
const pickupDateRow = document.querySelector('#pickupDateRow');
const pickupDate = document.querySelector('#pickup_date');
const bikeQuickMessages = document.querySelector('#bikeQuickMessages');
const itemLabel = document.querySelector('#itemLabel');
const itemHelp = document.querySelector('#itemHelp');
const introTitle = document.querySelector('#introTitle');
const introLead = document.querySelector('#introLead');
const introInfo = document.querySelector('#introInfo');
const mailTypeCards = document.querySelectorAll('.mail-type-card');

const fields = {
    customer_name: 'Vul de naam van de klant in.',
    customer_email: 'Vul een geldig e-mailadres in.',
    bike_type: 'Vul een fiets of product in.'
};

const ID_CARD_MESSAGE = 'Gelieve je identiteitskaart mee te brengen in functie van de leasing.';
const LEASE_A_BIKE_PIN_MESSAGE = 'Gelieve je pincode van Lease a Bike mee te brengen.';
const PICKUP_PREFIX = 'Je fiets kan opgehaald worden vanaf ';

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function capitalizeWords(value) {
    return value.replace(/(^|\s|[-/])([\p{L}])/gu, (match, separator, letter) => separator + letter.toLocaleUpperCase('nl-BE'));
}

function getMailType() {
    return form.elements.mail_type.value;
}

function formatDateNl(value) {
    if (!value) return '';
    const [year, month, day] = value.split('-').map(Number);
    const date = new Date(year, month - 1, day);
    return new Intl.DateTimeFormat('nl-BE', { day: 'numeric', month: 'long', year: 'numeric' }).format(date);
}

function setQuickActive(button, active) {
    button.classList.toggle('active', active);
    button.setAttribute('aria-pressed', active ? 'true' : 'false');
}

function clearBikeQuickMessages() {
    setQuickActive(quickIdCard, false);
    setQuickActive(quickLeaseABikePin, false);
    setQuickActive(quickPickupDate, false);
    pickupDate.value = '';
    pickupDateRow.hidden = true;

    const lines = note.value
        .split('\n')
        .map(line => line.trim())
        .filter(line => line !== '' && line !== ID_CARD_MESSAGE && line !== LEASE_A_BIKE_PIN_MESSAGE && !line.startsWith(PICKUP_PREFIX));
    note.value = lines.join('\n');
    noteCount.textContent = note.value.length;
}

function rebuildPickupNote() {
    if (getMailType() !== 'bike') return;

    const lines = note.value
        .split('\n')
        .map(line => line.trim())
        .filter(line => line !== '' && line !== ID_CARD_MESSAGE && line !== LEASE_A_BIKE_PIN_MESSAGE && !line.startsWith(PICKUP_PREFIX));

    if (quickIdCard.classList.contains('active')) {
        lines.unshift(ID_CARD_MESSAGE);
    }

    if (quickLeaseABikePin.classList.contains('active')) {
        lines.unshift(LEASE_A_BIKE_PIN_MESSAGE);
    }

    if (quickPickupDate.classList.contains('active') && pickupDate.value) {
        lines.push(`${PICKUP_PREFIX}${formatDateNl(pickupDate.value)}.`);
    }

    note.value = lines.join('\n');
    noteCount.textContent = note.value.length;
}

function updateMailTypeUi() {
    const isCollectGo = getMailType() === 'collect_go';

    mailTypeCards.forEach(card => {
        const radio = card.querySelector('input[type="radio"]');
        card.classList.toggle('active', radio.checked);
    });

    bikeQuickMessages.hidden = isCollectGo;

    if (isCollectGo) {
        clearBikeQuickMessages();
        itemLabel.textContent = 'Besteld product';
        itemHelp.textContent = 'Bijvoorbeeld: Thule Epos 2 fietsendrager, helm of onderdeel.';
        bikeInput.placeholder = 'Bijvoorbeeld: Thule Epos 2 fietsendrager';
        introTitle.textContent = 'Collect & Go bestelling klaar';
        introLead.textContent = 'Laat de klant weten dat een besteld product klaarstaat voor afhaling in de winkel.';
        introInfo.textContent = 'Collect & Go: geen bookinglink. De klant komt langs tijdens de openingsuren.';
    } else {
        itemLabel.textContent = 'Nieuwe fiets';
        itemHelp.textContent = 'Elk woord start automatisch met een hoofdletter.';
        bikeInput.placeholder = 'Bijvoorbeeld: Trek Madone SL 7 Gen 8';
        introTitle.textContent = 'Nieuwe fiets klaar voor afhaling';
        introLead.textContent = 'Vul de klantgegevens in en verstuur de mail rechtstreeks vanuit verkoop@aertsactionbike.be via Microsoft 365.';
        introInfo.textContent = 'Nieuwe fiets: de klant ontvangt een bookinglink om de afhaling in te plannen.';
    }
}

function validateForm() {
    let valid = true;

    Object.entries(fields).forEach(([name, message]) => {
        const input = form.elements[name];
        const error = input.parentElement.querySelector('.field-error');
        const value = input.value.trim();
        let fieldValid = value !== '';

        if (name === 'customer_email') {
            fieldValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        }

        input.classList.toggle('invalid', !fieldValid);
        error.textContent = fieldValid ? '' : message;
        valid = valid && fieldValid;
    });

    return valid;
}

function buildPreview() {
    if (!validateForm()) return;

    const name = escapeHtml(form.elements.customer_name.value.trim());
    const email = escapeHtml(form.elements.customer_email.value.trim());
    const item = escapeHtml(form.elements.bike_type.value.trim());
    const extra = escapeHtml(form.elements.pickup_note.value.trim()).replaceAll('\n', '<br>');
    const isCollectGo = getMailType() === 'collect_go';

    if (isCollectGo) {
        previewContent.innerHTML = `
            <div style="max-width:640px;margin:auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 8px 24px rgba(20,35,24,.08);font-family:Arial,sans-serif;">
                <div style="background:#fff;padding:20px 32px 18px;text-align:center;">
                    <img src="assets/aab-logo.svg" alt="Aerts Action Bike" style="display:block;width:270px;max-width:100%;height:auto;margin:0 auto;">
                </div>
                <div style="background:#172019;padding:24px 32px 26px;color:#fff;text-align:center;">
                    <h2 style="margin:0;font-size:30px;">Je bestelling staat klaar</h2>
                </div>
                <div style="padding:32px;font-size:16px;line-height:1.7;color:#263229;">
                    <small style="color:#667068;">Naar: ${email}</small><br><br>
                    Dag ${name},<br><br>
                    Goed nieuws: je bestelling <strong>${item}</strong> staat klaar voor afhaling bij Aerts Action Bike.<br><br>
                    Je hoeft hiervoor geen afspraak te maken. Kom gerust langs tijdens onze openingsuren en ons team helpt je verder.
                    <div style="margin:24px 0;padding:18px 20px;background:#f4f7f2;border-radius:10px;line-height:1.7;">
                        <strong>Openingsuren</strong><br>
                        Dinsdag t.e.m. vrijdag: 09:00–12:30 &amp; 13:30–18:00<br>
                        Zaterdag: 09:00–17:00<br>
                        Zondag &amp; maandag: gesloten
                    </div>
                    ${extra ? `<div style="padding:16px 18px;background:#f4f7f2;border-left:4px solid #60bb46;border-radius:8px;"><strong>Extra informatie</strong><br>${extra}</div><br>` : ''}
                    Heb je nog een vraag? Antwoord gerust op deze mail of neem contact op met onze winkel.<br><br>
                    Tot snel!<br><strong>Team Aerts Action Bike</strong>
                </div>
            </div>`;
    } else {
        previewContent.innerHTML = `
            <div style="max-width:640px;margin:auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 8px 24px rgba(20,35,24,.08);font-family:Arial,sans-serif;">
                <div style="background:#fff;padding:20px 32px 18px;text-align:center;">
                    <img src="assets/aab-logo.svg" alt="Aerts Action Bike" style="display:block;width:270px;max-width:100%;height:auto;margin:0 auto;">
                </div>
                <div style="background:#172019;padding:24px 32px 26px;color:#fff;text-align:center;">
                    <h2 style="margin:0;font-size:30px;">Je nieuwe fiets staat klaar</h2>
                </div>
                <div style="padding:32px;font-size:16px;line-height:1.7;color:#263229;">
                    <small style="color:#667068;">Naar: ${email}</small><br><br>
                    Dag ${name},<br><br>
                    <strong>Proficiat met je nieuwe fiets!</strong><br><br>
                    Goed nieuws: je nieuwe <strong>${item}</strong> staat volledig klaar voor afhaling bij Aerts Action Bike.<br><br>
                    Tijdens de afhaling nemen we de tijd om je nieuwe fiets samen te overlopen, correct af te stellen en alle belangrijke uitleg mee te geven.
                    <div style="text-align:center;padding:28px 0;">
                        <span style="display:inline-block;background:#60bb46;padding:15px 24px;border-radius:8px;font-weight:700;">Plan de afhaling van je nieuwe fiets</span>
                    </div>
                    ${extra ? `<div style="padding:16px 18px;background:#f4f7f2;border-left:4px solid #60bb46;border-radius:8px;"><strong>Extra informatie</strong><br>${extra}</div><br>` : ''}
                    Heb je nog een vraag? Antwoord gerust op deze mail of neem contact op met onze winkel.<br><br>
                    Sportieve groeten,<br><strong>Team Aerts Action Bike</strong>
                </div>
            </div>`;
    }

    previewDialog.showModal();
}

bikeInput.addEventListener('input', () => {
    const start = bikeInput.selectionStart;
    const end = bikeInput.selectionEnd;
    bikeInput.value = capitalizeWords(bikeInput.value);
    if (start !== null && end !== null) bikeInput.setSelectionRange(start, end);
});

form.querySelectorAll('input[name="mail_type"]').forEach(radio => {
    radio.addEventListener('change', updateMailTypeUi);
});

quickIdCard.addEventListener('click', () => {
    setQuickActive(quickIdCard, !quickIdCard.classList.contains('active'));
    rebuildPickupNote();
});

quickLeaseABikePin.addEventListener('click', () => {
    setQuickActive(quickLeaseABikePin, !quickLeaseABikePin.classList.contains('active'));
    rebuildPickupNote();
});

quickPickupDate.addEventListener('click', () => {
    const active = !quickPickupDate.classList.contains('active');
    setQuickActive(quickPickupDate, active);
    pickupDateRow.hidden = !active;
    if (active) pickupDate.focus();
    rebuildPickupNote();
});

pickupDate.addEventListener('change', rebuildPickupNote);

note.addEventListener('input', () => {
    noteCount.textContent = note.value.length;
});

previewButton.addEventListener('click', buildPreview);
closePreview.addEventListener('click', () => previewDialog.close());
previewDialog.addEventListener('click', event => {
    if (event.target === previewDialog) previewDialog.close();
});

form.addEventListener('submit', event => {
    bikeInput.value = capitalizeWords(bikeInput.value.trim());
    rebuildPickupNote();

    if (!validateForm()) {
        event.preventDefault();
        return;
    }

    const submitter = event.submitter;
    if (submitter && submitter.value === 'graph') {
        submitter.disabled = true;
        submitter.textContent = 'Mail wordt verstuurd…';
    }
});

updateMailTypeUi();

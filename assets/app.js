const form = document.querySelector('#mailForm');
const previewButton = document.querySelector('#previewButton');
const previewDialog = document.querySelector('#previewDialog');
const closePreview = document.querySelector('#closePreview');
const previewContent = document.querySelector('#previewContent');
const note = document.querySelector('#pickup_note');
const noteCount = document.querySelector('#noteCount');
const downloadMessage = document.querySelector('#downloadMessage');

const fields = {
    customer_name: 'Vul de naam van de klant in.',
    customer_email: 'Vul een geldig e-mailadres in.',
    bike_type: 'Vul het type of model van de nieuwe fiets in.'
};

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
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
    const bike = escapeHtml(form.elements.bike_type.value.trim());
    const extra = escapeHtml(form.elements.pickup_note.value.trim()).replaceAll('\n', '<br>');

    previewContent.innerHTML = `
        <div style="max-width:640px;margin:auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 8px 24px rgba(20,35,24,.08);font-family:Arial,sans-serif;">
            <div style="background:#172019;padding:24px 32px 28px;color:#fff;">
                <div style="display:inline-block;background:#fff;border-radius:10px;padding:9px 12px;margin-bottom:18px;">
                    <img src="assets/aab-logo.svg" alt="Aerts Action Bike" style="display:block;width:190px;max-width:100%;height:auto;">
                </div>
                <h2 style="margin:0;font-size:30px;">Je nieuwe fiets staat klaar</h2>
            </div>
            <div style="padding:32px;font-size:16px;line-height:1.7;color:#263229;">
                <small style="color:#667068;">Naar: ${email}</small><br><br>
                Dag ${name},<br><br>
                <strong>Proficiat met je nieuwe fiets!</strong><br><br>
                Goed nieuws: je nieuwe <strong>${bike}</strong> staat volledig klaar voor afhaling bij Aerts Action Bike.<br><br>
                Tijdens de afhaling nemen we de tijd om je nieuwe fiets samen te overlopen, correct af te stellen en alle belangrijke uitleg mee te geven.
                <div style="text-align:center;padding:28px 0;">
                    <span style="display:inline-block;background:#60bb46;padding:15px 24px;border-radius:8px;font-weight:700;">Plan de afhaling van je nieuwe fiets</span>
                </div>
                ${extra ? `<div style="padding:16px 18px;background:#f4f7f2;border-left:4px solid #60bb46;border-radius:8px;"><strong>Extra informatie</strong><br>${extra}</div><br>` : ''}
                Heb je nog een vraag over je nieuwe fiets? Antwoord gerust op deze mail.<br><br>
                Sportieve groeten,<br><strong>Team Aerts Action Bike</strong>
            </div>
        </div>`;

    previewDialog.showModal();
}

note.addEventListener('input', () => {
    noteCount.textContent = note.value.length;
});

previewButton.addEventListener('click', buildPreview);
closePreview.addEventListener('click', () => previewDialog.close());
previewDialog.addEventListener('click', event => {
    if (event.target === previewDialog) previewDialog.close();
});

form.addEventListener('submit', event => {
    if (!validateForm()) {
        event.preventDefault();
        return;
    }

    downloadMessage.hidden = false;
    downloadMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
});
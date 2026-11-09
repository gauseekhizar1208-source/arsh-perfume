import QRCode from 'qrcode';

const canvas = document.querySelector('[data-upi-qr]');
const status = document.querySelector('[data-qr-status]');
if (canvas) {
    QRCode.toCanvas(canvas, canvas.dataset.paymentUri, {
        width: 300, margin: 4, errorCorrectionLevel: 'M', color: { dark: '#000000', light: '#ffffff' },
    }).then(() => {
        status.hidden = true;
        const download = document.querySelector('[data-qr-download]');
        download.href = canvas.toDataURL('image/png');
        download.hidden = false;
    }).catch(() => {
        status.textContent = 'The QR could not load. Use the UPI ID below or the Open UPI app button to pay.';
    });
}

import path from 'node:path';
import { fileURLToPath } from 'node:url';
import QRCode from 'qrcode';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const out = path.resolve(__dirname, '..', 'public', 'github-qr.png');
const url = 'https://github.com/fadhilbagus-ctrlC/RentalMotor';

await QRCode.toFile(out, url, {
  width: 720,
  margin: 2,
  errorCorrectionLevel: 'M',
  color: {
    dark: '#0b1530',
    light: '#ffffff',
  },
});

console.log(`QR code untuk ${url} -> ${out}`);

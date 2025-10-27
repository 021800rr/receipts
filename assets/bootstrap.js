import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
import ReceiptLineController from './controllers/receipt_line_controller.js';
app.register('receipt-line', ReceiptLineController);

// app.register('some_controller_name', SomeImportedController);

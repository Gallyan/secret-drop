import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

import * as SecretCrypto from './crypto';
import secretForm from './components/secret-form.js';
import secretViewer from './components/secret-viewer.js';

Alpine.plugin(collapse);
Alpine.data('secretForm', secretForm);
Alpine.data('secretViewer', secretViewer);

window.Alpine = Alpine;
window.SecretCrypto = SecretCrypto;

Alpine.start();

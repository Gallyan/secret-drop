import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';

import * as SecretCrypto from './crypto';
import secretForm from './components/secret-form.js';
import secretViewer from './components/secret-viewer.js';
import adminSecrets from './admin-secrets.js';

Alpine.plugin(collapse);
Alpine.plugin(focus);
Alpine.data('secretForm', secretForm);
Alpine.data('secretViewer', secretViewer);
Alpine.data('adminSecrets', adminSecrets);

window.Alpine = Alpine;
window.SecretCrypto = SecretCrypto;

Alpine.start();

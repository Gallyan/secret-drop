import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

import * as SecretCrypto from './crypto';
import secretForm from './components/secret-form.js';

Alpine.plugin(collapse);
Alpine.data('secretForm', secretForm);

window.Alpine = Alpine;
window.SecretCrypto = SecretCrypto;

Alpine.start();

import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

import * as SecretCrypto from './crypto';

Alpine.plugin(collapse);

window.Alpine = Alpine;
window.SecretCrypto = SecretCrypto;

Alpine.start();

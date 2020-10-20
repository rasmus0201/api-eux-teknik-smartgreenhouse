import Vue from 'vue';
import { Datetime } from 'vue-datetime';
import 'vue-datetime/dist/vue-datetime.css';

import { Settings } from 'luxon'
Settings.defaultLocale = 'da';
 
Vue.use(Datetime);
Vue.component('datetime', Datetime);

import App from "./App.vue";
import './bootstrap';

new Vue({
    render: h => h(App)
}).$mount("#app");

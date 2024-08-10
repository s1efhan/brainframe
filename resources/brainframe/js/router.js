import { createRouter, createWebHistory } from 'vue-router';
import Home from '../components/Home.vue';
import Login from '../components/Login.vue';
import Datenschutz from '../components/Datenschutz.vue';
import Impressum from '../components/Impressum.vue';
import Session from '../components/Session.vue';
import Method from '../components/Method.vue';
import Error404 from '../components/Error404.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
      { name: 'home', path: '/brainframe', component: Home },
      { name: 'login', path: '/brainframe/login', component: Login },
      { name: 'datenschutz', path: '/brainframe/datenschutz', component: Datenschutz },
      { name: 'impressum', path: '/brainframe/impressum', component: Impressum },
      { name: 'session', path: '/brainframe/:id', component: Session },
      { name: 'method', path: '/brainframe/:id/:method/:phase', component: Method },
      { name: 'error404', path: '/:catchAll(.*)', component: Error404 },
    ],
});

export default router;

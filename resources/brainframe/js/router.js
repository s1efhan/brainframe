import { createRouter, createWebHistory } from 'vue-router';
import Create from '../components/Create.vue';
import Login from '../components/Login.vue';
import Datenschutz from '../components/Datenschutz.vue';
import Impressum from '../components/Impressum.vue';
import Session from '../components/Session.vue';
import Error404 from '../components/Error404.vue';
import Join from '../components/Join.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
      { path: '/', redirect: { name: 'join' } },
      { path: '/brainframe', redirect: { name: 'join' } },
      { path: '/brainframe/', redirect: { name: 'join' } },
      { name: 'join', path: '/brainframe/join', component: Join },
      { name: 'create', path: '/brainframe/create', component: Create },
      { name: 'login', path: '/brainframe/login', component: Login },
      { name: 'datenschutz', path: '/brainframe/datenschutz', component: Datenschutz },
      { name: 'impressum', path: '/brainframe/impressum', component: Impressum },
      { name: 'session', path: '/brainframe/:id', component: Session },
      { name: 'error404', path: '/:catchAll(.*)', component: Error404 },
    ],
});

export default router;
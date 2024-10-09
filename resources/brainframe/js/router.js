import { createRouter, createWebHistory } from 'vue-router';
import Create from '../components/Create.vue';
import Datenschutz from '../components/Datenschutz.vue';
import Impressum from '../components/Impressum.vue';
import Sitemap from '../components/Sitemap.vue';
import Session from '../components/Session.vue';
import Error404 from '../components/Error404.vue';
import Join from '../components/Join.vue';
import Profile from '../components/Profile.vue';
import Wissen from '../components/Wissen.vue';
import Sessions from '../components/Sessions.vue';
import SechsDreiFünf from '../components/articles/6-3-5-Article.vue';
import WaltDisney from '../components/articles/Walt-Disney-Article.vue';
import CrazyEight from '../components/articles/Crazy-8-Article.vue';
import SixThinkingHats from '../components/articles/6-Thinking-Hats-Article.vue';
import Survey from '../components/Survey.vue';
const router = createRouter({
    history: createWebHistory(),
    routes: [
      { path: '/', redirect: { name: 'join' } },
      { path: '/brainframe', redirect: { name: 'join' } },
      { path: '/brainframe/', redirect: { name: 'join' } },
      { name: 'join', path: '/brainframe/join', component: Join },
      { name: 'create', path: '/brainframe/create', component: Create },
      { name: 'datenschutz', path: '/brainframe/datenschutz', component: Datenschutz },
      { name: 'impressum', path: '/brainframe/impressum', component: Impressum },
      { name: 'sitemap', path: '/brainframe/sitemap', component: Sitemap },
      { name: 'Profile', path: '/brainframe/profile', component: Profile },
      { name: 'Sessions', path: '/brainframe/sessions', component: Sessions },
      { name: 'Wissen', path: '/brainframe/knowledge', component: Wissen },
      { name: '6-3-5', path: '/brainframe/knowledge/6-3-5', component: SechsDreiFünf },
      { name: 'Walt-Disney', path: '/brainframe/knowledge/walt-disney', component: WaltDisney },
      { name: '6-Thinking-Hats', path: '/brainframe/knowledge/6-thinking-hats', component: SixThinkingHats },
      { name: 'Crazy-8', path: '/brainframe/knowledge/crazy-8', component: CrazyEight },
      { name: 'Survey', path: '/brainframe/:sessionId/survey', component: Survey },
       { name: 'error404', path: '/:catchAll(.*)', component: Error404 },
       { name: 'session', path: '/brainframe/:id', component: Session }
    ],
});

export default router;
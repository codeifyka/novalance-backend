import axios from 'axios';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import { MotionPlugin } from '@vueuse/motion'

import './style.css';
import App from './App.vue';

import { HomeVue } from './screens/home';
import { SignInVue } from './screens/sign_in';
import { SignUpVue } from './screens/sign_up';
import { JobPreviewVue } from './screens/job';
import { ServicePreviewVue } from './screens/service';
import { ProjectPreviewVue } from './screens/project';
import { ChatVue } from './screens/chat';
import { ChatPreviewVue } from './screens/chat/preview';

import setupAxios from './libs/ProtectAPI';
import UserSessionRepository from './libs/UserSessionRepository';
import RestUserSession from './libs/RestUserSession';

import { Icon } from '@iconify/vue';
import { FreeLancerPortfolioVue } from './components/freelancer/portfolio';
import { FreeLancerServicesVue } from './components/freelancer/services';
import { FreeLancerCreateServiceVue } from './components/freelancer/services/create';
import { LoadingVue } from './components/loading';
import { ToastVue } from './components/toast';
import { ClientCreateJobVue } from './components/client/jobs/create'
import { FreeLancerCreateProjectVue } from './components/freelancer/portfolio/create';
import { ProfileVue } from './components/client/profile'
import { ClientServicesVue } from './components/client/services'
import { ServiceVue } from '@/components/childcomponent/service';
import { ClientMyjobsVue } from '@/components/client/jobs/my_jobs';
import { FreeLancerProfileVue } from './components/freelancer/profile';
import { ClientUpdateJobVue } from './components/client/jobs/update';
import { ClientChatVue } from './components/client/chat';
import { LandingPageVue } from './screens/landing_page';
import { ProposalsVue } from './screens/proposal';


const app = createApp(App);

const routes = [
    // shared routes
    { path: '/', component: LandingPageVue },
    { path: '/home', component: HomeVue },
    { path: '/sign_in', component: SignInVue },
    { path: '/sign_up', component: SignUpVue },
    { path: '/job/:id', component: JobPreviewVue },
    { path: '/service/:id', component: ServicePreviewVue },
    { path: '/project/:id', component: ProjectPreviewVue },
    { path: '/chat', component: ChatVue },
    { path: '/chat/:id', component: ChatPreviewVue },
    { path: '/services', component: FreeLancerServicesVue },
    { path: '/create_service', component: FreeLancerCreateServiceVue },
    { path: '/services/:username', component: FreeLancerServicesVue },
    { path: '/portfolio', component: FreeLancerPortfolioVue },
    { path: '/create_project', component: FreeLancerCreateProjectVue },
    { path: '/portfolio/:username', component: FreeLancerPortfolioVue },
    { path: '/create_job', component: ClientCreateJobVue },
    { path: '/client/profile', component: ProfileVue },
    { path: '/all_services', component: ClientServicesVue },
    { path: '/my_jobs', component: ClientMyjobsVue },
    { path: '/profile', component: FreeLancerProfileVue },
    { path: '/update_job/:id', component: ClientUpdateJobVue },
    { path: '/chat_client_freelancer/', component: ChatVue },
    { path: '/proposals/', component: ProposalsVue },
    { path: '/create_portfolio/', component: FreeLancerCreateProjectVue },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});


const UNPROTECTED_ROUTES = ['/','/sign_in', '/sign_up'];

router.beforeEach(async (to, from) => {
    if(!UNPROTECTED_ROUTES.includes(to.path)){
        let userSessionRepository = new UserSessionRepository(localStorage);
        let restUserSession = new RestUserSession(axios);
        let access_token = userSessionRepository.getAccessToken();
        
        if(!access_token){
            return { path: '/sign_in' };
        }

        try {
            let response = await restUserSession.checkAuth(access_token);
            if(response.error){
                userSessionRepository.clear();
                return { path: 'sign_in' };
            }
            
            app.provide('axios', setupAxios(access_token));
        } catch (error) {
            console.log(error);
        }
        
    }
});

app.component("Icon", Icon);
app.component("Loading", LoadingVue);
app.component("Toast", ToastVue);
app.component("Service", ServiceVue);

// WebSocket
const ws_host = import.meta.env.MODE == "development" ? "127.0.0.1:8001" : window.location.host; 
const ws = new WebSocket(`ws://${ws_host}/ws`);

ws.onopen = (ev) => { console.log("WebSocket connection is open."); }

app.provide("ws", ws);

app.use(router);
app.use(MotionPlugin)
app.mount('#app');
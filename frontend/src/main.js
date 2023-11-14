import { Icon } from '@iconify/vue';
import { createApp } from 'vue';
import './style.css';
import App from './App.vue';
import { createRouter, createWebHistory } from 'vue-router';
import { HomeVue } from './screens/home';
import { SignInVue } from './screens/sign_in';
import { SignUpVue } from './screens/sign_up';
import UserSessionRepository from './libs/UserSessionRepository';
import RestUserSession from './libs/RestUserSession';
import axios from 'axios';
import { FreeLancerPortfolioVue } from './components/freelancer/portfolio';
import { FreeLancerServicesVue } from './components/freelancer/services';
import { FreeLancerCreateServiceVue } from './components/freelancer/services/create';
import setupAxios from './libs/ProtectAPI';


const app = createApp(App);

const routes = [
    { path: '/', component: HomeVue },
    { path: '/sign_in', component: SignInVue },
    { path: '/sign_up', component: SignUpVue },
    { path: '/services', component: FreeLancerServicesVue },
    { path: '/create_service', component: FreeLancerCreateServiceVue },
    { path: '/services/:username', component: FreeLancerServicesVue },
    { path: '/portfolio', component: FreeLancerPortfolioVue },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});


const UNPROTECTED_ROUTES = ['/sign_in', '/sign_up'];

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

app.use(router);
app.mount('#app');
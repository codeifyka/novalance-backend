import { FreeLancerHeaderVue } from '@/components/freelancer/header';
import { JobPostVue } from '@/components/childcomponent/job_post';
import{ref} from 'vue'
import RestUserSession from '@/libs/RestUserSession';
import { inject, onMounted, ref } from 'vue';

export default {
    components: { FreeLancerHeaderVue , JobPostVue},
    setup(){

        const colors = ref({
            'text':'text-purple-700',
            'background':"bg-purple-50/60",
        })
        return {colors }

        const axios = inject("axios");
        const restUserSession = new RestUserSession(axios);
        let user_info = ref({ user: { username: "undefined" }, services: 0, projects: 0, sells: 0 });

        
        onMounted(async () => {
            user_info.value = (await restUserSession.getInfo()).data;
        });

        return { user_info };

    }
}

import { FreeLancerHeaderVue } from '@/components/freelancer/header';
import RestFreelancerProjects from '@/libs/RestFreelancerProjects';
import type { AxiosInstance } from 'axios';
import { defineComponent, inject, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';

export default defineComponent({
    components: { FreeLancerHeaderVue },
    setup() {
        let route = useRoute();
        let projects = ref<Project[]>([]);

        const axios = inject<AxiosInstance>('axios');
        const restFreelancerProjects = new RestFreelancerProjects(axios!);

        onMounted(async () => {
            let response = await restFreelancerProjects.getAll(route.params.username as string);
            console.log(response);
            if (response.isSuccess()) {
                projects.value = response.value! || [];
            }
        });

        return {
            projects
        }
    }
});

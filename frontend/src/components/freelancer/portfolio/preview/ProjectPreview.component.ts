import { FreeLancerHeaderVue } from '@/components/freelancer/header';
import { SliderVue } from "@/components/slider";
import RestFreelancerProjects from '@/libs/RestFreelancerProjects';
import type { AxiosInstance } from 'axios';
import { inject, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';

export default {
    components: { FreeLancerHeaderVue, SliderVue },
    setup() {
        let route = useRoute();
        let project = ref<Project | null>(null);

        const axios = inject<AxiosInstance>('axios');
        const restFreelancerProjects = new RestFreelancerProjects(axios!);

        onMounted(async () => {
            let response = await restFreelancerProjects.getById(Number(route.params.id));
            console.log(response);
            if (response.isSuccess()) {
                project.value = response.value!;
            } else {
                window.location.href = "/portfolio"
            }
        });

        return {
            project
        }
    }
}

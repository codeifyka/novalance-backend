import { FreeLancerHeaderVue } from '@/components/freelancer/header';
import RestFreelancerServices from "@/libs/RestFreelancerServices";
import type { AxiosInstance } from 'axios';
import { inject, onMounted, ref } from "vue";
import { useRoute } from "vue-router";

export default {
    components: { FreeLancerHeaderVue },
    setup() {
        let route = useRoute();
        let services = ref<Service[]>([]);

        const axios = inject<AxiosInstance>('axios');
        const restFreelancerServices = new RestFreelancerServices(axios!);

        onMounted(async () => {
            let response = await restFreelancerServices.getAll(String(route.params.username));
            console.log(response);
            if (response.isSuccess()) {
                services.value = response.value! || [];
            }
        });

        return {
            services
        };
    }
}

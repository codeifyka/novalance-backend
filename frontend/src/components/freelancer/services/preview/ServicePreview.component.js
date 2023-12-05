import { FreeLancerHeaderVue } from '@/components/freelancer/header';
import RestFreelancerServices from "@/libs/RestFreelancerServices";
import { SliderVue } from "@/components/slider";
import { inject, onMounted, ref } from "vue";
import { useRoute } from "vue-router";

export default {
    components: { FreeLancerHeaderVue, SliderVue },
    setup(){
        let route = useRoute();
        let service = ref({ title: 'Service title', description: 'Description...', category: "Category", images :[], rate: 4, price: 300 });
        
        const axios = inject('axios');
        const restFreelancerServices = new RestFreelancerServices(axios);

        onMounted(async () => {
            let response = await restFreelancerServices.getById(route.params.id);
            console.log(response);
            if(response.data){
                service.value = response.data;
            }
        });

        return { 
            service
        };
    }
}

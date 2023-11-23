import { FreeLancerHeaderVue } from '@/components/freelancer/header';
import { SliderVue } from "@/components/slider";
import RestFreelancerProjects from '@/libs/RestFreelancerProjects';
import { inject, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';

export default {
    components: { FreeLancerHeaderVue, SliderVue },
    setup(){
        let route = useRoute();
        let project = ref({ title: 'Project title', description: 'Description...', images :[] });
        
        const axios = inject('axios');
        const restFreelancerProjects = new RestFreelancerProjects(axios);

        onMounted(async () => {
            let response = await restFreelancerProjects.getById(route.params.id);
            console.log(response);
            if(response.data){
                project.value = response.data;
            }else{
                window.location.href = "/portfolio"
            }
        });

        return {
            project
        }
    }
}

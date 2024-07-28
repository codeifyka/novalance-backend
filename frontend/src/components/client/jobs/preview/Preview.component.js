import { ClientHeaderVue } from '@/components/client/header';
import { ClientFooterVue } from '@/components/client/footer';
import RestClientJobs from "@/libs/RestClientJobs";
import { useRoute } from "vue-router";
import { JobPostVue } from '@/components/childcomponent/job_post';
import {inject,ref,onMounted} from 'vue'
export default {
  components: { ClientHeaderVue , ClientFooterVue ,JobPostVue },
  setup() { 
    let route = useRoute();
    const axios = inject('axios');
    const restClientJobs = new RestClientJobs(axios);
    const jobId = route.params.id
    let job = ref([])
    const colors = ref({
      'text':'text-regal-brown',
      'background':"bg-white",
    })
    onMounted(async () => {
      let response = await restClientJobs.getById(jobId);
      console.log(response);
      if(response.data){
          job.value = response.data;
      }
    });

    return {
    job , colors
    };
  },
};

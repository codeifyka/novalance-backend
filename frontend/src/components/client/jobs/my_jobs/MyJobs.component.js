import { ClientHeaderVue } from '@/components/client/header';
import { ClientFooterVue } from '@/components/client/footer';
import { JobPostVue } from '@/components/childcomponent/job_post';
import RestClientJobs from "@/libs/RestClientJobs";
import { ref , inject , onMounted} from 'vue'

export default {
  components: { ClientHeaderVue , ClientFooterVue ,JobPostVue },
  setup() {   
    const axios = inject('axios');
    let Jobs = ref([])
    let restClientJobs = new RestClientJobs(axios)

    const fetchData = async () => {
      try {
        const response = await restClientJobs.getAll();

        if (response.data) {
          Jobs.value = response.data;
          console.log(response.data)
        } else {
          console.log(response);
        }
      } catch (error) {
        console.error('An error occurred:', error);
      }
    };
    onMounted(fetchData);
    return {
      Jobs
    };
  },
};

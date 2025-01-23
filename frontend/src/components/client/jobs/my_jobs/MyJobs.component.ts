import { ClientHeaderVue } from '@/components/client/header';
import { ClientFooterVue } from '@/components/client/footer';
import { JobPostVue } from '@/components/childcomponent/job_post';
import RestClientJobs from "@/libs/RestClientJobs";
import { ref, inject, onMounted } from 'vue'
import type { AxiosInstance } from 'axios';

export default {
  components: { ClientHeaderVue, ClientFooterVue, JobPostVue },
  setup() {
    const axios = inject<AxiosInstance>('axios');
    let Jobs = ref<JobPost[]>([])
    let restClientJobs = new RestClientJobs(axios!)
    const colors = ref({
      'text': 'text-regal-brown',
      'background': "bg-white",
    })
    const fetchData = async () => {
      try {
        const response = await restClientJobs.getMyJobs();

        if (response.isSuccess()) {
          Jobs.value = response.value || [];
          console.log(response.value)
        } else {
          console.log(response);
        }
      } catch (error) {
        console.error('An error occurred:', error);
      }
    };
    function removeJobCard(jobId: number) {
      // Find and remove the job from the json
      Jobs.value = Jobs.value.filter(job => job.id !== jobId);
    }
    onMounted(fetchData);
    return {
      Jobs, colors, removeJobCard
    };
  },
};

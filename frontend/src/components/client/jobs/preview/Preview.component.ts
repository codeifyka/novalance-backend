import { ClientHeaderVue } from '@/components/client/header';
import { ClientFooterVue } from '@/components/client/footer';
import RestClientJobs from "@/libs/RestClientJobs";
import { useRoute } from "vue-router";
import { JobPostVue } from '@/components/childcomponent/job_post';
import { inject, ref, onMounted } from 'vue'
import type { AxiosInstance } from 'axios';
export default {
  components: { ClientHeaderVue, ClientFooterVue, JobPostVue },
  setup() {
    let route = useRoute();
    const axios = inject<AxiosInstance>('axios');
    const restClientJobs = new RestClientJobs(axios!);
    const jobId = route.params.id
    let job = ref<JobPost | null>(null);
    const colors = ref({
      'text': 'text-regal-brown',
      'background': "bg-white",
    })
    onMounted(async () => {
      let response = await restClientJobs.getById(Number(jobId));
      console.log(response);
      if (response.isSuccess()) {
        job.value = response.value!;
      }
    });

    return {
      job, colors
    };
  },
};

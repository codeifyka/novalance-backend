import { FreeLancerHeaderVue } from '@/components/freelancer/header';
import { JobPostVue } from '@/components/childcomponent/job_post';
import RestUserSession from '@/libs/RestUserSession';
import RestClientJobs from "@/libs/RestClientJobs";
import { inject, onMounted, ref } from 'vue';
import type { AxiosInstance } from 'axios';

export default {
    components: { FreeLancerHeaderVue, JobPostVue },
    setup() {

        const colors = ref({
            'text': 'text-violet-700',
            'background': "bg-violet-50/60",
        })

        const axios = inject<AxiosInstance>("axios");
        const restUserSession = new RestUserSession(axios!);
        let user_info = ref<UserInfo | null>(null);

        let Jobs = ref<JobPost[]>([])
        let restClientJobs = new RestClientJobs(axios!)
        const fetchData = async () => {
            try {
                const response = await restClientJobs.getAll();
                if (response.isSuccess()) {
                    Jobs.value = response.value! || [];
                    console.log(response.value);
                } else {
                    console.log(response);
                }
            } catch (error) {
                console.error('An error occurred:', error);
            }
        };

        onMounted(async () => {
            user_info.value = (await restUserSession.getInfo()).value!;
            fetchData()
        });

        return { user_info, colors, fetchData, Jobs };

    }
}

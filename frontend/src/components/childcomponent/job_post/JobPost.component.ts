import { ref, inject, onMounted } from 'vue';
import RestClientJobs from "@/libs/RestClientJobs";
import UserSessionRepository from "@/libs/UserSessionRepository";
import type { AxiosInstance } from 'axios';

export default {
    props: {
        job: Object,
        colors: Object,
    },
    setup({ job }: { job: JobPost }, { emit }: any) {
        const axios = inject<AxiosInstance>('axios');
        let account_type = ref<AccountType | null>(null);

        const userSessionRepository = new UserSessionRepository(localStorage);

        onMounted(async () => {
            console.log("job: " + job)
            account_type.value = await userSessionRepository.getAccountType();
        });
        const menuValue = ref(false)
        function menu() {
            menuValue.value = !menuValue.value
        }
        const deleteJob = async () => {
            let restClientJobs = new RestClientJobs(axios!)
            let response = await restClientJobs.delete(job.id)
            console.log("deleteJob:", response);
            emit('remove-job', job.id);
        }
        return { job, menu, menuValue, deleteJob, account_type }
    }
}

import { ref, inject, onMounted, defineComponent } from 'vue';
import RestClientJobs from '@/libs/RestClientJobs'
import RestProposals from '@/libs/RestProposals'
import type { AxiosInstance } from 'axios';

export default defineComponent({
    props: {
        proposal: {
            type: Object as () => Proposal,
            required: true
        },
        status: {
            type: String as () => ProposalStatus,
        }
    },
    components: {
    },
    setup({ proposal }) {
        const axios = inject<AxiosInstance>('axios');
        let account_type = ref(null);
        const client = ref<User | null>(null);
        const restJob = new RestClientJobs(axios!)
        const isTruncated = ref(true)
        const isLoading = ref(false);
        const restClientProposals = new RestProposals(axios!)
        const user_id = ref(0);
        const jobTitle = ref("");

        const createdAtDay = ref<string>("");
        const createdAtTime = ref<string>("");
        const startedAtDay = ref<string>("");
        const startedAtTime = ref<string>("");
        const endedAtDay = ref<string>("");
        const endedAtTime = ref<string>("");

        const getJobPost = async () => {
            let response = await restJob.getById(proposal.job_post_id)
            if (response.isSuccess()) {
                jobTitle.value = response.value!.title
                user_id.value = response.value!.user_id
                console.log(user_id)
            }
        }


        const getClient = async () => {
            console.log(user_id.value)
            let response = await restClientProposals.getUserInfo(user_id.value)
            if (response.isSuccess()) {
                client.value = response.value!
            }
        }

        const getFirstLetter = (name: string) => {
            if (name && typeof name === 'string') {
                return name.charAt(0);
            }
            return '';
        }

        onMounted(async () => {
            await getJobPost();
            await getClient();
        });

        if (proposal.created_at) {
            let [date, time] = proposal.created_at.split("T");
            createdAtDay.value = date;
            createdAtTime.value = time.split("Z")[0];
        } if (proposal.started_at) {
            let [date, time] = proposal.started_at.split(" ");
            startedAtDay.value = date;
            startedAtTime.value = time
        } else {
            console.error('created_at is undefined for a proposal:', proposal);
        }

        if (proposal.ends_at) {
            let [date, time] = proposal.ends_at.split(" ");
            endedAtDay.value = date;
            endedAtTime.value = time
        }

        const toggleTruncate = () => {
            isTruncated.value = !isTruncated.value;
        }

        return {
            toggleTruncate, isTruncated,
            account_type,
            proposal,
            getFirstLetter,
            client,
            isLoading,
            jobTitle,
            createdAtDay,
            createdAtTime,
            startedAtDay,
            startedAtTime,
            endedAtDay,
            endedAtTime,
        }
    }
});

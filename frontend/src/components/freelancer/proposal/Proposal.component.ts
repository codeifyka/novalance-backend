import { FreeLancerHeaderVue } from '@/components/freelancer/header';
import { inject, onMounted, ref } from 'vue';
import { DoneVue } from './done';
import { PendingVue } from './pending';
import { ActiveVue } from './active';
import RestProposals from '@/libs/RestProposals';
import type { AxiosInstance } from 'axios';

export default {
    components: { FreeLancerHeaderVue, DoneVue, PendingVue, ActiveVue },
    setup() {
        const axios = inject<AxiosInstance>("axios");
        const isLoading = ref(false);
        const status = ref('active');
        const restClientProposals = new RestProposals(axios!);
        const pendingProposals = ref<Proposal[]>([])
        const doneProposals = ref<Proposal[]>([])
        const activeProposals = ref<Proposal[]>([])

        onMounted(async () => {
            let response = await restClientProposals.getFreelancerProposals();
            if (response.isSuccess()) {
                pendingProposals.value = response.value!.filter(p => p.status === 'pending') || [];
                doneProposals.value = response.value!.filter(p => p.status === 'done') || [];
                activeProposals.value = response.value!.filter(p => p.status === 'active') || [];
            }
        });
        const changeSection = (statusValue: string) => {
            status.value = statusValue;
        };

        return {
            isLoading, changeSection, pendingProposals, doneProposals, activeProposals, status
        };
    }
}

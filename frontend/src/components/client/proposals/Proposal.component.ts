import { ClientHeaderVue } from '@/components/client/header';
import { ClientFooterVue } from '@/components/client/footer';
import { PendingVue } from './pending';
import { ActiveVue } from './active';
import { DoneVue } from './done';
import RestProposals from "@/libs/RestProposals";
import { inject, ref, onMounted } from 'vue'
import type { AxiosInstance } from 'axios';

export default {
  components: { ClientHeaderVue, ClientFooterVue, DoneVue, PendingVue, ActiveVue },
  setup() {
    const status = ref('active');
    const axios = inject<AxiosInstance>('axios');
    const restClientProposals = new RestProposals(axios!);
    const pendingProposals = ref<Proposal[]>([])
    const doneProposals = ref<Proposal[]>([])
    const activeProposals = ref<Proposal[]>([])

    onMounted(async () => {
      let response = await restClientProposals.getMyProposals();
      if (response.isSuccess()) {
        pendingProposals.value = response.value!.filter(p => p.status === 'pending') || [];
        doneProposals.value = response.value!.filter(p => p.status === 'done') || [];
        activeProposals.value = response.value!.filter(p => p.status === 'active') || [];
      }
    });
    const changeSection = (statusValue: string) => {
      status.value = statusValue;
    };

    const handleProposalAccepted = (proposalId: number) => {
      doneProposals.value.push(...pendingProposals.value.filter(proposal => proposal.id == proposalId))
      pendingProposals.value = pendingProposals.value.filter(proposal => proposal.id !== proposalId);
    }

    const handleProposalDeleted = (proposalId: number) => {
      pendingProposals.value = pendingProposals.value.filter(proposal => proposal.id !== proposalId);
    }

    return {
      changeSection, status, pendingProposals, doneProposals, activeProposals, handleProposalAccepted, handleProposalDeleted,
    };
  },
};
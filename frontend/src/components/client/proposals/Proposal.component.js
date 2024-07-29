import { ClientHeaderVue } from '@/components/client/header';
import { ClientFooterVue } from '@/components/client/footer';
import { PendingVue } from './pending';
import { ActiveVue } from './active';
import { DoneVue } from './done';
import RestProposals from "@/libs/RestProposals";
import {inject,ref,onMounted} from 'vue'

export default {
  components: { ClientHeaderVue, ClientFooterVue, DoneVue, PendingVue, ActiveVue },
  setup() {
    const status = ref('active');
    const axios = inject('axios');
    const restClientProposals = new RestProposals(axios);
    const pendingProposals = ref([])
    const doneProposals = ref([])
    const activeProposals = ref([])

    onMounted(async () => {
      let response = await restClientProposals.getMyProposals();
      if(response){
        pendingProposals.value = response.filter(p=> p.status === 'pending');
        doneProposals.value = response.filter(p=> p.status === 'done');
        activeProposals.value = response.filter(p=> p.status === 'active');
      }
    });
    const changeSection = (statusValue) => {
      status.value = statusValue;
    };

    const handleProposalAccepted = (proposalId) => {
      doneProposals.value.push(pendingProposals.filter(proposal => proposal.id == proposalId))
      pendingProposals.value = pendingProposals.filter(proposal => proposal.id !== proposalId);
    }

    const handleProposalDeleted = (proposalId) => {
      pendingProposals.value = pendingProposals.filter(proposal => proposal.id !== proposalId);
    }

    return {
      changeSection, status, pendingProposals, doneProposals, activeProposals, handleProposalAccepted, handleProposalDeleted,
    };
  },
};
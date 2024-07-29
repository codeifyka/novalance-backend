import { ProposalVue } from '@/components/childcomponent/proposal';
import { ClientHeaderVue } from '@/components/client/header';
import { ref } from 'vue';
export default {
  components: { ClientHeaderVue, ProposalVue  },
  props: {proposals: Array},
  setup() {   

    return {

    };
  },
};

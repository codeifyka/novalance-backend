import { ProposalVue } from '@/components/childcomponent/proposal';
import { ClientHeaderVue } from '@/components/client/header';
export default {
  components: { ClientHeaderVue, ProposalVue  },
  props: {proposals: Array},
  setup() {   

    return {

    };
  },
};

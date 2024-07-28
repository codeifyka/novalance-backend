import { ProposalVue } from '@/components/childcomponent/proposal';
import { ClientHeaderVue } from '@/components/client/header';
import { ref } from 'vue';
export default {
  components: { ClientHeaderVue, ProposalVue  },
  props: {proposals: Array},
  setup({proposals}) {   
    const menuValue = ref(false)
    function menu(){
        menuValue.value=!menuValue.value
    }    

    return {
      menuValue,
      menu, proposals
    };
  },
};

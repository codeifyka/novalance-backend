import { ClientHeaderVue } from '@/components/client/header';
import { ClientFooterVue } from '@/components/client/footer';
import RestClientJobs from "@/libs/RestClientJobs";

import {ref , inject} from 'vue'
export default {
  components: { ClientHeaderVue , ClientFooterVue  },
  setup() {   
    const axios = inject('axios');
    // Data
    const title = ref('')
    const description = ref('')
    const skills = ref('')
    const selectedSize = ref('')
    const selectedLevel = ref('')
    const budjet = ref('')
    const time = ref('')
    const files = ref('file.pdf')
    let toastManager = inject("toastManager");
    function handleSkillsChange(  ) {
      // This method is triggered when the selected value changes
      console.log('Selected Skill:', this.skills);
    }
    let onSubmit = async() =>{
      console.log(skills.value)
      let restClientJobs = new RestClientJobs(axios)
      let response = await  restClientJobs.create({
        title:title.value,
        description:description.value,
        skills:skills.value,
        level:selectedLevel.value,
        size:selectedSize.value,
        budjet:budjet.value,
        time:time.value,
        files:files.value,
        expected_delivery_time:time.value
      });
      if(response.data){
        console.log(response.data)
        toastManager.value.alertSuccess('Job post created successfuly');
        setTimeout(() => {
          window.location.href='/'
        }, 2000);
      }else{
        toastManager.value.alertError('Something went wrong!')
        console.log(response)
      }
    }



    return {
      title ,description ,skills ,selectedSize,selectedLevel, budjet, time,files ,onSubmit ,handleSkillsChange
    };
  },
};

import { ClientHeaderVue } from '@/components/client/header';
import { ClientFooterVue } from '@/components/client/footer';
import RestClientJobs from "@/libs/RestClientJobs";

import {ref , inject, mergeProps} from 'vue'
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

    let onSubmit = async() =>{
      // console.log(skills.value)
      let restClientJobs = new RestClientJobs(axios)
      try {
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
            window.location.href='/my_jobs'
          }, 2000);
        }
      } catch (err){
        toastManager.value.alertError(err.response.data.messages)
        let messages = err.response.data.messages;
        let Props = Object.getOwnPropertyNames(messages)
        console.log(Props)
        console.log(err.response.data.messages)
      }
      
    }



    return {
      title ,description ,skills ,selectedSize,selectedLevel, budjet, time,files ,onSubmit 
    };
  },
};

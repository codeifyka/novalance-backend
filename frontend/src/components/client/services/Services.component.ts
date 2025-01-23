import { ClientHeaderVue } from '@/components/client/header';
import { ClientFooterVue } from '@/components/client/footer';
import RestFreelancerServices from "@/libs/RestFreelancerServices";
import { inject, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import type { AxiosInstance } from 'axios';

export default {
  components: { ClientHeaderVue, ClientFooterVue },
  setup() {

    const services = ref<Service[]>([]);
    let route = useRoute();
    const axios = inject<AxiosInstance>('axios');
    const restFreelancerServices = new RestFreelancerServices(axios!);

    onMounted(async () => {
      const query = String(route.query.title)
      if (!query) {
        let response = await restFreelancerServices.getAll2();
        if (response.isSuccess()) {
          services.value = response.value! || [];
        }
      }
      else {
        let searchResponse = await restFreelancerServices.searchByTitle(query);
        if (searchResponse.isSuccess()) {
          services.value = searchResponse.value!;
        }
      }
    });

    return {
      services,
    };
  },
};

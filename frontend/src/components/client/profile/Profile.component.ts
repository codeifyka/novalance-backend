import { ClientHeaderVue } from '@/components/client/header';
import { ClientFooterVue } from '@/components/client/footer';
import RestUserSession from '@/libs/RestUserSession';
import { inject, onMounted, ref, type Ref } from 'vue';
import type ToastsManager from '@/libs/ToastsManager';
import type { AxiosInstance } from 'axios';

export default {
  components: { ClientHeaderVue, ClientFooterVue },
  setup() {
    const axios = inject<AxiosInstance>("axios");
    let user = ref<User | null>(null);
    const toastManager = inject<Ref<ToastsManager>>("toastManager");
    const isLoading = ref(false);

    onMounted(async () => {
      let restUserSession = new RestUserSession(axios!);
      user.value = (await restUserSession.getInfo()).value!.user;
    });

    const save = async () => {
      isLoading.value = true;
      let restUserSession = new RestUserSession(axios!);
      let response = await restUserSession.updateUserInfo(user.value!);
      isLoading.value = false;
      console.log(response)
      if (response.error) {
        toastManager?.value.alertError(response.error);
      } else {
        toastManager?.value.alertSuccess('User info changed successfuly.');
      }
    }
    return {
      user, save, isLoading
    };
  },
};

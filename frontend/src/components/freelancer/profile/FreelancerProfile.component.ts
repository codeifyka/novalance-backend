import { FreeLancerHeaderVue } from '@/components/freelancer/header';
import RestUserSession from '@/libs/RestUserSession';
import type ToastsManager from '@/libs/ToastsManager';
import type { AxiosInstance } from 'axios';
import { inject, onMounted, ref, type Ref } from 'vue';

export default {
    components: { FreeLancerHeaderVue },
    setup() {
        const axios = inject<AxiosInstance>("axios");
        const toastManager = inject<Ref<ToastsManager>>("toastManager");
        let user = ref<User | null>(null);
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
            user,
            save,
            isLoading
        };
    }
}

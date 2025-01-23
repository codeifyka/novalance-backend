import RestUserSession from "@/libs/RestUserSession";
import ToastsManager from "@/libs/ToastsManager";
import UserSessionRepository from "@/libs/UserSessionRepository";
import axios from "axios";
import { inject, ref, type Ref } from "vue";

export default {
    setup() {
        let toastManager = inject<Ref<ToastsManager>>("toastManager");
        const isLoading = ref(false);
        const restUserSession = new RestUserSession(axios!);
        const userSessionRepository = new UserSessionRepository(localStorage);

        const email = ref('');
        const password = ref('');

        const handleErrorMessage = (error: string) => {
            toastManager?.value.alertError(error);
        }

        const signIn = () => {
            isLoading.value = true;
            restUserSession.login({
                email: email.value,
                password: password.value
            }).then(response => {
                isLoading.value = false;
                console.log(response);
                if (response.isSuccess()) {
                    userSessionRepository.save({ access_token: response.value!.access_token, account_type: response.value!.account_type });
                    toastManager?.value.alertSuccess("Sign in successfuly.");
                    setTimeout(() => {
                        location.href = "/home";
                    }, 3000);
                }
            }).catch(error => {
                isLoading.value = false;
                console.log(error);
                handleErrorMessage('Bad credentials');
            });
        }

        return {
            email,
            password,
            signIn,
            isLoading
        };
    }
}
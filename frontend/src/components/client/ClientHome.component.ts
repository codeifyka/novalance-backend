import backgroundImage from "@/assets/bg_client.png";
import logo from "@/assets/images/logo.png";
import RestUserSession from "@/libs/RestUserSession";
import { ref, inject, computed, type Ref } from "vue";
import axios from "axios";
import { ClientHeaderVue } from "./header";
import type ToastsManager from "@/libs/ToastsManager";

export default {
    components: { ClientHeaderVue },
    setup() {
        let isShow = ref(false);
        let isShow2 = ref(false);
        let title = ref("");
        const path = computed(() => ({
            path: "/all_services",
            query: { title: title.value },
        }));
        const isLoading = ref(false);
        const restUserSession = new RestUserSession(axios!);

        const handleErrorMessage = (error: string) => {
            toastManager?.value.alertError(error);
        };

        let toastManager = inject<Ref<ToastsManager>>("toastManager");

        const logout = () => {
            isLoading.value = true;
            restUserSession
                .logout()
                .then(_ => {
                    isLoading.value = false;
                    location.href = "/home";
                })
                .catch((error) => {
                    isLoading.value = false;
                    console.log(error);
                    handleErrorMessage("Bad credentials");
                });
        };
        return {
            backgroundImagePath: backgroundImage,
            logo,
            isShow,
            isShow2,
            path,
            title,
            logout,
        };
    },
};

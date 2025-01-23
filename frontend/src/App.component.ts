import { defineComponent, provide, ref } from "vue";
import ToastsManager from "./libs/ToastsManager";

export default defineComponent({
    name: 'AppVue',
    props: {},
    setup() {
        let toastManager = ref(new ToastsManager());
        provide("toastManager", toastManager);

        return {
            toasts: toastManager?.value.toasts,
        };
    },
});

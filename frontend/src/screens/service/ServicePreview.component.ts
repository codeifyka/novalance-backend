import { FreeLancerServicePreviewVue } from "@/components/freelancer/services/preview";
import UserSessionRepository from "@/libs/UserSessionRepository";
import { defineComponent, onMounted, ref } from "vue";

export default defineComponent({
    name: 'ServiceVue',
    components: { FreeLancerServicePreviewVue },
    props: {},
    setup() {
        let account_type = ref<AccountType | null>(null);

        const userSessionRepository = new UserSessionRepository(localStorage);

        onMounted(async () => {
            account_type.value = userSessionRepository.getAccountType();
        });

        return { account_type };
    },
});

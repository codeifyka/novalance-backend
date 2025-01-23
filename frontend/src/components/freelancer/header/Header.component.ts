import { defineComponent, ref } from "vue";

export default defineComponent({
    props: {
        target: String,
    },
    setup(props) {
        const showMobileMenu = ref(false);

        function toggleMenu() {
            showMobileMenu.value = !showMobileMenu.value;
        }

        return {
            target: props.target,
            showMobileMenu,
            toggleMenu
        }
    }
});
import { defineComponent } from "vue"

export default defineComponent({
    props: {
        toasts: Array,
    },
    setup(props) {

        return {
            toasts: props.toasts
        }
    }
});
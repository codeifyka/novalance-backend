import { ref } from "vue"

export default {
    props: {
        question:String, answer:String, key: Number, 
    },
    setup(){
        const show = ref(false)
        const toggle = () => {
            show.value = !show.value;
        }
        return {toggle,show }
    }
}

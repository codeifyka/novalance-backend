import RestUserSession from "@/libs/RestUserSession";
import axios from "axios";
import { inject, ref } from "vue";

const styles = {
    container: "w-full h-screen flex flex-col items-center bg-regal-purple",
    inner: "w-full lg:w-1/3 rounded-md p-8 my-auto",
    headTitle: "text-2xl lg:text-2xl text-gray-50 font-semibold",
    form: "grid grid-rows-5 grid-cols-2 gap-2 my-10",
    long_input: "bg-transparent text-gray-300 font-semibold border-gray-500 placeholder:text-gray-500 border-b p-2 col-span-2 w-full mx-auto outline-none",
    input: "bg-transparent text-gray-300 font-semibold border-gray-500 placeholder:text-gray-500 border-b p-2 col-span-2 lg:col-span-1 w-full mx-auto outline-none",
    signInBtn: "text-nice-purple hover:text-nice-purple/50 cursor-pointer text-nowrap col-span-2 lg:col-span-1",
    signUpBtn: "px-16 py-2 uppercase text-nice-regal-purple font-semibold rounded-full bg-nice-purple hover:bg-nice-purple/50 cursor-pointer w-full lg:w-fit text-center mx-auto",
};

export default {
    setup(){
        let toastManager = inject("toastManager");

        const isLoading = ref(false);
        const restUserSession = new RestUserSession(axios);
        const isAccountTypeSelected = ref(false);
        const username = ref('');
        const first_name = ref('');
        const last_name = ref('');
        const email = ref('');
        const account_type = ref('freelancer');
        const password = ref('');
        const confirm_password = ref('');

        const handleErrorMessage = (error) => {
            let keys = Object.keys(error);
            let t = 0;
            for(let key of keys){
                setTimeout(() => {
                    toastManager.value.alertError(`${error[key]}`);
                }, t);
                t += 300;
            }
        }

        const signUp = () => {
            isLoading.value = true;
            restUserSession.register({
                username: username.value,
                first_name: first_name.value, 
                last_name: last_name.value, 
                account_type: account_type.value, 
                email: email.value,
                password: password.value,
                confirm_password: password.value,
            }).then(response => {
                isLoading.value = false;
                console.log(response);
                if(response.error){
                    handleErrorMessage(response.data);
                }else{
                    toastManager.value.alertSuccess("Sign up successfuly.");
                    location.href = "/sign_in";
                }
            }).catch(error => {
                isLoading.value = false;
                console.log(error);
                alert('Bad credentials');
            });
        }

        const setAccountType = (accountType) => { 
            isAccountTypeSelected.value = true;
            account_type.value = accountType;
        }

        return {
            styles,
            username,
            first_name,
            last_name,
            email,
            account_type,
            password,
            confirm_password,
            signUp,
            isLoading,
            setAccountType,
            isAccountTypeSelected
        };
    }
}
import { FreeLancerHeaderVue } from '@/components/freelancer/header';
import RestCategories from "@/libs/RestCategories";
import RestFreelancerServices from "@/libs/RestFreelancerServices";
import type ToastsManager from '@/libs/ToastsManager';
import type { AxiosInstance } from 'axios';
import { inject, onMounted, ref, type Ref } from "vue";

export default {
    components: { FreeLancerHeaderVue },
    setup() {
        const axios = inject<AxiosInstance>('axios');
        let toastManager = inject<Ref<ToastsManager>>("toastManager");

        const title = ref('');
        const description = ref('');
        const category = ref<string>('');
        const price = ref(0);
        const images = ref<string[]>([]);
        const images_files = ref<File[]>([]);

        const categories = ref<Category[]>([]);

        onMounted(async () => {
            const restCategories = new RestCategories(axios!);
            let response = await restCategories.getAll();
            if (response.isSuccess()) {
                categories.value = response.value!;
            }
        });

        const onDropImage = (ev: DragEvent) => {
            const files = ev.dataTransfer?.files || [];

            if (files.length > 0) {
                images_files.value.push(...files);
                const file = files[0];
                if (file.type.startsWith('image/')) {
                    const blobUrl = URL.createObjectURL(file);
                    images.value.push(blobUrl);
                    console.log(images.value);
                } else {
                    alert('Please drop a valid image file.');
                }
            }
        }

        const uploadImages = async (): Promise<string[]> => {
            if (images.value.length > 0) {
                const restFreelancerServices = new RestFreelancerServices(axios!);
                let response = await restFreelancerServices.uploadImages(images_files.value);
                if (response.isSuccess()) {
                    return response.value!;
                }
            }

            return [];
        }

        const onSubmit = async () => {
            const restFreelancerServices = new RestFreelancerServices(axios!);

            let response = await restFreelancerServices.create({
                title: title.value,
                description: description.value,
                category: category.value,
                images: await uploadImages(),
                price: price.value
            });

            if (response.isSuccess()) {
                toastManager?.value.alertSuccess('Service created successfuly');
                window.location.href = `/services`;
                console.log(response.value);
            } else {
                toastManager?.value.alertError('Something went wrong!');
                console.log(response);
            }
        }

        return {
            title,
            description,
            category,
            price,
            images,
            categories,
            onDropImage,
            onSubmit
        };
    }
}

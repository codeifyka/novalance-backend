import { FreeLancerHeaderVue } from '@/components/freelancer/header';
import RestFreelancerProjects from '@/libs/RestFreelancerProjects';
import type ToastsManager from '@/libs/ToastsManager';
import type { AxiosInstance } from 'axios';
import { inject, ref, type Ref } from "vue";

export default {
    components: { FreeLancerHeaderVue },
    setup() {
        const axios = inject<AxiosInstance>('axios');
        let toastManager = inject<Ref<ToastsManager>>("toastManager");

        const title = ref('');
        const description = ref('');
        const images = ref<string[]>([]);
        const images_files = ref<File[]>([]);

        const onDropImage = (ev: DragEvent) => {
            const files = ev.dataTransfer?.files || [];

            if (files.length > 0) {
                images_files.value.push(...files);
                const file = files[0];
                if (file.type.startsWith('image/')) {
                    const blobUrl = URL.createObjectURL(file);
                    console.log({ blobUrl });
                    images.value.push(blobUrl);
                    console.log(images.value);
                } else {
                    alert('Please drop a valid image file.');
                }
            }
        }

        const uploadImages = async (): Promise<string[]> => {
            if (images.value.length > 0) {
                const restFreelancerProjects = new RestFreelancerProjects(axios!);
                let response = await restFreelancerProjects.uploadImages(images_files.value);
                if (response.isSuccess()) {
                    return response.value!;
                }
            }

            return [];
        }

        const onSubmit = async () => {
            const restFreelancerProjects = new RestFreelancerProjects(axios!);

            let response = await restFreelancerProjects.create({
                title: title.value,
                description: description.value,
                images: await uploadImages(),
            });

            if (response.isSuccess()) {
                toastManager?.value.alertSuccess('Service created successfuly');
                window.location.href = `/portfolio`;
                console.log(response.value);
            } else {
                toastManager?.value.alertError('Something went wrong!');
                console.log(response);
            }
        }

        return {
            title,
            description,
            images,
            onDropImage,
            onSubmit
        };
    }
}

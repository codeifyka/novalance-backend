interface Service {
    id: number;
    title: string;
    description: string;
    user_id: number;
    category_id: number;
    price_id: number;
    rate: number;
    created_at: string;
    updated_at: string;
}

interface CreateService {
    title: string;
    description: string;
    category: string;
    images: string[];
    price: number;
}

interface ServiceImage {
    path: string;
    service_id: number;
}
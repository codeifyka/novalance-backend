interface JobPost {
    id: number;
    title: string;
    description: string;
    size: number;
    experience_level: string;
    budjet: number;
    expected_delivery_time: string;
    illustrative_files: string;
    user_id: number;
    created_at: string;
    updated_at: string;
}

interface CreateJobPost {
    title: string;
    description: string;
    skills: string;
    level: string;
    size: number;
    budjet: number;
    time: string;
    files: string;
    expected_delivery_time: string;
}
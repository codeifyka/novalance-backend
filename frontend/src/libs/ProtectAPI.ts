import type { AxiosInstance } from "axios";
import axios from "axios";

export default function setupAxios(access_token: string): AxiosInstance {
    axios.defaults.headers.common['Authorization'] = `Bearer ${access_token}`;
    return axios;
}
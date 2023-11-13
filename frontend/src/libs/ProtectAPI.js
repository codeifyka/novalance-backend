import axios from "axios";

export default function setupAxios(access_token){
    axios.defaults.headers.common['Authorization'] = `Bearer ${access_token}`;
    return axios; 
}
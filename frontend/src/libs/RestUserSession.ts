import type { AxiosInstance, AxiosResponse } from "axios";
import Result from "./Result";
import Utils from "./Utils";

export default class RestUserSession {
    axiosInstance: AxiosInstance;

    constructor(axiosInstance: AxiosInstance) {
        this.axiosInstance = axiosInstance;
    }

    async login(userCredentials: UserCredentials): Promise<Result<string, UserSession>> {
        let { email, password } = userCredentials;
        return this.axiosInstance.post<UserCredentials, AxiosResponse<UserSession>>('/api/auth/login', { email, password }).then(Utils.handleResponse<string, UserSession>);
    }

    async register(user: CreateUser): Promise<Result<Record<string, string>, User>> {
        return this.axiosInstance.post('/api/auth/register', user).then(Utils.handleResponseWithErrors<Record<string, string>, User>);
    }

    async logout(): Promise<Result<string, string>> {
        return this.axiosInstance.post('/api/auth/logout').then(Utils.handleResponse<string, string>);
    }

    async checkAuth(access_token: string): Promise<Result<string, UserSession>> {
        return this.axiosInstance.get('/api/auth/me', { headers: { Authorization: `Bearer ${access_token}` } }).then(Utils.handleResponse<string, UserSession>);
    }

    async getInfo(): Promise<Result<string, UserInfo>> {
        // NOTE: To use this function you must use inject("axios") to setup the Authorization header by default.
        return this.axiosInstance.get<User>('/api/user').then(Utils.handleResponse<string, UserInfo>);
    }

    async updateUserInfo(user: User): Promise<Result<string, User>> {
        // NOTE: To use this function you must use inject("axios") to setup the Authorization header by default.
        return this.axiosInstance.post('/api/user', user).then(Utils.handleResponse<string, User>);
    }
};

import type { AxiosResponse } from "axios";
import Result from "./Result";

export default class Utils {
    static handleResponse<Error, Type>(response: AxiosResponse<any, any>) {
        if (response.data.error) {
            return Result.failure<Error, Type>(response.data.error);
        } else {
            return Result.success<Error, Type>(response.data.data);
        }
    }

    static handleResponseWithErrors<Error, Type>(response: AxiosResponse<any, any>) {
        if (response.data.error) {
            return Result.failure<Error, Type>(response.data.data);
        } else {
            return Result.success<Error, Type>(response.data.data);
        }
    }
}
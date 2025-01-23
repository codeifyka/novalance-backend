const ACCESS_TOKEN_KEY = "access_token";
const ACCOUNT_TYPE_KEY = "account_type";

export default class UserSessionRepository {
    localStorage: Storage;
    constructor(localStorage: Storage) {
        this.localStorage = localStorage;
    }

    save(userSession: UserSession): void {
        let { access_token, account_type } = userSession;
        this.localStorage.setItem(ACCESS_TOKEN_KEY, access_token);
        this.localStorage.setItem(ACCOUNT_TYPE_KEY, account_type);
    }

    getAccessToken(): string | null {
        let access_token = this.localStorage.getItem(ACCESS_TOKEN_KEY);
        if (access_token) {
            return access_token;
        }

        return null;
    }

    getAccountType(): AccountType | null {
        let account_type = this.localStorage.getItem(ACCOUNT_TYPE_KEY) as AccountType;
        if (account_type) {
            return account_type;
        }

        return null;
    }

    clear(): void {
        this.localStorage.removeItem(ACCESS_TOKEN_KEY);
        this.localStorage.removeItem(ACCOUNT_TYPE_KEY);
    }
};

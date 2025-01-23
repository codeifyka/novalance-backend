type TokenType = "bearer";

interface UserSession {
    access_token: string;
    account_type: AccountType;
    token_type?: TokenType;
    expires_in?: number;
}
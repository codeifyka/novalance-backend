interface Message {
    id: number;
    sender: number;
    reciever: number;
    message: string;
    chat_id: number;
    isRead: boolean;
    created_at: string;
    updated_at: string;
}
const DEFAULT_TOAST_DURATION = 3000; // 3s

type ToastMessageType = "success" | "error" | "info";
interface ToastMessage {
    message: string;
    type: ToastMessageType;
}

export default class ToastsManager {
    toasts: ToastMessage[];

    constructor() {
        this.toasts = [];
    }

    alertSuccess(message: string, duration_in_seconds?: number): void {
        this.toasts.push({ message, type: "success" });
        if (duration_in_seconds) {
            setTimeout(() => {
                this.toasts.shift();
            }, 1000 * duration_in_seconds);
        } else {
            setTimeout(() => {
                this.toasts.shift();
            }, DEFAULT_TOAST_DURATION);
        }
    }

    alertError(message: string, duration_in_seconds?: number): void {
        this.toasts.push({ message, type: "error" });
        if (duration_in_seconds) {
            setTimeout(() => {
                this.toasts.shift();
            }, 1000 * duration_in_seconds);
        } else {
            setTimeout(() => {
                this.toasts.shift();
            }, DEFAULT_TOAST_DURATION);
        }
    }

    alertInfo(message: string, duration_in_seconds?: number): void {
        this.toasts.push({ message, type: "info" });
        if (duration_in_seconds) {
            setTimeout(() => {
                this.toasts.shift();
            }, 1000 * duration_in_seconds);
        } else {
            setTimeout(() => {
                this.toasts.shift();
            }, DEFAULT_TOAST_DURATION);
        }
    }
}
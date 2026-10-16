import "./bootstrap";
import Alpine from "alpinejs";

// Resilient Clipboard utility for browsers, iframes, and restricted security contexts
window.copyToClipboard = async function (text) {
    if (!text) return false;

    // 1. Modern asynchronous Clipboard API (secure contexts)
    if (navigator.clipboard && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {
            console.warn("navigator.clipboard writeText failed, falling back to execCommand:", err);
        }
    }

    // 2. Legacy execCommand fallback (works in sandboxed cross-origin iframes)
    try {
        const textarea = document.createElement("textarea");
        textarea.value = text;
        textarea.style.position = "fixed";
        textarea.style.left = "-9999px";
        textarea.style.top = "0";
        textarea.setAttribute("readonly", "");
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        textarea.setSelectionRange(0, text.length);
        const success = document.execCommand("copy");
        document.body.removeChild(textarea);
        if (success) {
            return true;
        }
    } catch (err) {
        console.warn("execCommand fallback failed:", err);
    }

    // 3. User prompt manual copy fallback (guarantees text is accessible and user is not stuck)
    try {
        window.prompt("الرجاء نسخ النص يدوياً (Ctrl+C / Cmd+C ثم Enter):", text);
        return true;
    } catch (err) {
        console.error("Manual prompt failed:", err);
        return false;
    }
};

window.Alpine = Alpine;
Alpine.start();

const methods = new Set(['getMe', 'sendMessage', 'deleteMessage', 'setWebhook']);
const limit = 65536;
const reply = (data, status = 200) => new Response(JSON.stringify(data), {
    status, headers: { 'Content-Type': 'application/json; charset=utf-8', 'Cache-Control': 'no-store' }
});

async function sameSecret(actual, expected) {
    if (!actual || !expected || actual.length > 512) return false;
    const encoder = new TextEncoder();
    const [a, b] = await Promise.all([actual, expected].map(value => crypto.subtle.digest('SHA-256', encoder.encode(value))));
    const left = new Uint8Array(a), right = new Uint8Array(b);
    let difference = 0;
    for (let i = 0; i < left.length; i++) difference |= left[i] ^ right[i];
    return difference === 0;
}

async function readBody(request) {
    if (Number(request.headers.get('Content-Length')) > limit) throw new RangeError('Body too large');
    if (!request.body) return '';
    const reader = request.body.getReader();
    const chunks = [];
    let size = 0;
    while (true) {
        const { done, value } = await reader.read();
        if (done) break;
        size += value.byteLength;
        if (size > limit) { await reader.cancel(); throw new RangeError('Body too large'); }
        chunks.push(value);
    }
    const bytes = new Uint8Array(size);
    let offset = 0;
    for (const chunk of chunks) { bytes.set(chunk, offset); offset += chunk.byteLength; }
    return new TextDecoder().decode(bytes);
}

async function forward(url, body, headers = {}, timeout = 4000) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeout);
    try {
        const response = await fetch(url, {
            method: 'POST', body, headers: { 'Content-Type': 'application/json', ...headers },
            redirect: 'manual', signal: controller.signal
        });
        if (response.status >= 300 && response.status < 400) return reply({ ok: false }, 502);
        const data = await response.json();
        return reply(data, response.status);
    } catch {
        // No URLs, message bodies or tokens in logs and error responses.
        return reply({ ok: false }, 502);
    } finally { clearTimeout(timer); }
}

export default {
    async fetch(request, env) {
        const path = new URL(request.url).pathname;
        if (!['/api', '/webhook'].includes(path)) return reply({ ok: false }, 404);
        if (request.method !== 'POST') return reply({ ok: false }, 405);
        const webhook = path === '/webhook';
        const secret = webhook ? env.TELEGRAM_WEBHOOK_SECRET : env.TELEGRAM_RELAY_SECRET;
        const header = webhook ? 'X-Telegram-Bot-Api-Secret-Token' : 'X-Relay-Secret';
        if (!await sameSecret(request.headers.get(header), secret)) return reply({ ok: false }, 403);
        let raw, data;
        try { raw = await readBody(request); data = JSON.parse(raw); }
        catch (error) { return reply({ ok: false }, error instanceof RangeError ? 413 : 400); }
        if (!data || typeof data !== 'object' || Array.isArray(data)) return reply({ ok: false }, 400);
        if (webhook) {
            if (!Number.isInteger(data.update_id)) return reply({ ok: false }, 400);
            let site;
            try { site = new URL(env.TELEGRAM_SITE_URL); } catch { return reply({ ok: false }, 503); }
            if (site.protocol !== 'https:') return reply({ ok: false }, 503);
            return forward(new URL('/api/telegram-webhook.php', site).href, raw,
                { 'X-Telegram-Bot-Api-Secret-Token': secret }, 20000);
        }
        if (!methods.has(data.method) || !data.payload || typeof data.payload !== 'object' || Array.isArray(data.payload)) return reply({ ok: false }, 400);
        if (!env.TELEGRAM_BOT_TOKEN) return reply({ ok: false }, 503);
        return forward(`https://api.telegram.org/bot${env.TELEGRAM_BOT_TOKEN}/${data.method}`, JSON.stringify(data.payload));
    }
};

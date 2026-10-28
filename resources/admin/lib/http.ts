/**
 * JSON/HTML requests outside Inertia visits (media picker, live preview), with Laravel's
 * CSRF protection.
 */
function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export async function request(url: string, { method = 'GET', body, accept = 'application/json', signal }: { method?: string; body?: unknown; accept?: string; signal?: AbortSignal } = {}): Promise<Response> {
    const headers: Record<string, string> = {
        Accept: accept,
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': xsrfToken(),
    };

    let payload: BodyInit | undefined;
    if (body instanceof FormData) {
        payload = body;
    } else if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
        payload = JSON.stringify(body);
    }

    return fetch(url, { method, headers, body: payload, credentials: 'same-origin', signal });
}

export async function requestJson<T>(url: string, options: Parameters<typeof request>[1] = {}): Promise<T> {
    const response = await request(url, options);

    if (!response.ok) {
        const error = new Error(`Request failed with ${response.status}`) as Error & { status: number; body: unknown };
        error.status = response.status;
        error.body = await response.json().catch(() => null);
        throw error;
    }

    return response.json() as Promise<T>;
}

export class ApiClient {
  private cookies: Map<string, string> = new Map();
  private lastRequestTime = 0;

  constructor(
    private baseUrl: string,
    private delayMs: number = 150,
  ) {}

  /**
   * Set initial cookies from a "key=val; key2=val2" string.
   */
  setManualCookies(cookieString: string): void {
    for (const pair of cookieString.split(";")) {
      const trimmed = pair.trim();
      const eqIdx = trimmed.indexOf("=");
      if (eqIdx > 0) {
        const name = trimmed.substring(0, eqIdx).trim();
        const value = trimmed.substring(eqIdx + 1).trim();
        this.cookies.set(name, value);
      }
    }
  }

  /**
   * Capture Set-Cookie headers from a response and merge them.
   */
  private captureResponseCookies(response: Response): void {
    const setCookieHeaders = response.headers.getSetCookie?.() ?? [];
    for (const header of setCookieHeaders) {
      // Parse just the name=value part (before first ;)
      const nameValue = header.split(";")[0].trim();
      const eqIdx = nameValue.indexOf("=");
      if (eqIdx > 0) {
        const name = nameValue.substring(0, eqIdx).trim();
        const value = nameValue.substring(eqIdx + 1).trim();
        this.cookies.set(name, value);
      }
    }
  }

  private buildCookieHeader(): string {
    return Array.from(this.cookies.entries())
      .map(([name, value]) => `${name}=${value}`)
      .join("; ");
  }

  private async rateLimit(): Promise<void> {
    const now = Date.now();
    const elapsed = now - this.lastRequestTime;
    if (elapsed < this.delayMs) {
      await new Promise((resolve) =>
        setTimeout(resolve, this.delayMs - elapsed),
      );
    }
    this.lastRequestTime = Date.now();
  }

  async fetch(
    url: string,
    options: RequestInit = {},
  ): Promise<Response> {
    await this.rateLimit();

    const fullUrl = url.startsWith("http") ? url : `${this.baseUrl}${url}`;

    const headers = new Headers(options.headers);
    const cookieHeader = this.buildCookieHeader();
    if (cookieHeader) {
      headers.set("Cookie", cookieHeader);
    }
    if (!headers.has("User-Agent")) {
      headers.set("User-Agent", "NodeBB-Scraper/1.0");
    }

    const response = await fetch(fullUrl, {
      ...options,
      headers,
      redirect: "manual",
    });

    // Capture any new cookies from the response (including HttpOnly ones)
    this.captureResponseCookies(response);

    return response;
  }

  async fetchJson<T>(url: string, options: RequestInit = {}): Promise<T> {
    const response = await this.fetch(url, options);

    if (!response.ok) {
      const text = await response.text().catch(() => "");
      throw new Error(
        `HTTP ${response.status} for ${url}: ${text.slice(0, 200)}`,
      );
    }

    return response.json() as Promise<T>;
  }

  async followRedirects(
    url: string,
    options: RequestInit = {},
    maxRedirects = 10,
  ): Promise<Response> {
    let response = await this.fetch(url, options);
    let redirects = 0;

    while (
      (response.status === 301 ||
        response.status === 302 ||
        response.status === 303 ||
        response.status === 307 ||
        response.status === 308) &&
      redirects < maxRedirects
    ) {
      const location = response.headers.get("Location");
      if (!location) break;

      const redirectUrl = new URL(location, url).toString();

      // 303 always becomes GET, 301/302 typically become GET for non-GET
      const method =
        response.status === 303 ||
        ((response.status === 301 || response.status === 302) &&
          options.method !== "GET")
          ? "GET"
          : options.method ?? "GET";

      response = await this.fetch(redirectUrl, {
        method,
        headers: options.headers,
      });
      redirects++;
    }

    return response;
  }
}

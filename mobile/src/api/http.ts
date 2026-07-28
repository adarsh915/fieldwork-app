import { API_BASE_URL, API_PREFIX } from "./config";
import { getAuthToken } from "../auth/tokenStore";

export type HttpMethod = "GET" | "POST" | "PUT" | "PATCH" | "DELETE";

export type HttpError = {
  status: number;
  message: string;
  details?: unknown;
};

async function buildHeaders(extra?: HeadersInit): Promise<HeadersInit> {
  const token = await getAuthToken();

  return {
    Accept: "application/json",
    "Content-Type": "application/json",
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...(extra ?? {}),
  };
}

export async function requestJson<TResponse>(
  path: string,
  options?: {
    method?: HttpMethod;
    body?: unknown;
    headers?: HeadersInit;
  },
): Promise<TResponse> {
  const url = `${API_BASE_URL}${API_PREFIX}${path.startsWith("/") ? path : `/${path}`}`;

  const response = await fetch(url, {
    method: options?.method ?? "GET",
    headers: await buildHeaders(options?.headers),
    body:
      options?.body === undefined ? undefined : JSON.stringify(options.body),
  });

  const contentType = response.headers.get("content-type") ?? "";
  const isJson = contentType.includes("application/json");
  const data = isJson
    ? await response.json().catch(() => null)
    : await response.text().catch(() => null);

  if (!response.ok) {
    const err: HttpError = {
      status: response.status,
      message:
        (data as any)?.message ??
        `Request failed with status ${response.status}`,
      details: data ?? undefined,
    };
    throw err;
  }

  return data as TResponse;
}

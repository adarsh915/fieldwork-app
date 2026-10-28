import { API_BASE_URL, API_PREFIX } from "./config";
import { getAuthToken } from "../auth/tokenStore";

export type HttpMethod = "GET" | "POST" | "PUT" | "PATCH" | "DELETE";

export type HttpError = {
  status: number;
  message: string;
  errors?: Record<string, string[]>;
  details?: unknown;
};

async function buildHeaders(extra?: HeadersInit): Promise<HeadersInit> {
  const token = await getAuthToken();

  return {
    Accept: "application/json",
    "Content-Type": "application/json",
    "bypass-tunnel-reminder": "true",
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

  let response: Response;
  try {
    response = await fetch(url, {
      method: options?.method ?? "GET",
      headers: await buildHeaders(options?.headers),
      body:
        options?.body === undefined ? undefined : JSON.stringify(options.body),
    });
  } catch (netErr: any) {
    const err: HttpError = {
      status: 0,
      message: "Network error: Unable to connect to server. Please check your internet connection.",
      details: netErr,
    };
    throw err;
  }

  const contentType = response.headers.get("content-type") ?? "";
  const isJson = contentType.includes("application/json");
  const data = isJson
    ? await response.json().catch(() => null)
    : await response.text().catch(() => null);

  if (!response.ok) {
    let resolvedMessage: string = (data as any)?.message;

    // If Laravel returned validation errors, extract the first clear error
    if ((data as any)?.errors && typeof (data as any).errors === "object") {
      const errKeys = Object.keys((data as any).errors);
      if (errKeys.length > 0) {
        const firstVal = (data as any).errors[errKeys[0]];
        if (Array.isArray(firstVal) && firstVal.length > 0) {
          resolvedMessage = firstVal[0];
        } else if (typeof firstVal === "string") {
          resolvedMessage = firstVal;
        }
      }
    }

    if (!resolvedMessage) {
      if (response.status === 401) {
        resolvedMessage = "Session expired. Please log in again.";
      } else if (response.status === 403) {
        resolvedMessage = "You do not have permission to perform this action.";
      } else if (response.status === 404) {
        resolvedMessage = "The requested resource was not found.";
      } else if (response.status >= 500) {
        resolvedMessage = "Server encountered an error. Please try again later.";
      } else {
        resolvedMessage = `Request failed with status ${response.status}`;
      }
    }

    const err: HttpError = {
      status: response.status,
      message: resolvedMessage,
      errors: (data as any)?.errors,
      details: data ?? undefined,
    };
    throw err;
  }

  return data as TResponse;
}

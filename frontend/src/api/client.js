import { API_BASE_URL } from '../utils/config';
import { getAuthHeaders } from '../auth/authHeaders';

// The one function every API call goes through (design 13.4). Components never call fetch directly.

// An API error with the HTTP status, our error code (SLOT_FULL ...) and Laravel field errors (422).
export class ApiError extends Error {
  constructor(message, status, code = null, fieldErrors = {}) {
    super(message);
    this.status = status;
    this.code = code;
    this.fieldErrors = fieldErrors;
  }
}

const NETWORK_MESSAGE = 'Check your connection and try again.';

// auth: true adds the login headers (Bearer token or local headers).
export async function request(path, { method = 'GET', body, auth = false } = {}) {
  const headers = { Accept: 'application/json' };

  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
  }

  if (auth) {
    Object.assign(headers, await getAuthHeaders());
  }

  let response;
  try {
    response = await fetch(`${API_BASE_URL}${path}`, {
      method,
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
  } catch {
    throw new ApiError(NETWORK_MESSAGE, 0, 'NETWORK_ERROR');
  }

  const data = await readJson(response);

  if (!response.ok) {
    throw toApiError(response.status, data);
  }

  return data;
}

// Builds a query string and skips empty values: { category: 'water', thrill_level: '' } -> "?category=water"
export function queryString(params) {
  const search = new URLSearchParams();

  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      search.set(key, value);
    }
  });

  const text = search.toString();
  return text ? `?${text}` : '';
}

async function readJson(response) {
  try {
    return await response.json();
  } catch {
    return null;
  }
}

// Our services send {"error": {"code", "message"}}. Validation errors use Laravel's {"message", "errors"}.
function toApiError(status, data) {
  if (data?.error) {
    return new ApiError(data.error.message, status, data.error.code);
  }

  if (status === 422 && data?.errors) {
    return new ApiError(data.message || 'Please check the form.', status, 'VALIDATION_ERROR', data.errors);
  }

  if (status === 401) {
    return new ApiError('Please log in to continue.', status, 'UNAUTHENTICATED');
  }

  if (status === 403) {
    return new ApiError('You need staff access for this page.', status, 'FORBIDDEN');
  }

  return new ApiError(status >= 500 ? NETWORK_MESSAGE : data?.message || 'Something went wrong.', status, 'HTTP_ERROR');
}

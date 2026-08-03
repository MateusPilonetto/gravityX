const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
const TOKEN_KEY = 'auth_token';

export function getToken() {
  return localStorage.getItem(TOKEN_KEY);
}

export function setToken(token) {
  localStorage.setItem(TOKEN_KEY, token);
}

export function clearToken() {
  localStorage.removeItem(TOKEN_KEY);
}

export function isAuthenticated() {
  return Boolean(getToken());
}

export class ApiError extends Error {
  constructor(message, status, errors = null) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.errors = errors;
  }

  firstMessage() {
    if (this.errors) {
      const firstField = Object.values(this.errors)[0];
      if (Array.isArray(firstField) && firstField.length) {
        return firstField[0];
      }
    }
    return this.message;
  }
}

async function request(path, { method = 'GET', body = null, auth = true } = {}) {
  const headers = { Accept: 'application/json' };
  const isFormData = body instanceof FormData;
  let httpMethod = method;

 
  if (isFormData && (method === 'PUT' || method === 'PATCH')) {
    body.append('_method', method);
    httpMethod = 'POST';
  }

  if (!isFormData && body) {
    headers['Content-Type'] = 'application/json';
  }

  if (auth) {
    const token = getToken();
    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }
  }

  let response;
  try {
    response = await fetch(`${API_BASE_URL}${path}`, {
      method: httpMethod,
      headers,
      body: isFormData ? body : body ? JSON.stringify(body) : undefined,
    });
  } catch {
    throw new ApiError('Failed to connect to the server.', 0);
  }

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw new ApiError(data.message || 'Something went wrong.', response.status, data.errors);
  }

  return data;
}

export const api = {
  get: (path, options = {}) => request(path, { method: 'GET', ...options }),
  post: (path, body, options = {}) => request(path, { method: 'POST', body, ...options }),
  put: (path, body, options = {}) => request(path, { method: 'PUT', body, ...options }),
};

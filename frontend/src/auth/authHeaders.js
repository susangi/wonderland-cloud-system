import { fetchAuthSession } from 'aws-amplify/auth';
import { AUTH_MODE } from '../utils/config';

// Builds the login headers for API calls. Kept outside React so the API client can use it.

const LOCAL_USER_KEY = 'wl-local-user';

// Demo users for the laptop only (docs/05 section 4). The backend accepts them only with APP_ENV=local.
export const LOCAL_USERS = {
  visitor: { id: 'local-visitor-1', email: 'visitor@wonderland.local', groups: [] },
  admin: { id: 'local-admin-1', email: 'admin@wonderland.local', groups: ['admins'] },
};

export function readLocalUser() {
  try {
    return JSON.parse(localStorage.getItem(LOCAL_USER_KEY));
  } catch {
    return null;
  }
}

export function saveLocalUser(user) {
  localStorage.setItem(LOCAL_USER_KEY, JSON.stringify(user));
}

export function clearLocalUser() {
  localStorage.removeItem(LOCAL_USER_KEY);
}

// Local mode: X-Local-User / X-Local-Groups headers.
// Cognito mode: the ACCESS token (not the ID token), because API Gateway and the services check the access token.
export async function getAuthHeaders() {
  if (AUTH_MODE === 'local') {
    const user = readLocalUser();
    if (!user) return {};

    return { 'X-Local-User': user.id, 'X-Local-Groups': user.groups.join(',') };
  }

  const session = await fetchAuthSession();
  const accessToken = session.tokens?.accessToken?.toString();

  return accessToken ? { Authorization: `Bearer ${accessToken}` } : {};
}

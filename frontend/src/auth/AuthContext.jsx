import { createContext, useContext, useEffect, useState } from 'react';
import { Hub } from 'aws-amplify/utils';
import { fetchAuthSession, signOut } from 'aws-amplify/auth';
import { AUTH_MODE } from '../utils/config';
import { LOCAL_USERS, clearLocalUser, readLocalUser, saveLocalUser } from './authHeaders';

// Keeps the logged in user and role for the whole app (the only global state we have).
// user = { id, email, groups } or null.

const AuthContext = createContext(null);

// Email and groups are in the ID token. Admin menus use groups only to hide links;
// the server's 403 is the real protection.
function userFromIdToken(idTokenPayload) {
  return {
    id: idTokenPayload.sub,
    email: idTokenPayload.email,
    groups: idTokenPayload['cognito:groups'] || [],
  };
}

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    if (AUTH_MODE === 'local') {
      setUser(readLocalUser());
      setIsLoading(false);
      return undefined;
    }

    loadCognitoUser();

    // Amplify tells us when someone signs in or out, so the nav updates at once.
    return Hub.listen('auth', ({ payload }) => {
      if (['signedIn', 'signedOut', 'tokenRefresh'].includes(payload.event)) {
        loadCognitoUser();
      }
    });
  }, []);

  async function loadCognitoUser() {
    try {
      const session = await fetchAuthSession();
      const idToken = session.tokens?.idToken;
      setUser(idToken ? userFromIdToken(idToken.payload) : null);
    } catch {
      setUser(null);
    } finally {
      setIsLoading(false);
    }
  }

  // Local mode only: "Continue as demo visitor / admin".
  function loginAsLocalUser(role) {
    const localUser = LOCAL_USERS[role];
    saveLocalUser(localUser);
    setUser(localUser);
  }

  async function logout() {
    if (AUTH_MODE === 'local') {
      clearLocalUser();
    } else {
      await signOut();
    }
    setUser(null);
  }

  const value = {
    user,
    isLoading,
    isLoggedIn: user !== null,
    isAdmin: user?.groups.includes('admins') ?? false,
    loginAsLocalUser,
    logout,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  return useContext(AuthContext);
}

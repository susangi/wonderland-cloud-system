import { useCallback } from 'react';
import { Navigate, useNavigate, useSearchParams } from 'react-router-dom';
import Logo from '../components/Logo';
import LocalLogin from '../auth/LocalLogin';
import CognitoLogin from '../auth/CognitoLogin';
import { useAuth } from '../auth/AuthContext';
import { AUTH_MODE } from '../utils/config';

// Login and sign up (design/Screens.md section 3). Which form we show depends only on VITE_AUTH_MODE.
export default function LoginPage() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const { isLoggedIn, isLoading } = useAuth();

  // Only go back to pages inside this site (no open redirects).
  const nextPath = searchParams.get('next')?.startsWith('/') ? searchParams.get('next') : '/';

  const handleLoggedIn = useCallback(() => navigate(nextPath, { replace: true }), [navigate, nextPath]);

  if (!isLoading && isLoggedIn && AUTH_MODE === 'local') {
    return <Navigate to={nextPath} replace />;
  }

  return (
    <div className="wl-container">
      <div className="wl-auth">
        <Logo />
        <div className="wl-card">
          {AUTH_MODE === 'cognito' ? <CognitoLogin onLoggedIn={handleLoggedIn} /> : <LocalLogin onLoggedIn={handleLoggedIn} />}
        </div>
      </div>
    </div>
  );
}

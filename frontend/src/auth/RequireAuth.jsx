import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from './AuthContext';
import { LoadingState } from '../components/LoadingState';

// Pages for logged in users. Logged out visitors go to /login and come back here after.
export default function RequireAuth({ children }) {
  const { isLoading, isLoggedIn } = useAuth();
  const location = useLocation();

  if (isLoading) {
    return <LoadingState text="Checking your login…" />;
  }

  if (!isLoggedIn) {
    const next = encodeURIComponent(location.pathname + location.search);
    return <Navigate to={`/login?next=${next}`} replace />;
  }

  return children;
}

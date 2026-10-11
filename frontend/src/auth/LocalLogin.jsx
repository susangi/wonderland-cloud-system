import { User } from 'lucide-react';
import Alert from '../components/Alert';
import { useAuth } from './AuthContext';

// Laptop login (VITE_AUTH_MODE=local): pick a demo role, no password.
// Used until the Cognito user pool exists. The backend refuses these headers outside APP_ENV=local.
export default function LocalLogin({ onLoggedIn }) {
  const { loginAsLocalUser } = useAuth();

  function handleContinue(role) {
    loginAsLocalUser(role);
    onLoggedIn();
  }

  return (
    <div className="wl-stack">
      <h1>Log in</h1>
      <Alert variant="warning" title="Local development login">
        This is the laptop version. In the cloud you log in with your email and password (Amazon Cognito).
      </Alert>
      <button type="button" className="wl-btn wl-btn--primary wl-btn--lg wl-btn--block" onClick={() => handleContinue('visitor')}>
        <User aria-hidden="true" />
        Continue as demo visitor
      </button>
      <button type="button" className="wl-btn wl-btn--lg wl-btn--block" onClick={() => handleContinue('admin')}>
        Continue as demo admin
      </button>
    </div>
  );
}

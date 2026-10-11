import { useEffect } from 'react';
import { Authenticator } from '@aws-amplify/ui-react';
import { I18n } from 'aws-amplify/utils';

// Real login (VITE_AUTH_MODE=cognito). The Amplify Authenticator already has sign in, create account,
// verify code and forgot password, so we only restyle it (design 13.3a, CSS in styles/app.css).

// Same words as design/Screens.md section 3.
I18n.putVocabulariesForLanguage('en', {
  'Sign In': 'Log in',
  'Sign in': 'Log in',
  'Create Account': 'Create account',
  'Forgot your password?': 'Forgot password?',
  'Incorrect username or password.': 'Email or password is incorrect',
  'User already exists': 'An account with this email already exists',
  'An account with the given email already exists.': 'An account with this email already exists',
  'Invalid verification code provided, please try again.': 'That code is wrong or has expired',
});

const FORM_FIELDS = {
  signIn: { username: { label: 'Email', placeholder: 'you@example.com' } },
  signUp: {
    email: { label: 'Email', placeholder: 'you@example.com', order: 1 },
    password: { label: 'Password', order: 2 },
    confirm_password: { label: 'Confirm password', order: 3 },
  },
};

export default function CognitoLogin({ onLoggedIn }) {
  return (
    <Authenticator loginMechanisms={['email']} signUpAttributes={['email']} formFields={FORM_FIELDS}>
      {() => <SignedIn onLoggedIn={onLoggedIn} />}
    </Authenticator>
  );
}

// Shown for a moment after login, then we go back to the page the visitor wanted.
function SignedIn({ onLoggedIn }) {
  useEffect(() => {
    onLoggedIn();
  }, [onLoggedIn]);

  return <p className="wl-muted">Logging you in…</p>;
}

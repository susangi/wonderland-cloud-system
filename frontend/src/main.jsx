import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { Amplify } from 'aws-amplify';
import App from './App';
import { AuthProvider } from './auth/AuthContext';
import { AUTH_MODE, COGNITO_CLIENT_ID, COGNITO_USER_POOL_ID } from './utils/config';

// Styles: design tokens first, then the Wonderland components, then Amplify's login styles,
// then our page layout and the Authenticator restyle (so our overrides win).
import './styles/tokens.css';
import './styles/wonderland.css';
import '@aws-amplify/ui-react/styles.css';
import './styles/app.css';

// Cognito is set up only in cognito mode. In local mode nothing talks to AWS.
if (AUTH_MODE === 'cognito') {
  Amplify.configure({
    Auth: {
      Cognito: {
        userPoolId: COGNITO_USER_POOL_ID,
        userPoolClientId: COGNITO_CLIENT_ID,
        loginWith: { email: true },
        signUpVerificationMethod: 'code',
      },
    },
  });
}

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <BrowserRouter>
      <AuthProvider>
        <App />
      </AuthProvider>
    </BrowserRouter>
  </React.StrictMode>,
);

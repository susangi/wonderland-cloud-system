// All frontend settings come from Vite env variables (VITE_*), so the same code runs
// on the laptop (Vite proxy, local login) and in Amplify (API Gateway, Cognito) without changes.

export const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '');

// "local" = role switcher (laptop only). "cognito" = real login with Amplify.
export const AUTH_MODE = import.meta.env.VITE_AUTH_MODE === 'cognito' ? 'cognito' : 'local';

export const COGNITO_USER_POOL_ID = import.meta.env.VITE_USER_POOL_ID || '';
export const COGNITO_CLIENT_ID = import.meta.env.VITE_USER_POOL_CLIENT_ID || '';

export const METABASE_URL = import.meta.env.VITE_METABASE_URL || '';

// The park runs on Sri Lanka time. All dates and times are shown in this zone.
export const PARK_TIMEZONE = 'Asia/Colombo';

// Booking dates: today up to 90 days ahead (FR-V-09).
export const MAX_BOOKING_DAYS_AHEAD = 90;

// Unpaid bookings hold their places for 15 minutes.
export const PAYMENT_HOLD_MINUTES = 15;

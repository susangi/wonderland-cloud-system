import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// On the laptop there is no API Gateway, so the Vite dev server plays its part.
// Same routing table as the ingress and API Gateway (design 8.2, docs/05 section 3).
// /api/internal/* is not proxied on purpose, same as production.
const ATTRACTION = 'http://127.0.0.1:8001';
const PRICING = 'http://127.0.0.1:8002';
const BOOKING = 'http://127.0.0.1:8003';
const PAYMENT = 'http://127.0.0.1:8004';
const EVENT = 'http://127.0.0.1:8005';

export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    proxy: {
      '/api/attractions': ATTRACTION,
      '/api/tiers': PRICING,
      '/api/time-slots': BOOKING,
      '/api/bookings': BOOKING,
      '/api/admin/bookings': BOOKING,
      '/api/payments': PAYMENT,
      '/api/checkins': EVENT,
      '/api/ride-scans': EVENT,
      '/api/visits': EVENT,
      '/api/analytics': EVENT,
    },
  },
});

import { queryString, request } from './client';

// event-service calls (design 6.5).

export function getMyVisits() {
  return request('/api/visits/me', { auth: true });
}

export function checkIn(bookingReference) {
  return request('/api/checkins', { method: 'POST', body: { booking_reference: bookingReference }, auth: true });
}

export function recordRideScan(bookingReference, attractionId) {
  return request('/api/ride-scans', {
    method: 'POST',
    body: { booking_reference: bookingReference, attraction_id: attractionId },
    auth: true,
  });
}

// from and to are park dates "YYYY-MM-DD".
export function getAnalyticsSummary(from, to) {
  return request(`/api/analytics/summary${queryString({ from, to })}`, { auth: true });
}

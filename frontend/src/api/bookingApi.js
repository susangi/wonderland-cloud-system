import { queryString, request } from './client';

// booking-service calls (design 6.3).

export function getTimeSlots(visitDate) {
  return request(`/api/time-slots${queryString({ date: visitDate })}`);
}

// booking: { tier_id, guests, visit_date, time_slot_id }
export function createBooking(booking) {
  return request('/api/bookings', { method: 'POST', body: booking, auth: true });
}

// card: { card_number, expiry, cvc, card_holder }. Sent once, never stored in the browser.
export function payBooking(bookingReference, card) {
  return request(`/api/bookings/${bookingReference}/pay`, { method: 'POST', body: card, auth: true });
}

export function getMyBookings() {
  return request('/api/bookings/me', { auth: true });
}

export function getBooking(bookingReference) {
  return request(`/api/bookings/${bookingReference}`, { auth: true });
}

export function getAdminBookings(visitDate) {
  return request(`/api/admin/bookings${queryString({ date: visitDate })}`, { auth: true });
}

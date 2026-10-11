import { queryString, request } from './client';

// attraction-service calls (design 6.1).

// filters: { category, thrill_level }. includeRetired works only for admins.
export function getAttractions(filters = {}, { includeRetired = false } = {}) {
  const query = queryString({ ...filters, include_retired: includeRetired ? 1 : '' });

  return request(`/api/attractions${query}`, { auth: includeRetired });
}

export function createAttraction(attraction) {
  return request('/api/attractions', { method: 'POST', body: attraction, auth: true });
}

export function updateAttraction(attractionId, attraction) {
  return request(`/api/attractions/${attractionId}`, { method: 'PUT', body: attraction, auth: true });
}

export function retireAttraction(attractionId) {
  return request(`/api/attractions/${attractionId}/retire`, { method: 'PATCH', auth: true });
}

export function activateAttraction(attractionId) {
  return request(`/api/attractions/${attractionId}/activate`, { method: 'PATCH', auth: true });
}
